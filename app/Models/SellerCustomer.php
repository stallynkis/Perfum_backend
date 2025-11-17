<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'name',
        'document',
        'phone',
        'email',
        'address',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
