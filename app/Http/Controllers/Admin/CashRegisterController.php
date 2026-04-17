<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\CashMovement;
use App\Models\FinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    private function resolveCollectionRegister(?int $sourceRegisterId = null): CashRegister
    {
        // Si la caja origen ya es recaudadora, úsala directamente.
        if ($sourceRegisterId) {
            $source = CashRegister::find($sourceRegisterId);
            if ($source && $source->is_collection_box) {
                return $source;
            }
        }

        $collection = CashRegister::where('is_collection_box', true)->first();
        if ($collection) {
            return $collection;
        }

        // Si no existe caja recaudadora, tomar una activa y marcarla automáticamente.
        $candidate = CashRegister::where('is_active', true)->orderBy('id')->first();
        if ($candidate) {
            CashRegister::where('id', '!=', $candidate->id)->update(['is_collection_box' => false]);
            $candidate->update(['is_collection_box' => true]);
            return $candidate->fresh();
        }

        // Último fallback: crear caja recaudadora por defecto.
        return CashRegister::create([
            'name' => 'CAJA RECAUDADORA',
            'code' => 'REC-' . now()->format('YmdHis'),
            'responsible_user_id' => null,
            'is_active' => true,
            'is_collection_box' => true,
            'current_balance' => 0,
        ]);
    }

    private function ensureOpenSessionForCollection(CashRegister $collectionRegister, int $userId): CashSession
    {
        $session = $collectionRegister->currentSession();
        if ($session) {
            return $session;
        }

        $openingAmount = (float) ($collectionRegister->current_balance ?? 0);

        $session = CashSession::create([
            'cash_register_id' => $collectionRegister->id,
            'user_id' => $userId,
            'opening_date' => now(),
            'opening_amount' => $openingAmount,
            'expected_amount' => $openingAmount,
            'status' => 'open',
            'notes' => 'Sesión automática de caja recaudadora',
        ]);

        if ($openingAmount > 0) {
            CashMovement::create([
                'cash_session_id' => $session->id,
                'type' => 'opening',
                'amount' => $openingAmount,
                'description' => 'Apertura automática de caja recaudadora',
                'user_id' => $userId,
            ]);
        }

        return $session;
    }

    // ========== CASH REGISTERS ==========
    
    public function index()
    {
        $registers = CashRegister::with('responsibleUser')->get();
        
        // Incluir sesión actual y conteo de sesiones para cada caja
        $registers->each(function ($register) {
            $currentSession = $register->currentSession();
            $register->current_session = $currentSession ? $currentSession->load('user') : null;
            $register->sessions_count = $register->sessions()->count();
            $register->closed_sessions_count = $register->sessions()->where('status', 'closed')->count();
            
            // Si hay sesión abierta, contar movimientos de venta
            if ($currentSession) {
                $register->current_sales_count = $currentSession->movements()
                    ->where('type', 'sale')->count();
                $register->current_sales_total = $currentSession->movements()
                    ->where('type', 'sale')->sum('amount');
                $register->current_movements_count = $currentSession->movements()->count();
            } else {
                $register->current_sales_count = 0;
                $register->current_sales_total = 0;
                $register->current_movements_count = 0;
            }
        });
        
        return response()->json($registers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:cash_registers,code',
            'responsible_user_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_collection_box' => 'boolean'
        ]);

        // Generar código automáticamente si no se proporciona
        if (!isset($validated['code'])) {
            $validated['code'] = 'CAJ-' . time();
        }

        $register = CashRegister::create($validated);

        if (!empty($validated['is_collection_box'])) {
            CashRegister::where('id', '!=', $register->id)->update(['is_collection_box' => false]);
        }
        
        return response()->json([
            'message' => 'Caja registrada exitosamente',
            'register' => $register->load('responsibleUser')
        ], 201);
    }

    public function show($id)
    {
        $register = CashRegister::with('responsibleUser', 'sessions')->findOrFail($id);
        return response()->json($register);
    }

    public function update(Request $request, $id)
    {
        $register = CashRegister::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:cash_registers,code,' . $id,
            'responsible_user_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_collection_box' => 'boolean',
            'current_balance' => 'sometimes|numeric'
        ]);

        $register->update($validated);

        if (!empty($validated['is_collection_box'])) {
            CashRegister::where('id', '!=', $register->id)->update(['is_collection_box' => false]);
            $register->refresh();
        }
        
        return response()->json([
            'message' => 'Caja actualizada exitosamente',
            'register' => $register->load('responsibleUser')
        ]);
    }

    public function destroy($id)
    {
        $register = CashRegister::findOrFail($id);
        
        // Verificar que no tenga sesiones abiertas
        if ($register->currentSession()) {
            return response()->json([
                'message' => 'No se puede eliminar una caja con sesión abierta'
            ], 400);
        }

        $register->delete();
        
        return response()->json([
            'message' => 'Caja eliminada exitosamente'
        ]);
    }

    // ========== CASH SESSIONS ==========

    public function getSessions($registerId)
    {
        $sessions = CashSession::where('cash_register_id', $registerId)
            ->with('user')
            ->orderBy('opening_date', 'desc')
            ->get();
        
        return response()->json($sessions);
    }

    public function openSession(Request $request)
    {
        $validated = $request->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $register = CashRegister::findOrFail($validated['cash_register_id']);

        // Verificar que no haya sesión abierta
        if ($register->currentSession()) {
            return response()->json([
                'message' => 'Ya existe una sesión abierta para esta caja'
            ], 400);
        }

        $session = CashSession::create([
            'cash_register_id' => $validated['cash_register_id'],
            'user_id' => $request->user()->id,
            'opening_date' => now(),
            'opening_amount' => $validated['opening_amount'],
            'expected_amount' => $validated['opening_amount'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? ''
        ]);

        // Registrar movimiento de apertura
        CashMovement::create([
            'cash_session_id' => $session->id,
            'type' => 'opening',
            'amount' => $validated['opening_amount'],
            'description' => 'Apertura de caja',
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'message' => 'Sesión abierta exitosamente',
            'session' => $session->load(['cashRegister', 'user'])
        ], 201);
    }

    public function closeSession(Request $request, $sessionId)
    {
        $session = CashSession::with('cashRegister')->findOrFail($sessionId);

        if ($session->status === 'closed') {
            return response()->json([
                'message' => 'La sesión ya está cerrada'
            ], 400);
        }

        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $userId = (int) $request->user()->id;

        $payload = DB::transaction(function () use ($session, $validated, $userId) {
            $closingAmount = (float) $validated['closing_amount'];
            $sourceRegister = $session->cashRegister;
            $sourceIsCollection = (bool) ($sourceRegister?->is_collection_box);

            $session->update([
                'closing_date' => now(),
                'closing_amount' => $closingAmount,
                'difference' => $closingAmount - (float) $session->expected_amount,
                'status' => 'closed',
                'notes' => trim(($session->notes ?? '') . "\n" . ($validated['notes'] ?? '')),
            ]);

            // Mantener balance de la caja fuente según el cierre.
            if ($sourceRegister) {
                $sourceRegister->update([
                    'current_balance' => $closingAmount,
                ]);
            }

            $transfer = null;

            if ($closingAmount > 0 && !$sourceIsCollection) {
                $collectionRegister = $this->resolveCollectionRegister();
                $collectionSession = $this->ensureOpenSessionForCollection($collectionRegister, $userId);

                // Registrar ingreso en movimientos de caja recaudadora.
                CashMovement::create([
                    'cash_session_id' => $collectionSession->id,
                    'type' => 'income',
                    'amount' => $closingAmount,
                    'description' => 'Transferencia automática por cierre de caja ' . ($sourceRegister?->name ?? ('#' . $session->cash_register_id)),
                    'reference_id' => $session->id,
                    'reference_type' => 'cash_closure_transfer',
                    'user_id' => $userId,
                    'payment_method' => 'cash',
                ]);

                $collectionSession->increment('expected_amount', $closingAmount);
                $collectionRegister->increment('current_balance', $closingAmount);

                // Registrar ingreso financiero para panel Negocio.
                FinancialTransaction::create([
                    'type' => 'income',
                    'category' => 'negocio',
                    'amount' => $closingAmount,
                    'description' => 'Ingreso por cierre de caja ' . ($sourceRegister?->name ?? ('#' . $session->cash_register_id)),
                    'cash_register_id' => $collectionRegister->id,
                    'user_id' => $userId,
                    'transaction_date' => now()->toDateString(),
                ]);

                $transfer = [
                    'collection_register_id' => $collectionRegister->id,
                    'collection_register_name' => $collectionRegister->name,
                    'amount' => $closingAmount,
                ];
            } elseif ($closingAmount > 0 && $sourceIsCollection) {
                $transfer = [
                    'collection_register_id' => $sourceRegister->id,
                    'collection_register_name' => $sourceRegister->name,
                    'amount' => $closingAmount,
                    'self_transfer' => true,
                ];
            }

            return [
                'session' => $session->fresh()->load(['cashRegister', 'user']),
                'transfer' => $transfer,
            ];
        });

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
            'session' => $payload['session'],
            'transfer' => $payload['transfer'],
        ]);
    }

    public function getCurrentSession($registerId)
    {
        $register = CashRegister::findOrFail($registerId);
        $session = $register->currentSession();

        if (!$session) {
            return response()->json(['session' => null]);
        }

        return response()->json($session->load(['cashRegister', 'user']));
    }

    // ========== CASH MOVEMENTS ==========

    public function addMovement(Request $request)
    {
        $validated = $request->validate([
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'type' => 'required|in:sale,purchase,income,expense,opening,deposit,withdrawal',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string',
            'seller_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string',
            'customer_document' => 'nullable|string',
            'payment_method' => 'nullable|in:cash,card,yape,plin,transfer,mixed',
            'payment_breakdown' => 'nullable|array',
            'payment_reference' => 'nullable|string|max:255',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'document_type' => 'nullable|in:ticket,boleta,factura'
        ]);

        $validated['user_id'] = $request->user()->id;

        $movement = CashMovement::create($validated);

        // Actualizar monto esperado de la sesión
        $session = CashSession::findOrFail($validated['cash_session_id']);
        if (in_array($validated['type'], ['sale', 'income', 'deposit'])) {
            $session->increment('expected_amount', $validated['amount']);
        } elseif (in_array($validated['type'], ['purchase', 'expense', 'withdrawal'])) {
            $session->decrement('expected_amount', $validated['amount']);
        }

        return response()->json([
            'message' => 'Movimiento registrado exitosamente',
            'movement' => $movement->load('user')
        ], 201);
    }

    public function getMovements($sessionId)
    {
        $movements = CashMovement::where('cash_session_id', $sessionId)
            ->with('user', 'seller')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($movements);
    }

    public function getMovementsByDateRange(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $movements = CashMovement::whereBetween('created_at', [
            $validated['start_date'],
            $validated['end_date']
        ])
        ->with('user', 'seller', 'cashSession.cashRegister')
        ->orderBy('created_at', 'desc')
        ->get();
        
        return response()->json($movements);
    }

    // ========== ALL SESSIONS (HISTORY) ==========

    public function getAllSessions(Request $request)
    {
        $query = CashSession::with(['cashRegister', 'user']);
        
        // Filtrar por estado si se proporciona
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrar por caja si se proporciona
        if ($request->has('cash_register_id')) {
            $query->where('cash_register_id', $request->cash_register_id);
        }
        
        $sessions = $query->orderBy('opening_date', 'desc')->get();
        
        // Agregar conteo de movimientos por sesión
        $sessions->each(function ($session) {
            $session->movements_count = $session->movements()->count();
            $session->sales_count = $session->movements()->where('type', 'sale')->count();
            $session->sales_total = $session->movements()->where('type', 'sale')->sum('amount');
        });
        
        return response()->json($sessions);
    }

    // ========== SELLER ROUTES ==========

    public function getSellerRegister(Request $request)
    {
        $userId = $request->user()->id;
        
        $register = CashRegister::where('responsible_user_id', $userId)
            ->where('is_active', true)
            ->with('responsibleUser')
            ->first();

        if (!$register) {
            return response()->json([
                'register' => null,
                'message' => 'No tienes una caja asignada'
            ]);
        }

        $currentSession = $register->currentSession();

        // Recalcular expected_amount desde movimientos reales (corrige datos históricos)
        if ($currentSession) {
            $movSales      = (float) $currentSession->movements()->whereIn('type', ['sale', 'income', 'deposit'])->sum('amount');
            $expensesTotal = (float) $currentSession->movements()->whereIn('type', ['purchase', 'expense', 'withdrawal'])->sum('amount');

            // Fallback: si no hay movimientos en caja (ventas previas al fix), sumar desde la tabla orders
            if ($movSales == 0.0) {
                $orderSales = (float) Order::where('user_id', $userId)
                    ->where('source', 'seller')
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', $currentSession->opening_date)
                    ->sum('total');
                $salesTotal = $orderSales;
            } else {
                $salesTotal = $movSales;
            }

            $realExpected = (float)$currentSession->opening_amount + $salesTotal - $expensesTotal;

            if (abs($realExpected - (float)$currentSession->expected_amount) > 0.001) {
                $currentSession->update(['expected_amount' => $realExpected]);
                $currentSession->refresh();
            }

            // Datos extra para el panel del vendedor
            $salesCount = $currentSession->movements()->where('type', 'sale')->count();
            if ($salesCount == 0) {
                $salesCount = Order::where('user_id', $userId)
                    ->where('source', 'seller')
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', $currentSession->opening_date)
                    ->count();
            }
            $currentSession->sales_count = $salesCount;
            $currentSession->sales_total = $salesTotal;
        }

        return response()->json([
            'register' => $register,
            'current_session' => $currentSession ? $currentSession->load('user') : null
        ]);
    }

    public function getSellerSessions(Request $request)
    {
        $userId = $request->user()->id;
        
        $sessions = CashSession::where('user_id', $userId)
            ->with('cashRegister')
            ->orderBy('opening_date', 'desc')
            ->get();
        
        return response()->json($sessions);
    }
}
