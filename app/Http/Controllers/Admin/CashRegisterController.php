<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\CashMovement;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    // ========== CASH REGISTERS ==========
    
    public function index()
    {
        $registers = CashRegister::with('responsibleUser')->get();
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
        $session = CashSession::findOrFail($sessionId);

        if ($session->status === 'closed') {
            return response()->json([
                'message' => 'La sesión ya está cerrada'
            ], 400);
        }

        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $session->update([
            'closing_date' => now(),
            'closing_amount' => $validated['closing_amount'],
            'difference' => $validated['closing_amount'] - $session->expected_amount,
            'status' => 'closed',
            'notes' => ($session->notes ?? '') . "\n" . ($validated['notes'] ?? '')
        ]);

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
            'session' => $session->load(['cashRegister', 'user'])
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
            'payment_method' => 'nullable|in:cash,card,yape,transfer',
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
