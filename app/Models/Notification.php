<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'priority',
        'read',
        'related_tab',
        'related_id',
        'order_id',
        'user_id',
        'vendor_id',
        'data'
    ];

    protected $casts = [
        'read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['time_ago'];

    /**
     * Relación con pedidos
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con usuario (cliente)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con vendedor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificaciones de alta prioridad
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update(['read' => true]);
    }

    /**
     * Marcar como no leída
     */
    public function markAsUnread()
    {
        $this->update(['read' => false]);
    }

    /**
     * Obtener tiempo transcurrido
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Crear notificación de pedido
     */
    public static function createOrderNotification($order, $type = 'order')
    {
        $paymentMethodLabels = [
            'card' => 'Tarjeta',
            'cash' => 'Efectivo',
            'yape' => 'Yape',
            'plin' => 'Plin',
            'transfer' => 'Transferencia',
            'bank' => 'Transferencia Bancaria'
        ];

        $paymentLabel = $paymentMethodLabels[$order->payment_method] ?? $order->payment_method;

        return self::create([
            'type' => 'order',
            'title' => 'Nueva orden pendiente de pago',
            'message' => "Pedido #{$order->id} por S/ " . number_format($order->total, 2) . " mediante {$paymentLabel}",
            'priority' => 'high',
            'related_tab' => 'orders',
            'related_id' => $order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id ?? null,
            'data' => [
                'order_number' => $order->id,
                'customer_name' => $order->customer_name ?? 'Cliente',
                'amount' => $order->total,
                'payment_method' => $order->payment_method,
                'status' => $order->status
            ]
        ]);
    }

    /**
     * Crear notificación de venta de vendedor
     */
    public static function createVendorSaleNotification($sale, $vendor)
    {
        $productNames = '';
        if (!empty($sale->product_ids)) {
            // Obtener nombres de productos si es necesario
            $productNames = count($sale->product_ids) . ' producto(s)';
        }

        return self::create([
            'type' => 'sale',
            'title' => 'Nueva venta registrada por vendedor',
            'message' => "Venta de {$productNames} por S/ " . number_format($sale->amount, 2) . " - Vendedor: {$vendor->name}",
            'priority' => 'medium',
            'related_tab' => 'vendor_sales',
            'related_id' => $sale->id,
            'vendor_id' => $vendor->id,
            'data' => [
                'sale_id' => $sale->id,
                'vendor_name' => $vendor->name,
                'amount' => $sale->amount,
                'payment_method' => $sale->payment_method ?? 'cash',
                'products' => $sale->product_ids ?? []
            ]
        ]);
    }

    /**
     * Crear notificación de formulario de contacto
     */
    public static function createContactFormNotification($contactForm)
    {
        return self::create([
            'type' => 'contact',
            'title' => 'Nueva consulta de cliente',
            'message' => "{$contactForm->name} envió una consulta: " . substr($contactForm->message, 0, 50) . '...',
            'priority' => 'medium',
            'related_tab' => 'contact',
            'related_id' => $contactForm->id,
            'contact_form_id' => $contactForm->id,
            'data' => [
                'contact_id' => $contactForm->id,
                'name' => $contactForm->name,
                'email' => $contactForm->email,
                'phone' => $contactForm->phone ?? null,
                'subject' => $contactForm->subject ?? 'Consulta general'
            ]
        ]);
    }

    /**
     * Crear notificación de stock bajo
     */
    public static function createLowStockNotification($product)
    {
        return self::create([
            'type' => 'stock',
            'title' => 'Alerta de stock bajo',
            'message' => "El producto '{$product->name}' tiene stock bajo: {$product->stock} unidades",
            'priority' => 'high',
            'related_tab' => 'products',
            'related_id' => $product->id,
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $product->stock,
                'min_stock' => 5
            ]
        ]);
    }
}
