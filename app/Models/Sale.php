<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_method',
        'document_type',
        'customer_name',
        'customer_document',
        'customer_address',
        'status',
        'notes',
        'sale_date'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sale_date' => 'date',
    ];

    // Relaciones
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        // Crear transacción de ingreso al crear venta
        static::created(function ($sale) {
            if ($sale->status === 'completed') {
                Transaction::create([
                    'type' => 'income',
                    'category' => 'Venta de Producto',
                    'description' => "Venta de {$sale->quantity} unidades de {$sale->product->name}",
                    'amount' => $sale->total_amount,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'payment_method' => $sale->payment_method,
                    'transaction_date' => $sale->sale_date,
                    'notes' => $sale->notes
                ]);

                // Reducir stock del producto
                $sale->product->decrement('stock', $sale->quantity);
            }
        });
    }
}
