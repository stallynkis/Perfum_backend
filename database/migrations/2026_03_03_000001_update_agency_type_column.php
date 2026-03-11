<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no soporta MODIFY COLUMN ni tiene ENUM real.
        // En SQLite los ENUM se almacenan como TEXT sin restricción de motor,
        // por lo que 'otros' ya se puede guardar sin cambios en el esquema.
        // Solo marcamos la migración como ejecutada para mantener coherencia.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN agency_type VARCHAR(50) NULL");
        }
        // Para SQLite: no action needed (TEXT column accepts any value)
    }

    public function down(): void
    {
        // No revert needed
    }
};
