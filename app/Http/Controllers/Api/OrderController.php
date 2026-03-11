<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\BillingDocument;
use App\Models\BillingConfig;
use App\Events\OrderCreated;
use App\Events\ProductStockUpdated;
use App\Services\FactuFlashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'payment_method' => 'required|in:paypal,yape,cash,card,transfer,mixed',
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
                'user_id' => $request->user_id ?? auth('sanctum')->id(),
                'source' => $source,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_document' => $request->customer_document,
                'document_type' => $request->document_type,
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

            // � REGISTRAR MOVIMIENTO DE CAJA para ventas de vendedor
            // Usar auth('sanctum') porque POST /orders es ruta publica
            $sanctumUser = auth('sanctum')->user();
            if ($source === 'seller' && $sanctumUser) {
                try {
                    $cashRegister = CashRegister::where('responsible_user_id', $sanctumUser->id)
                        ->where('is_active', true)
                        ->first();

                    if ($cashRegister) {
                        $currentSession = $cashRegister->currentSession();
                        if ($currentSession) {
                            // ✅ Incrementar expected_amount PRIMERO (siempre, independiente del movimiento)
                            $currentSession->increment('expected_amount', $request->total);

                            // Registrar movimiento (puede fallar sin afectar el balance)
                            try {
                                // 'mixed' no es un valor ENUM válido → usar null
                                $pmForMovement = in_array($request->payment_method, ['cash', 'card', 'yape', 'transfer'])
                                    ? $request->payment_method
                                    : null;

                                CashMovement::create([
                                    'cash_session_id'   => $currentSession->id,
                                    'type'              => 'sale',
                                    'amount'            => $request->total,
                                    'description'       => 'Venta POS - ' . $orderNumber,
                                    'reference_id'      => $order->id,
                                    'reference_type'    => 'order',
                                    'user_id'           => $sanctumUser->id,
                                    'seller_id'         => $sanctumUser->id,
                                    'customer_name'     => $request->customer_name,
                                    'customer_document' => $request->customer_document,
                                    'payment_method'    => $pmForMovement,
                                    'document_type'     => $request->document_type,
                                ]);
                            } catch (\Exception $movException) {
                                \Log::warning('⚠️ No se pudo crear movimiento de caja (balance ya actualizado): ' . $movException->getMessage());
                            }

                            \Log::info('💰 Balance de caja actualizado', [
                                'order'   => $orderNumber,
                                'total'   => $request->total,
                                'session' => $currentSession->id
                            ]);
                        } else {
                            \Log::warning('⚠️ Vendedor sin sesión de caja abierta', ['user_id' => $sanctumUser->id]);
                        }
                    } else {
                        \Log::warning('⚠️ Vendedor sin caja asignada', ['user_id' => $sanctumUser->id]);
                    }
                } catch (\Exception $cashException) {
                    \Log::error('❌ Error registrando movimiento de caja: ' . $cashException->getMessage());
                    // No interrumpir la orden por error de caja
                }
            }

            // �🔴 EMITIR EVENTO: Nueva orden creada
            broadcast(new OrderCreated($order))->toOthers();

            // 🧾 Emitir comprobante electrónico para ventas POS del vendedor
            $billingResult = null;
            if ($source === 'seller' && in_array($request->document_type, ['boleta', 'factura'])) {
                try {
                    $factuFlash = FactuFlashService::make();
                    if ($factuFlash) {
                        $items = collect($order->items)->map(function ($item) {
                            return [
                                'cod_producto' => (string) ($item['id'] ?? $item['product_id'] ?? 'PROD'),
                                'descripcion' => $item['name'] ?? $item['product_name'] ?? 'Producto',
                                'cantidad' => $item['quantity'] ?? 1,
                                'precio_unitario' => $item['price'] ?? $item['unit_price'] ?? 0,
                                'unidad' => 'NIU',
                            ];
                        })->toArray();

                        $rawDoc = $request->customer_document ?? '';
                        $cleanDoc = trim(preg_replace('/^(DNI|RUC|CE)\s*:\s*/i', '', $rawDoc));

                        if ($request->document_type === 'factura') {
                            $billingResult = $factuFlash->emitirFactura(
                                [
                                    'ruc' => $cleanDoc,
                                    'razon_social' => $request->customer_name ?? '',
                                ],
                                $items,
                                ['origin_type' => 'order', 'origin_id' => $order->id]
                            );
                        } else {
                            $clientData = ['nombre' => $request->customer_name ?? 'CLIENTE'];
                            if (!empty($cleanDoc) && strlen($cleanDoc) === 8) {
                                $clientData['dni'] = $cleanDoc;
                            } else {
                                $clientData['num_doc'] = '-';
                            }
                            $billingResult = $factuFlash->emitirBoleta(
                                $clientData,
                                $items,
                                ['origin_type' => 'order', 'origin_id' => $order->id]
                            );
                        }

                        Log::info('🧾 Comprobante emitido para venta POS', [
                            'order' => $order->order_number,
                            'tipo' => $request->document_type,
                            'resultado' => $billingResult['success'] ?? false,
                            'numero' => $billingResult['numero'] ?? null,
                        ]);

                        // Guardar estado de facturación en el pedido
                        $order->update([
                            'billing_status' => ($billingResult['success'] ?? false) ? 'emitida' : 'error',
                            'billing_number' => $billingResult['numero'] ?? null,
                            'billing_error' => ($billingResult['success'] ?? false) ? null : ($billingResult['error'] ?? 'Error desconocido'),
                            'billing_document_id' => $billingResult['document']->id ?? null,
                        ]);
                    } else {
                        $order->update(['billing_status' => 'no_configurado', 'billing_error' => 'Facturación no configurada']);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error al emitir comprobante POS: ' . $e->getMessage(), [
                        'order_id' => $order->id,
                    ]);
                    $billingResult = ['success' => false, 'error' => $e->getMessage()];
                    $order->update([
                        'billing_status' => 'error',
                        'billing_error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'order' => $order,
                'billing' => $billingResult,
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

        // 🧾 Emitir comprobante electrónico al entregar el pedido
        $billingResult = null;
        if (isset($updateData['status']) && $updateData['status'] === 'delivered'
            && in_array($order->document_type, ['boleta', 'factura'])) {
            try {
                $factuFlash = FactuFlashService::make();
                if ($factuFlash) {
                    // Extraer número de documento limpio (quitar prefijos como "DNI: ", "RUC: ")
                    $rawDoc = $order->customer_document ?? '';
                    $cleanDoc = preg_replace('/^(DNI|RUC|CE)\s*:\s*/i', '', $rawDoc);
                    $cleanDoc = trim($cleanDoc);

                    // Construir items desde los productos del pedido
                    $items = collect($order->items)->map(function ($item) {
                        return [
                            'cod_producto' => (string) ($item['id'] ?? $item['product_id'] ?? 'PROD'),
                            'descripcion' => $item['name'] ?? $item['product_name'] ?? 'Producto',
                            'cantidad' => $item['quantity'] ?? 1,
                            'precio_unitario' => $item['price'] ?? $item['unit_price'] ?? 0,
                            'unidad' => 'NIU',
                        ];
                    })->toArray();

                    if ($order->document_type === 'factura') {
                        $billingResult = $factuFlash->emitirFactura(
                            [
                                'ruc' => $cleanDoc,
                                'razon_social' => $order->customer_name ?? '',
                            ],
                            $items,
                            ['origin_type' => 'order', 'origin_id' => $order->id]
                        );
                    } else {
                        $clientData = [
                            'nombre' => $order->customer_name ?? 'CLIENTE',
                        ];
                        if (!empty($cleanDoc) && strlen($cleanDoc) === 8) {
                            $clientData['dni'] = $cleanDoc;
                        } else {
                            $clientData['num_doc'] = '-';
                        }
                        $billingResult = $factuFlash->emitirBoleta(
                            $clientData,
                            $items,
                            ['origin_type' => 'order', 'origin_id' => $order->id]
                        );
                    }

                    Log::info('🧾 Comprobante emitido para pedido', [
                        'order' => $order->order_number,
                        'tipo' => $order->document_type,
                        'resultado' => $billingResult['success'] ?? false,
                        'numero' => $billingResult['numero'] ?? null,
                    ]);

                    // Guardar estado de facturación en el pedido
                    $order->update([
                        'billing_status' => ($billingResult['success'] ?? false) ? 'emitida' : 'error',
                        'billing_number' => $billingResult['numero'] ?? null,
                        'billing_error' => ($billingResult['success'] ?? false) ? null : ($billingResult['error'] ?? 'Error desconocido'),
                        'billing_document_id' => $billingResult['document']->id ?? null,
                    ]);
                } else {
                    $order->update(['billing_status' => 'no_configurado', 'billing_error' => 'Facturación no configurada']);
                }
            } catch (\Exception $e) {
                Log::error('❌ Error al emitir comprobante para pedido: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'document_type' => $order->document_type,
                ]);
                $billingResult = ['success' => false, 'error' => $e->getMessage()];
                $order->update([
                    'billing_status' => 'error',
                    'billing_error' => $e->getMessage(),
                ]);
            }
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Pedido actualizado exitosamente',
            'order' => $order,
            'billing' => $billingResult,
        ]);
    }

    /**
     * Retry billing for an order (admin only)
     */
    public function retryBilling($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
        }

        if (!in_array($order->document_type, ['boleta', 'factura'])) {
            return response()->json(['success' => false, 'message' => 'El pedido no tiene tipo de documento asignado'], 400);
        }

        $billingResult = null;
        try {
            $factuFlash = FactuFlashService::make();
            if (!$factuFlash) {
                $order->update(['billing_status' => 'no_configurado', 'billing_error' => 'Facturación no configurada']);
                return response()->json(['success' => false, 'message' => 'Facturación no configurada'], 400);
            }

            $rawDoc = $order->customer_document ?? '';
            $cleanDoc = trim(preg_replace('/^(DNI|RUC|CE)\s*:\s*/i', '', $rawDoc));

            $items = collect($order->items)->map(function ($item) {
                return [
                    'cod_producto' => (string) ($item['id'] ?? $item['product_id'] ?? 'PROD'),
                    'descripcion' => $item['name'] ?? $item['product_name'] ?? 'Producto',
                    'cantidad' => $item['quantity'] ?? 1,
                    'precio_unitario' => $item['price'] ?? $item['unit_price'] ?? 0,
                    'unidad' => 'NIU',
                ];
            })->toArray();

            if ($order->document_type === 'factura') {
                $billingResult = $factuFlash->emitirFactura(
                    ['ruc' => $cleanDoc, 'razon_social' => $order->customer_name ?? ''],
                    $items,
                    ['origin_type' => 'order', 'origin_id' => $order->id]
                );
            } else {
                $clientData = ['nombre' => $order->customer_name ?? 'CLIENTE'];
                if (!empty($cleanDoc) && strlen($cleanDoc) === 8) {
                    $clientData['dni'] = $cleanDoc;
                } else {
                    $clientData['num_doc'] = '-';
                }
                $billingResult = $factuFlash->emitirBoleta(
                    $clientData,
                    $items,
                    ['origin_type' => 'order', 'origin_id' => $order->id]
                );
            }

            $order->update([
                'billing_status' => ($billingResult['success'] ?? false) ? 'emitida' : 'error',
                'billing_number' => $billingResult['numero'] ?? null,
                'billing_error' => ($billingResult['success'] ?? false) ? null : ($billingResult['error'] ?? 'Error desconocido'),
                'billing_document_id' => $billingResult['document']->id ?? null,
            ]);

            Log::info('🧾 Reintento de comprobante', [
                'order' => $order->order_number,
                'resultado' => $billingResult['success'] ?? false,
            ]);

            $order->refresh();

            return response()->json([
                'success' => $billingResult['success'] ?? false,
                'message' => ($billingResult['success'] ?? false) ? 'Comprobante emitido exitosamente' : 'Error al emitir comprobante',
                'order' => $order,
                'billing' => $billingResult,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al reintentar comprobante: ' . $e->getMessage());
            $order->update(['billing_status' => 'error', 'billing_error' => $e->getMessage()]);
            $order->refresh();
            return response()->json([
                'success' => false,
                'message' => 'Error al emitir: ' . $e->getMessage(),
                'order' => $order,
            ], 500);
        }
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
     * Get billing document data for an order (for receipt/download)
     */
    public function getBillingDocument($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
        }

        if ($order->billing_status !== 'emitida' || !$order->billing_document_id) {
            return response()->json(['success' => false, 'message' => 'Este pedido no tiene comprobante emitido'], 400);
        }

        $billingDoc = BillingDocument::find($order->billing_document_id);
        if (!$billingDoc) {
            return response()->json(['success' => false, 'message' => 'Documento de facturación no encontrado'], 404);
        }

        $config = BillingConfig::getActive();

        return response()->json([
            'success' => true,
            'document' => $billingDoc,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_document' => $order->customer_document,
                'total' => $order->total,
                'items' => $order->items,
                'created_at' => $order->created_at,
            ],
            'business' => $config ? [
                'ruc' => $config->ruc,
                'razon_social' => $config->razon_social,
                'nombre_comercial' => $config->nombre_comercial,
                'direccion' => $config->direccion,
            ] : null,
        ]);
    }

    /**
     * Emit credit note for an order with emitted billing
     */
    public function emitCreditNote(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
        }

        if ($order->billing_status !== 'emitida' || !$order->billing_number) {
            return response()->json(['success' => false, 'message' => 'Este pedido no tiene comprobante emitido'], 400);
        }

        if ($order->credit_note_status === 'emitida') {
            return response()->json(['success' => false, 'message' => 'Ya existe una nota de crédito emitida para este pedido'], 400);
        }

        $motivo = $request->input('motivo', 'Anulación de la operación');
        $codMotivo = $request->input('cod_motivo', '01'); // 01 = Anulación

        try {
            $factuFlash = FactuFlashService::make();
            if (!$factuFlash) {
                return response()->json(['success' => false, 'message' => 'Facturación no configurada'], 400);
            }

            // Determine tipo_doc_afectado from document_type
            $tipoDocAfectado = $order->document_type === 'factura' ? '01' : '03';

            // Build client data
            $rawDoc = $order->customer_document ?? '';
            $cleanDoc = trim(preg_replace('/^(DNI|RUC|CE)\s*:\s*/i', '', $rawDoc));

            $clientData = [
                'num_doc' => !empty($cleanDoc) ? $cleanDoc : '-',
                'razon_social' => $order->customer_name ?? 'CLIENTE',
            ];

            if ($order->document_type === 'factura') {
                $clientData['ruc'] = $cleanDoc;
            } else {
                $clientData['dni'] = $cleanDoc;
                $clientData['nombre'] = $order->customer_name ?? 'CLIENTE';
            }

            // Build items from order
            $items = collect($order->items)->map(function ($item) {
                return [
                    'cod_producto' => $item['product_id'] ?? $item['id'] ?? 'PROD',
                    'descripcion' => $item['name'] ?? $item['product_name'] ?? 'Producto',
                    'cantidad' => $item['quantity'] ?? 1,
                    'precio_unitario' => $item['price'] ?? $item['unit_price'] ?? 0,
                    'unidad' => 'NIU',
                ];
            })->toArray();

            $result = $factuFlash->emitirNotaCredito(
                $tipoDocAfectado,
                $order->billing_number,
                $codMotivo,
                $motivo,
                $clientData,
                $items,
                ['origin_type' => 'order', 'origin_id' => $order->id]
            );

            $order->update([
                'credit_note_number' => $result['numero'] ?? null,
                'credit_note_status' => ($result['success'] ?? false) ? 'emitida' : 'error',
                'credit_note_document_id' => $result['document']->id ?? null,
            ]);

            Log::info('📝 Nota de crédito emitida', [
                'order' => $order->order_number,
                'billing_number' => $order->billing_number,
                'credit_note' => $result['numero'] ?? null,
                'success' => $result['success'] ?? false,
            ]);

            $order->refresh();

            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => ($result['success'] ?? false)
                    ? 'Nota de crédito emitida exitosamente: ' . ($result['numero'] ?? '')
                    : 'Error al emitir nota de crédito: ' . ($result['error'] ?? 'Error desconocido'),
                'order' => $order,
                'credit_note' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al emitir nota de crédito: ' . $e->getMessage());
            $order->update(['credit_note_status' => 'error']);
            $order->refresh();
            return response()->json([
                'success' => false,
                'message' => 'Error al emitir nota de crédito: ' . $e->getMessage(),
                'order' => $order,
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
