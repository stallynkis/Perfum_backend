<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Events\OrderCreated;
use App\Events\ProductStockUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Get all orders
     */
    public function index(Request $request)
    {
        // Optimización: no cargar relación 'user' si se pide muchos registros (dashboard)
        $perPage = $request->get('per_page', 15);
        
        if ($perPage > 50) {
            // Para dashboard con muchos registros, no cargar relaciones
            $query = Order::query();
        } else {
            // Para listados normales, cargar relación user
            $query = Order::with('user');
        }
        
        $query->orderBy('created_at', 'desc');

        // Filter by order source (web customers vs sellers)
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by customer email
        if ($request->has('customer_email')) {
            $query->where('customer_email', $request->customer_email);
        }

        // Filter by requires confirmation
        if ($request->has('requires_confirmation')) {
            $query->requiresConfirmation();
        }

        // Pagination
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'nullable|in:web,seller',
            'user_id' => 'nullable|exists:users,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_document' => 'nullable|string|max:50',
            'delivery_type' => 'required|in:home,agency',
            'shipping_address' => 'nullable|string',
            'shipping_district' => 'nullable|string|max:100',
            'shipping_reference' => 'nullable|string',
            'agency_type' => 'nullable|in:olva,shalom',
            'agency_id' => 'nullable|string|max:100',
            'agency_name' => 'nullable|string|max:255',
            'agency_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:paypal,yape,cash,card,transfer',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled,completed',
            'transaction_id' => 'nullable|string|max:255',
            'approval_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'document_type' => 'nullable|in:ticket,boleta,factura'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Generate order number
            $orderNumber = Order::generateOrderNumber();

            // Prepare items with product details
            $orderItems = [];
            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                
                if (!$product) {
                    throw new \Exception("Producto con ID {$item['id']} no encontrado");
                }

                // Check stock
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para {$product->name}. Disponible: {$product->stock}");
                }

                // Guardar stock anterior
                $oldStock = $product->stock;

                // Reduce stock
                $product->decrement('stock', $item['quantity']);
                
                // Refrescar modelo para obtener nuevo stock
                $product->refresh();

                // 🔴 EMITIR EVENTO: Stock actualizado en tiempo real
                broadcast(new ProductStockUpdated($product, $oldStock, $product->stock))->toOthers();

                // Add to order items
                $orderItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'image' => $product->image,
                    'brand' => $product->brand,
                    'category' => $product->category
                ];
            }

            // Determine if requires admin confirmation (Yape needs confirmation)
            $requiresConfirmation = $request->payment_method === 'yape';
            
            // Usar valores del request o determinar automáticamente
            $source = $request->source ?? (in_array($request->payment_method, ['cash', 'card', 'transfer']) ? 'seller' : 'web');
            $paymentStatus = $request->payment_status ?? (in_array($request->payment_method, ['paypal', 'cash', 'card', 'transfer']) ? 'paid' : 'pending');
            $orderStatus = $request->status ?? 'pending';

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $request->user_id ?? auth()->id(),
                'source' => $source,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_document' => $request->customer_document,
                'delivery_type' => $request->delivery_type,
                'shipping_address' => $request->shipping_address,
                'shipping_district' => $request->shipping_district,
                'shipping_reference' => $request->shipping_reference,
                'agency_type' => $request->agency_type,
                'agency_id' => $request->agency_id,
                'agency_name' => $request->agency_name,
                'agency_address' => $request->agency_address,
                'items' => $orderItems,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'total' => $request->total,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'approval_code' => $request->approval_code,
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'notes' => $request->notes,
                'requires_admin_confirmation' => $requiresConfirmation
            ]);

            // 👤 GUARDAR CLIENTE automáticamente si es venta de vendedor
            if ($source === 'seller' && $order->user_id && $request->customer_name && 
                $request->customer_name !== 'Cliente en tienda' && 
                $request->customer_name !== 'CLIENTES VARIOS' &&
                trim($request->customer_name) !== '') {
                
                try {
                    // Normalizar documento a string
                    $customerDocument = $request->customer_document ? (string)$request->customer_document : null;
                    if ($customerDocument === '00000000') {
                        $customerDocument = null;
                    }
                    
                    // 1️⃣ GUARDAR EN SELLER_CUSTOMERS (vendedor específico)
                    $existingCustomer = \App\Models\SellerCustomer::where('seller_id', $order->user_id)
                        ->where(function($query) use ($customerDocument, $request) {
                            if ($customerDocument) {
                                $query->where('document', $customerDocument);
                            } else {
                                $query->where('name', $request->customer_name);
                            }
                        })
                        ->first();

                    if (!$existingCustomer) {
                        if ($customerDocument || $request->customer_phone || $request->customer_email) {
                            \App\Models\SellerCustomer::create([
                                'seller_id' => $order->user_id,
                                'name' => $request->customer_name,
                                'document' => $customerDocument,
                                'phone' => $request->customer_phone ?: null,
                                'email' => $request->customer_email ?: null,
                                'address' => $request->shipping_address ?: null
                            ]);
                        }
                    } else {
                        $updateData = [];
                        if (!$existingCustomer->document && $customerDocument) {
                            $updateData['document'] = $customerDocument;
                        }
                        if (!$existingCustomer->phone && $request->customer_phone) {
                            $updateData['phone'] = $request->customer_phone;
                        }
                        if (!$existingCustomer->email && $request->customer_email) {
                            $updateData['email'] = $request->customer_email;
                        }
                        if (!$existingCustomer->address && $request->shipping_address) {
                            $updateData['address'] = $request->shipping_address;
                        }
                        if (!empty($updateData)) {
                            $existingCustomer->update($updateData);
                        }
                    }

                    // 2️⃣ GUARDAR EN BUSINESS_PARTNERS (global - Socios de Negocio)
                    if ($customerDocument) {
                        $existingPartner = \App\Models\BusinessPartner::where('ruc', $customerDocument)
                            ->where('type', 'customer')
                            ->first();

                        if (!$existingPartner) {
                            \App\Models\BusinessPartner::create([
                                'name' => $request->customer_name,
                                'type' => 'customer',
                                'ruc' => $customerDocument,
                                'phone' => $request->customer_phone ?: null,
                                'email' => $request->customer_email ?: null,
                                'address' => $request->shipping_address ?: null,
                                'is_active' => true,
                                'notes' => 'Auto-creado desde venta #' . $orderNumber
                            ]);
                            \Log::info('✅ Cliente guardado en Business Partners', ['ruc' => $customerDocument, 'name' => $request->customer_name]);
                        } else {
                            // Actualizar datos si están vacíos
                            $updatePartner = [];
                            if (!$existingPartner->phone && $request->customer_phone) {
                                $updatePartner['phone'] = $request->customer_phone;
                            }
                            if (!$existingPartner->email && $request->customer_email) {
                                $updatePartner['email'] = $request->customer_email;
                            }
                            if (!$existingPartner->address && $request->shipping_address) {
                                $updatePartner['address'] = $request->shipping_address;
                            }
                            if (!empty($updatePartner)) {
                                $existingPartner->update($updatePartner);
                                \Log::info('🔄 Cliente actualizado en Business Partners', ['ruc' => $customerDocument]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('⚠️ Error guardando cliente: ' . $e->getMessage());
                }
            }


            // 🔔 CREAR NOTIFICACIÓN cuando se crea una orden
            /*
            \App\Models\Notification::create([
                'type' => 'new_order',
                'title' => '🛒 Nuevo Pedido',
                'message' => "Pedido #{$orderNumber} - {$request->customer_name} - S/ {$request->total}",
                'priority' => $requiresConfirmation ? 'high' : 'medium',
                'read' => false,
                'related_tab' => 'pedidos',
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'data' => [
                    'order_number' => $orderNumber,
                    'customer_name' => $request->customer_name,
                    'total' => $request->total,
                    'payment_method' => $request->payment_method,
                    'requires_confirmation' => $requiresConfirmation
                ]
            ]);
            */

            // 🔴 EMITIR EVENTO: Nueva orden creada
            broadcast(new OrderCreated($order))->toOthers();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('❌ ERROR AL CREAR PEDIDO:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get a specific order
     */
    public function show($id)
    {
        $order = Order::with('user')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    /**
     * Update order status
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        // Validación base
        $rules = [
            'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed,refunded',
            'admin_notes' => 'nullable|string',
            'tracking_number' => 'nullable|string|max:100',
            'tracking_order_number' => 'nullable|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0'
        ];

        // Si se está cambiando a "shipped" y el pedido es por agencia (Olva/Shalom)
        // entonces los números de tracking son OBLIGATORIOS
        if ($request->status === 'shipped' && $order->delivery_type === 'agency') {
            $rules['tracking_number'] = 'required|string|max:100';
            $rules['tracking_order_number'] = 'required|string|max:100';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Para marcar como enviado por agencia, debe proporcionar el número de guía y número de orden'
            ], 422);
        }

        // Preparar datos para actualizar
        $updateData = $request->only(['status', 'payment_status', 'admin_notes']);

        // Si se proporciona tracking, agregarlo
        if ($request->has('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }
        
        if ($request->has('tracking_order_number')) {
            $updateData['tracking_order_number'] = $request->tracking_order_number;
        }

        // Si se proporciona costo de envío, agregarlo y recalcular el total
        if ($request->has('shipping_cost')) {
            $newShippingCost = $request->shipping_cost;
            $updateData['shipping_cost'] = $newShippingCost;
            
            // Recalcular el total: subtotal + tax + shipping_cost
            $updateData['total'] = $order->subtotal + $order->tax + $newShippingCost;
        }

        // Si se cambia a "shipped", guardar la fecha de envío
        if ($request->status === 'shipped' && $order->status !== 'shipped') {
            $updateData['shipped_at'] = now();
        }

        $order->update($updateData);

        // 🔔 Crear notificación cuando cambia el estado importante
        if (isset($updateData['status'])) {
            $statusMessages = [
                'processing' => '⏳ Pedido en Preparación',
                'shipped' => '🚚 Pedido Enviado',
                'delivered' => '✅ Pedido Entregado',
                'cancelled' => '❌ Pedido Cancelado'
            ];

            if (isset($statusMessages[$updateData['status']])) {
                \App\Models\Notification::create([
                    'type' => 'order_status_change',
                    'title' => $statusMessages[$updateData['status']],
                    'message' => "Pedido #{$order->order_number} - Estado: {$updateData['status']}",
                    'priority' => 'medium',
                    'read' => false,
                    'related_tab' => 'pedidos',
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'data' => [
                        'order_number' => $order->order_number,
                        'old_status' => $order->status,
                        'new_status' => $updateData['status']
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido actualizado exitosamente',
            'order' => $order
        ]);
    }

    /**
     * Confirm payment (for Yape orders)
     */
    public function confirmPayment(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        $order->markAsPaid($request->transaction_id);

        // 🔔 Crear notificación cuando se confirma el pago
        \App\Models\Notification::create([
            'type' => 'payment_confirmed',
            'title' => '💰 Pago Confirmado',
            'message' => "Pago confirmado para pedido #{$order->order_number} - S/ {$order->total}",
            'priority' => 'medium',
            'read' => false,
            'related_tab' => 'pedidos',
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'data' => [
                'order_number' => $order->order_number,
                'total' => $order->total,
                'transaction_id' => $request->transaction_id
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago confirmado exitosamente',
            'order' => $order
        ]);
    }

    /**
     * Cancel order
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido no puede ser cancelado'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Restore stock
            foreach ($order->items as $item) {
                $product = Product::find($item['id']);
                if ($product) {
                    $product->increment('stock', $item['quantity']);
                }
            }

            $order->cancel('Cancelado por usuario');
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders statistics
     */
    public function stats()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending' => Order::pending()->count(),
            'processing' => Order::processing()->count(),
            'shipped' => Order::shipped()->count(),
            'delivered' => Order::delivered()->count(),
            'cancelled' => Order::cancelled()->count(),
            'requires_confirmation' => Order::requiresConfirmation()->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'pending_revenue' => Order::where('payment_status', 'pending')->sum('total')
        ];

        return response()->json($stats);
    }

    /**
     * Get orders for the authenticated customer
     */
    public function getCustomerOrders(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Obtener pedidos del usuario autenticado (por user_id O por email)
        $orders = Order::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('customer_email', $user->email);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get orders for the authenticated seller (OPTIMIZADO)
     */
    public function getSellerOrders(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Query optimizada solo para órdenes del vendedor
        $query = Order::where('user_id', $user->id)
            ->where('source', 'seller')
            ->select(['id', 'order_number', 'customer_name', 'customer_email', 'customer_phone', 'customer_document', 
                     'total', 'subtotal', 'tax', 'payment_method', 'payment_status', 'status', 'document_type', 
                     'items', 'user_id', 'source', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'desc');

        // Filtro por fecha si se proporciona
        if ($request->has('date')) {
            $date = $request->date;
            $query->whereDate('created_at', $date);
        }

        // Filtro por rango de fechas
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get seller stats for today (OPTIMIZADO)
     */
    public function getSellerTodayStats(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $today = now()->toDateString();

        // Query ultra-optimizada usando agregaciones
        $stats = Order::where('user_id', $user->id)
            ->where('source', 'seller')
            ->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as transactions, SUM(total) as revenue')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => (int) ($stats->transactions ?? 0),
                'revenue' => (float) ($stats->revenue ?? 0)
            ]
        ]);
    }
}
