<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialTransactionController extends Controller
{
    // Obtener todas las transacciones con filtros
    public function index(Request $request)
    {
        \Log::info('📊 Obteniendo transacciones', [
            'filters' => $request->all()
        ]);

        $query = FinancialTransaction::with('user');

        // Filtrar por tipo
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrar por categoría (negocio/personal)
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filtrar por rango de fechas
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->get();

        \Log::info('✅ Transacciones encontradas', [
            'count' => $transactions->count(),
            'total_in_db' => FinancialTransaction::count()
        ]);

        return response()->json(['data' => $transactions]);
    }

    // Crear nueva transacción
    public function store(Request $request)
    {
        try {
            \Log::info('💰 Creando transacción financiera', $request->all());

            $validated = $request->validate([
                'type' => 'required|in:income,withdrawal,expense',
                'category' => 'required|in:negocio,personal',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'cash_register_id' => 'nullable|exists:cash_registers,id',
                'transaction_date' => 'required|date'
            ]);

            // Agregar user_id del usuario autenticado (nullable)
            if (auth()->check()) {
                $validated['user_id'] = auth()->id();
            }

            $transaction = FinancialTransaction::create($validated);

            \Log::info('✅ Transacción creada', ['id' => $transaction->id]);

            return response()->json([
                'transaction' => $transaction->load('user'),
                'message' => 'Transacción registrada exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Error de validación', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('❌ Error creando transacción', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al crear transacción: ' . $e->getMessage()
            ], 500);
        }
    }

    // Obtener resumen/estadísticas
    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $category = $request->get('category'); // 'negocio', 'personal', o null para ambos

        \Log::info('📊 Calculando resumen financiero', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $category
        ]);

        $baseQuery = FinancialTransaction::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);
        
        if ($category) {
            $baseQuery->where('category', $category);
        }

        // Totales por tipo
        $summary = (clone $baseQuery)->select(
            'type',
            'category',
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('type', 'category')
        ->get();

        // Calcular totales generales
        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount') ?? 0;
        $totalWithdrawals = (clone $baseQuery)->where('type', 'withdrawal')->sum('amount') ?? 0;
        $totalExpenses = (clone $baseQuery)->where('type', 'expense')->sum('amount') ?? 0;
        
        $balance = $totalIncome - ($totalWithdrawals + $totalExpenses);

        // Totales por categoría
        $negocioQuery = FinancialTransaction::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->where('category', 'negocio');
        
        $negocioIncome = (clone $negocioQuery)->where('type', 'income')->sum('amount') ?? 0;
        $negocioExpenses = (clone $negocioQuery)->whereIn('type', ['withdrawal', 'expense'])->sum('amount') ?? 0;
        $negocioBalance = $negocioIncome - $negocioExpenses;

        $personalQuery = FinancialTransaction::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->where('category', 'personal');
        
        $personalIncome = (clone $personalQuery)->where('type', 'income')->sum('amount') ?? 0;
        $personalExpenses = (clone $personalQuery)->whereIn('type', ['withdrawal', 'expense'])->sum('amount') ?? 0;
        $personalBalance = $personalIncome - $personalExpenses;

        \Log::info('✅ Resumen calculado', [
            'total_income' => $totalIncome,
            'negocio_income' => $negocioIncome,
            'personal_income' => $personalIncome
        ]);

        return response()->json([
            'summary' => $summary,
            'totals' => [
                'income' => $totalIncome,
                'withdrawals' => $totalWithdrawals,
                'expenses' => $totalExpenses,
                'balance' => $balance
            ],
            'by_category' => [
                'negocio' => [
                    'income' => $negocioIncome,
                    'expenses' => $negocioExpenses,
                    'balance' => $negocioBalance
                ],
                'personal' => [
                    'income' => $personalIncome,
                    'expenses' => $personalExpenses,
                    'balance' => $personalBalance
                ]
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    // Actualizar transacción
    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        try {
            $validated = $request->validate([
                'type' => 'sometimes|required|in:income,withdrawal,expense',
                'category' => 'sometimes|required|in:negocio,personal',
                'amount' => 'sometimes|required|numeric|min:0',
                'description' => 'nullable|string',
                'cash_register_id' => 'nullable|exists:cash_registers,id',
                'transaction_date' => 'sometimes|required|date'
            ]);

            $financialTransaction->update($validated);

            return response()->json([
                'transaction' => $financialTransaction->load('user'),
                'message' => 'Transacción actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar transacción: ' . $e->getMessage()
            ], 500);
        }
    }

    // Eliminar transacción
    public function destroy(FinancialTransaction $financialTransaction)
    {
        try {
            $financialTransaction->delete();
            return response()->json([
                'message' => 'Transacción eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar transacción: ' . $e->getMessage()
            ], 500);
        }
    }
}
