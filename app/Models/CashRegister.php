<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'name',
        'code',
        'responsible_user_id',
        'is_active',
        'is_collection_box',
        'current_balance'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_collection_box' => 'boolean',
        'current_balance' => 'decimal:2'
    ];

    protected $appends = ['responsible_name'];

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function currentSession()
    {
        return $this->sessions()->where('status', 'open')->first();
    }

    public function getResponsibleNameAttribute()
    {
        return $this->responsibleUser ? $this->responsibleUser->name : null;
    }
}
