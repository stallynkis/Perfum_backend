<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'phone',
        'email',
        'address',
        'city',
        'hours',
        'whatsapp',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Obtener la información de contacto activa
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first() ?? self::getDefault();
    }

    /**
     * Obtener información de contacto por defecto
     */
    public static function getDefault()
    {
        return new self([
            'title' => 'Contáctanos',
            'subtitle' => 'Estamos aquí para ayudarte',
            'phone' => '+51 999 999 999',
            'email' => 'contacto@eliteperfumes.com',
            'address' => 'Av. Principal 123',
            'city' => 'Lima, Perú',
            'hours' => 'Lunes a Viernes: 9:00 AM - 6:00 PM',
            'whatsapp' => '+51999999999',
            'description' => 'Contáctanos para cualquier consulta sobre nuestros productos.',
            'is_active' => true
        ]);
    }
}