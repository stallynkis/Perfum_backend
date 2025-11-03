<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'category',
        'description',
        'amount',
        'reference_type',
        'reference_id',
        'payment_method',
        'notes',
        'transaction_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relaciones polimórficas para ventas y compras
    public function referenceable()
    {
        return $this->morphTo('reference');
    }
}
