<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::select('id', 'name', 'email', 'password', 'role', 'phone', 'address')
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Eliminar tokens antiguos para este usuario
        $user->tokens()->delete();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'document_type' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'role' => 'customer', // Por defecto los registros públicos son clientes
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'document_type' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
        ]);

        // Actualizar solo los campos que fueron enviados
        $user->update($request->only([
            'name',
            'email', 
            'phone',
            'address',
            'city',
            'document_type',
            'document_number',
            'birth_date'
        ]));

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->fresh()
        ]);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario por email o nombre con rol admin
        $user = User::select('id', 'name', 'email', 'password', 'role')
            ->where(function($query) use ($request) {
                $query->where('email', $request->username)
                      ->orWhere('name', $request->username);
            })
            ->where('role', 'admin')
            ->first();

        // Verificar credenciales
        if ($user && Hash::check($request->password, $user->password)) {
            // Eliminar tokens antiguos
            $user->tokens()->delete();
            
            $token = $user->createToken('admin_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'name' => $user->name,
                    'role' => $user->role
                ],
                'token' => $token
            ], 200);
        }

        return response()->json([
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    public function adminLogout(Request $request)
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logout exitoso'
        ], 200);
    }

    public function adminMe(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'username' => $request->user()->name,
                'name' => $request->user()->name,
                'role' => $request->user()->role
            ]
        ], 200);
    }

    public function sellerLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario por email o nombre con rol vendedor
        $user = User::where(function($query) use ($request) {
                $query->where('email', $request->username)
                      ->orWhere('name', $request->username);
            })
            ->where('role', 'vendedor')
            ->where('is_active', true)
            ->first();

        // Verificar credenciales
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('seller_token')->plainTextToken;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    public function sellerLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logout exitoso'
        ], 200);
    }
}
