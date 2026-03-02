<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: modificar el ENUM para incluir 'mixed'
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal','yape','cash','card','transfer','mixed') DEFAULT 'paypal'");
            DB::statement("ALTER TABLE cash_movements MODIFY COLUMN payment_method ENUM('cash','card','yape','transfer','mixed') NULL");
        }
        // SQLite: no necesita cambio (almacena TEXT sin restricción ENUM real)
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal','yape','cash','card','transfer') DEFAULT 'paypal'");
            DB::statement("ALTER TABLE cash_movements MODIFY COLUMN payment_method ENUM('cash','card','yape','transfer') NULL");
        }
    }
};
