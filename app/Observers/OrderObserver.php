<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Notification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Crear notificación solo si el pago está pendiente (Yape, Transferencia)
        if (in_array($order->payment_method, ['yape', 'plin', 'transfer', 'bank'])) {
            Notification::createOrderNotification($order);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Crear notificación cuando el estado cambie a "completed"
        if ($order->wasChanged('status') && $order->status === 'completed') {
            Notification::create([
                'type' => 'order',
                'title' => 'Pedido completado',
                'message' => "El pedido #{$order->id} ha sido completado exitosamente",
                'priority' => 'low',
                'related_tab' => 'orders',
                'related_id' => $order->id,
                'order_id' => $order->id,
                'data' => [
                    'order_number' => $order->id,
                    'status' => 'completed',
                    'total' => $order->total
                ]
            ]);
        }

        // Crear notificación cuando el pago sea confirmado
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            Notification::create([
                'type' => 'payment',
                'title' => 'Pago confirmado',
                'message' => "Se confirmó el pago del pedido #{$order->id} por S/ " . number_format($order->total, 2),
                'priority' => 'high',
                'related_tab' => 'orders',
                'related_id' => $order->id,
                'order_id' => $order->id,
                'data' => [
                    'order_number' => $order->id,
                    'amount' => $order->total,
                    'payment_method' => $order->payment_method
                ]
            ]);
        }
    }
}
