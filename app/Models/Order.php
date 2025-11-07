<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document',
        'delivery_type',
        'shipping_address',
        'shipping_district',
        'shipping_reference',
        'agency_type',
        'agency_id',
        'agency_name',
        'agency_address',
        'items',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'payment_method',
        'transaction_id',
        'approval_code',
        'payment_status',
        'status',
        'notes',
        'admin_notes',
        'requires_admin_confirmation'
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'requires_admin_confirmation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = ['formatted_status', 'formatted_payment_status'];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'processing' => 'Procesando',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => $this->status
        };
    }

    public function getFormattedPaymentStatusAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            default => $this->payment_status
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRequiresConfirmation($query)
    {
        return $query->where('requires_admin_confirmation', true)
                     ->where('payment_status', 'pending');
    }

    // Helper methods
    public static function generateOrderNumber(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        return "ORD-{$timestamp}-{$random}";
    }

    public function getTotalItems(): int
    {
        if (!is_array($this->items)) {
            return 0;
        }

        return array_reduce($this->items, function ($carry, $item) {
            return $carry + ($item['quantity'] ?? 0);
        }, 0);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markAsPaid(string $transactionId = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'requires_admin_confirmation' => false
        ]);
    }

    public function markAsShipped(): void
    {
        $this->update(['status' => 'shipped']);
    }

    public function markAsDelivered(): void
    {
        $this->update(['status' => 'delivered']);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'admin_notes' => $reason
        ]);
    }
}
