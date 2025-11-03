<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'supplier',
        'invoice_number',
        'notes',
        'purchase_date'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    // Relaciones
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        // Crear transacción de egreso al crear compra
        static::created(function ($purchase) {
            Transaction::create([
                'type' => 'expense',
                'category' => 'Compra de Stock',
                'description' => "Compra de {$purchase->quantity} unidades de {$purchase->product->name}",
                'amount' => $purchase->total_cost,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'transaction_date' => $purchase->purchase_date,
                'notes' => $purchase->notes
            ]);

            // Actualizar stock del producto
            $purchase->product->increment('stock', $purchase->quantity);
        });
    }
}
