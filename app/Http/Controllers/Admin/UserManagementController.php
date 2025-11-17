<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:admin,client,vendedor',
            'is_active' => 'nullable|boolean',
            'documentType' => 'nullable|string|in:dni,ce,passport',
            'documentNumber' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $validated['role'] ?? 'client';
        $validated['is_active'] = $validated['is_active'] ?? true;
        
        // Mapear camelCase a snake_case para la base de datos
        if (isset($validated['documentType'])) {
            $validated['document_type'] = $validated['documentType'];
            unset($validated['documentType']);
        }
        if (isset($validated['documentNumber'])) {
            $validated['document_number'] = $validated['documentNumber'];
            unset($validated['documentNumber']);
        }

        $user = User::create($validated);

        return response()->json([
            'user' => $user,
            'message' => 'Usuario creado exitosamente'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json(['data' => $user]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|nullable|string|in:admin,client,vendedor',
            'is_active' => 'sometimes|nullable|boolean',
            'documentType' => 'sometimes|nullable|string|in:dni,ce,passport',
            'documentNumber' => 'sometimes|nullable|string|max:20',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Mapear camelCase a snake_case para la base de datos
        if (isset($validated['documentType'])) {
            $validated['document_type'] = $validated['documentType'];
            unset($validated['documentType']);
        }
        if (isset($validated['documentNumber'])) {
            $validated['document_number'] = $validated['documentNumber'];
            unset($validated['documentNumber']);
        }

        $user->update($validated);

        return response()->json([
            'user' => $user,
            'message' => 'Usuario actualizado exitosamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }
}
