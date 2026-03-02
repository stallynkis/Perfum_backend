<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSellerRole
{
    /**
     * Verifica que el usuario autenticado tenga rol de vendedor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        if (!in_array($user->role, ['vendedor', 'seller'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: se requiere rol de vendedor'
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta de vendedor desactivada'
            ], 403);
        }

        return $next($request);
    }
}
