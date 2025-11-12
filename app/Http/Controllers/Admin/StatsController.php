<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Display admin dashboard statistics.
     */
    public function index()
    {
        // Usuarios
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Productos
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('stock', '<', 10)->count();

        // Órdenes (si existe la tabla)
        $totalOrders = 0;
        $pendingOrders = 0;
        $completedOrders = 0;
        $totalRevenue = 0;
        
        try {
            $totalOrders = Order::count();
            $pendingOrders = Order::where('status', 'pending')->count();
            $completedOrders = Order::where('status', 'completed')->count();
            $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        } catch (\Exception $e) {
            // Si no existe la tabla orders, ignorar
        }

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'newThisMonth' => $newUsersThisMonth,
            ],
            'products' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'lowStock' => $lowStockProducts,
            ],
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'completed' => $completedOrders,
            ],
            'revenue' => [
                'total' => $totalRevenue,
            ],
        ]);
    }
}
