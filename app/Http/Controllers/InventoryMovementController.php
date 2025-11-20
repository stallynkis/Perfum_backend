<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    /**
     * Listar todos los movimientos de inventario
     */
    public function index(Request $request)
    {
        $query = InventoryMovement::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

        // Filtrar por producto si se proporciona
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filtrar por tipo si se proporciona
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrar por rango de fechas
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->paginate($request->get('per_page', 50));

        return response()->json($movements);
    }

    /**
     * Crear un nuevo movimiento de inventario
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:ingreso,retiro',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Obtener el producto
            $product = Product::findOrFail($validated['product_id']);
            $previousStock = $product->stock;

            // Calcular nuevo stock
            $newStock = $validated['type'] === 'ingreso' 
                ? $previousStock + $validated['quantity']
                : $previousStock - $validated['quantity'];

            // Validar que no quede stock negativo
            if ($newStock < 0) {
                return response()->json([
                    'message' => 'Stock insuficiente. Stock actual: ' . $previousStock
                ], 422);
            }

            // Crear el movimiento
            $movement = InventoryMovement::create([
                'product_id' => $validated['product_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
            ]);

            // Actualizar stock del producto
            $product->update(['stock' => $newStock]);

            DB::commit();

            return response()->json([
                'message' => 'Movimiento registrado exitosamente',
                'movement' => $movement->load(['product', 'user']),
                'new_stock' => $newStock,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un movimiento específico
     */
    public function show($id)
    {
        $movement = InventoryMovement::with(['product', 'user'])->findOrFail($id);
        return response()->json($movement);
    }

    /**
     * Estadísticas de movimientos
     */
    public function stats(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $ingresos = InventoryMovement::where('type', 'ingreso')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        $retiros = InventoryMovement::where('type', 'retiro')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        $totalMovements = InventoryMovement::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $recentMovements = InventoryMovement::with(['product', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_ingresos' => $ingresos,
            'total_retiros' => $retiros,
            'net_movement' => $ingresos - $retiros,
            'total_movements' => $totalMovements,
            'recent_movements' => $recentMovements,
        ]);
    }
}
