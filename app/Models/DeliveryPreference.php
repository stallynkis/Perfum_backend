<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPreference extends Model
{
    protected $fillable = [
        'user_id',
        'deliveryOption',
        'phone',
        'address',
        'agencyType',
        'selectedAgencyId',
        'selectedAgencyName',
        'selectedAgencyAddress'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
