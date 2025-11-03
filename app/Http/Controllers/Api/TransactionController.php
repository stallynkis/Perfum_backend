<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->paginate(50);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date'
        ]);

        $transaction = Transaction::create($validated);

        return response()->json([
            'message' => 'Transacción creada exitosamente',
            'transaction' => $transaction
        ], 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:income,expense',
            'category' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'sometimes|date'
        ]);

        $transaction->update($validated);

        return response()->json([
            'message' => 'Transacción actualizada exitosamente',
            'transaction' => $transaction
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'message' => 'Transacción eliminada exitosamente'
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $income = Transaction::where('type', 'income')
                            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                            ->sum('amount');

        $expenses = Transaction::where('type', 'expense')
                              ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                              ->sum('amount');

        $profit = $income - $expenses;

        $incomeByCategory = Transaction::where('type', 'income')
                                     ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                                     ->selectRaw('category, SUM(amount) as total')
                                     ->groupBy('category')
                                     ->get();

        $expensesByCategory = Transaction::where('type', 'expense')
                                        ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                                        ->selectRaw('category, SUM(amount) as total')
                                        ->groupBy('category')
                                        ->get();

        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'summary' => [
                'total_income' => $income,
                'total_expenses' => $expenses,
                'profit' => $profit
            ],
            'income_by_category' => $incomeByCategory,
            'expenses_by_category' => $expensesByCategory
        ]);
    }
}
