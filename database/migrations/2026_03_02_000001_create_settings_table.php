<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Insertar configuración inicial del negocio
        DB::table('settings')->insert([
            'key'        => 'business_info',
            'value'      => json_encode([
                'name'    => 'HERLINSO PERFUMERÍA',
                'ruc'     => '20123456789',
                'address' => 'Av. Principal 123, Lima, Perú',
                'phone'   => '+51 999 999 999',
                'email'   => 'contacto@herlinsoperfumeria.com',
                'website' => 'www.herlinsoperfumeria.com',
                'slogan'  => 'La fragancia perfecta para cada momento',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
