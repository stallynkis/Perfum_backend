<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'buttonText',
        'buttonLink',
        'buttonAction',
        'actionValue',
        'order',
        'isActive'
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'order' => 'integer'
    ];
}
