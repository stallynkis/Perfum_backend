<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Obtener todos los vendedores
        $sellers = User::where('role', 'vendedor')->get();

        $sellerStats = [];

        foreach ($sellers as $seller) {
            $orders = Order::where('source', 'seller')
                ->where('user_id', $seller->id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();

            $sellerStats[] = [
                'seller_id' => $seller->id,
                'seller_name' => $seller->name,
                'seller_email' => $seller->email,
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total'),
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'total' => $order->total,
                        'payment_method' => $order->payment_method,
                        'status' => $order->status,
                        'created_at' => $order->created_at->toISOString(),
                    ];
                })
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $sellerStats,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function sellerDetail($sellerId, Request $request)
    {
        $seller = User::findOrFail($sellerId);

        if ($seller->role !== 'vendedor') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no es un vendedor'
            ], 400);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $orders = Order::where('source', 'seller')
            ->where('user_id', $sellerId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email
            ],
            'stats' => [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total'),
                'average_ticket' => $orders->count() > 0 ? $orders->sum('total') / $orders->count() : 0,
            ],
            'orders' => $orders,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }
}
