<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerCustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener clientes del vendedor autenticado
        $customers = SellerCustomer::where('seller_id', $user->id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $customer = SellerCustomer::create([
            'seller_id' => $user->id,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente creado exitosamente'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $customer = SellerCustomer::where('seller_id', $user->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'document' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente actualizado exitosamente'
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $customer = SellerCustomer::where('seller_id', $user->id)
            ->findOrFail($id);

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }
}
