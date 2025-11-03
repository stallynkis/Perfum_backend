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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

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
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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

    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario por email o nombre con rol admin
        $user = User::where(function($query) use ($request) {
                $query->where('email', $request->username)
                      ->orWhere('name', $request->username);
            })
            ->where('role', 'admin')
            ->first();

        // Verificar credenciales
        if ($user) {
            // Si la contraseña está hasheada, usar Hash::check
            $passwordMatches = Hash::check($request->password, $user->password) || 
                             $user->password === $request->password;
            
            if ($passwordMatches) {
                $token = $user->createToken('admin_token')->plainTextToken;
                
                return response()->json([
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role
                    ],
                    'token' => $token
                ], 200);
            }
        }

        // Credenciales de respaldo hardcodeadas
        if ($request->username === 'admin' && $request->password === 'admin123') {
            return response()->json([
                'message' => 'Login exitoso',
                'user' => [
                    'id' => 1,
                    'username' => 'admin',
                    'name' => 'Administrador',
                    'role' => 'admin'
                ],
                'token' => 'admin-token-' . time()
            ], 200);
        }

        return response()->json([
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    public function adminLogout(Request $request)
    {
        return response()->json([
            'message' => 'Logout exitoso'
        ], 200);
    }
}
