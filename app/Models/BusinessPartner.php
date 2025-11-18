<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'ruc',
        'email',
        'phone',
        'address',
        'contact_person',
        'credit_limit',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2'
    ];
}
