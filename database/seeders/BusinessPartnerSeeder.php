<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessPartner;

class BusinessPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurar que CLIENTES VARIOS siempre tenga ID 1
        BusinessPartner::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'CLIENTES VARIOS',
                'type' => 'customer',
                'ruc' => '00000000',
                'email' => null,
                'phone' => null,
                'address' => null,
                'notes' => 'Cliente genérico para ventas sin registro específico',
                'is_active' => true
            ]
        );

        // Para MySQL, resetear el auto_increment
        if (\DB::getDriverName() === 'mysql') {
            \DB::statement('ALTER TABLE business_partners AUTO_INCREMENT = 2');
        }
    }
}
