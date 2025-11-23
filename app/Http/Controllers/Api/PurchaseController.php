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
        $purchases = Purchase::with(['product', 'businessPartner'])
            ->orderBy('purchase_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por invoice_number para combinar items
        $grouped = $purchases->groupBy('invoice_number')->map(function ($items) {
            $first = $items->first();
            return [
                'id' => $first->id,
                'business_partner_id' => $first->business_partner_id,
                'business_partner' => $first->businessPartner,
                'document_type' => $first->document_type,
                'invoice_number' => $first->invoice_number,
                'purchase_date' => $first->purchase_date,
                'fecha_emision' => $first->purchase_date,
                'serie' => explode('-', $first->invoice_number)[0] ?? '',
                'numero' => explode('-', $first->invoice_number)[1] ?? '',
                'notes' => $first->notes,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_cost,
                        'subtotal' => $item->total_cost,
                    ];
                })->values(),
                'subtotal' => $items->sum('total_cost'),
                'igv' => $items->sum('total_cost') * 0.18,
                'total' => $items->sum('total_cost') * 1.18,
                'created_at' => $first->created_at,
            ];
        })->values();

        return response()->json([
            'data' => $grouped
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_partner_id' => 'required|exists:business_partners,id',
            'document_type' => 'required|string|in:factura,boleta,nota_credito,nota_debito',
            'serie' => 'required|string|max:10',
            'numero' => 'required|string|max:20',
            'fecha_emision' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'igv' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0'
        ]);

        $invoiceNumber = $validated['serie'] . '-' . $validated['numero'];

        $purchases = [];
        foreach ($validated['items'] as $item) {
            $purchase = Purchase::create([
                'business_partner_id' => $validated['business_partner_id'],
                'document_type' => $validated['document_type'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_price'],
                'total_cost' => $item['subtotal'],
                'invoice_number' => $invoiceNumber,
                'purchase_date' => $validated['fecha_emision'],
                'notes' => $validated['notes'] ?? null
            ]);

            $product = Product::find($item['product_id']);
            if ($product) {
                $product->increment('stock', $item['quantity']);
            }

            $purchases[] = $purchase->load('product', 'businessPartner');
        }

        return response()->json([
            'message' => 'Compra registrada exitosamente',
            'data' => [
                'invoice_number' => $invoiceNumber,
                'items' => $purchases,
                'subtotal' => $validated['subtotal'],
                'igv' => $validated['igv'],
                'total' => $validated['total']
            ]
        ], 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['product', 'businessPartner']);
        return response()->json($purchase);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
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
        $purchase->load(['product', 'businessPartner']);

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
