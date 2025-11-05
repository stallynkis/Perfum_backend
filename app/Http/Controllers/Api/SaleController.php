<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['product', 'user', 'seller']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $sales = $query->orderBy('sale_date', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate(50);

        return response()->json($sales);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'register_id' => 'nullable|integer',
            'cash_session_id' => 'nullable|integer',
            'type' => 'nullable|string|in:sale,expense,income,withdrawal',
            'product_id' => 'required|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'seller_id' => 'nullable|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,yape,plin,transfer,mixed',
            'document_type' => 'nullable|string|in:ticket,boleta,factura',
            'customer_name' => 'nullable|string|max:255',
            'customer_document' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'sometimes|in:completed,pending,cancelled',
            'notes' => 'nullable|string',
            'sale_date' => 'required|date'
        ]);

        $product = Product::findOrFail($validated['product_id']);
        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Stock insuficiente',
                'available_stock' => $product->stock,
                'requested_quantity' => $validated['quantity']
            ], 400);
        }

        // Si customer_document es '1' o vacío, se considera "Clientes Varios"
        if (empty($validated['customer_document']) || $validated['customer_document'] === '1') {
            $validated['customer_name'] = 'Clientes Varios';
            $validated['customer_document'] = null;
        }
        
        // Establecer valores por defecto
        $validated['type'] = $validated['type'] ?? 'sale';
        $validated['total_amount'] = $validated['quantity'] * $validated['unit_price'];
        $validated['status'] = $validated['status'] ?? 'completed';

        $sale = Sale::create($validated);
        $sale->load(['product', 'user', 'seller']);

        return response()->json([
            'message' => 'Venta registrada exitosamente',
            'sale' => $sale
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['product', 'user', 'seller']);
        return response()->json($sale);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'status' => 'sometimes|in:completed,pending,cancelled',
            'notes' => 'nullable|string',
            'sale_date' => 'sometimes|date'
        ]);

        if (isset($validated['quantity']) || isset($validated['unit_price'])) {
            $quantity = $validated['quantity'] ?? $sale->quantity;
            $unitPrice = $validated['unit_price'] ?? $sale->unit_price;
            $validated['total_amount'] = $quantity * $unitPrice;
        }

        $sale->update($validated);
        $sale->load(['product', 'user', 'seller']);

        return response()->json([
            'message' => 'Venta actualizada exitosamente',
            'sale' => $sale
        ]);
    }

    public function destroy(Sale $sale): JsonResponse
    {
        if ($sale->status === 'completed') {
            $sale->product->increment('stock', $sale->quantity);
        }
        
        $sale->delete();

        return response()->json([
            'message' => 'Venta eliminada exitosamente'
        ]);
    }
}
