<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('date_from')) {
            $query->where('purchase_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('purchase_date', '<=', $request->date_to);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(50);

        return response()->json($purchases);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'purchase_date' => 'required|date'
        ]);

        $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

        $purchase = Purchase::create($validated);
        $purchase->load('product');

        return response()->json([
            'message' => 'Compra registrada exitosamente',
            'purchase' => $purchase
        ], 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load('product');
        return response()->json($purchase);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'purchase_date' => 'sometimes|date'
        ]);

        if (isset($validated['quantity']) || isset($validated['unit_cost'])) {
            $quantity = $validated['quantity'] ?? $purchase->quantity;
            $unitCost = $validated['unit_cost'] ?? $purchase->unit_cost;
            $validated['total_cost'] = $quantity * $unitCost;
        }

        $purchase->update($validated);
        $purchase->load('product');

        return response()->json([
            'message' => 'Compra actualizada exitosamente',
            'purchase' => $purchase
        ]);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->product->decrement('stock', $purchase->quantity);
        
        $purchase->delete();

        return response()->json([
            'message' => 'Compra eliminada exitosamente'
        ]);
    }
}
