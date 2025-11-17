<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    protected $fillable = [
        'cash_session_id',
        'type',
        'amount',
        'description',
        'reference_id',
        'reference_type',
        'user_id',
        'seller_id',
        'customer_name',
        'customer_document',
        'payment_method',
        'document_type'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    protected $appends = ['user_name'];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }
}
