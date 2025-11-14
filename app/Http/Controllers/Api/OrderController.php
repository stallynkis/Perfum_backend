<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
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
        $query = Order::with('user')->orderBy('created_at', 'desc');

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
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_document' => 'nullable|string|max:50',
            'delivery_type' => 'required|in:home,agency',
            'shipping_address' => 'required_if:delivery_type,home|nullable|string',
            'shipping_district' => 'nullable|string|max:100',
            'shipping_reference' => 'nullable|string',
            'agency_type' => 'required_if:delivery_type,agency|nullable|in:olva,shalom',
            'agency_id' => 'nullable|string|max:100',
            'agency_name' => 'required_if:delivery_type,agency|nullable|string|max:255',
            'agency_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:paypal,yape,cash,card,transfer',
            'transaction_id' => 'nullable|string|max:255',
            'approval_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
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

                // Reduce stock
                $product->decrement('stock', $item['quantity']);

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
            
            // Ventas de vendedores (cash, card) se marcan como pagadas automáticamente
            $paymentStatus = in_array($request->payment_method, ['paypal', 'cash', 'card', 'transfer']) 
                ? 'paid' 
                : 'pending';

            // Determinar la fuente de la orden (web o seller)
            // Si el payment_method es cash, card o transfer, es venta de vendedor
            $source = in_array($request->payment_method, ['cash', 'card', 'transfer']) ? 'seller' : 'web';

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
                'status' => 'pending',
                'notes' => $request->notes,
                'requires_admin_confirmation' => $requiresConfirmation
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
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
}
