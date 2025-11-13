<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Relación con productos
    public function products()
    {
        return $this->hasMany(Product::class, 'brand', 'name');
    }

    // Scope para marcas activas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para ordenar
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }
}
