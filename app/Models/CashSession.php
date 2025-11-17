<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'cash_register_id',
        'user_id',
        'opening_date',
        'closing_date',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'status',
        'notes'
    ];

    protected $casts = [
        'opening_date' => 'datetime',
        'closing_date' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2'
    ];

    protected $appends = ['cash_register_name', 'user_name'];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function getCashRegisterNameAttribute()
    {
        return $this->cashRegister ? $this->cashRegister->name : null;
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }
}
