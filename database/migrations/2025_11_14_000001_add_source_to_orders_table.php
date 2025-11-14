<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agregar campo 'source' a la tabla orders para diferenciar ventas web vs vendedores
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Agregar campo 'source' si no existe
        if (!Schema::hasColumn('orders', 'source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('source', 10)->default('web')->after('user_id');
            });
        }
        
        // Modificar payment_method según el driver
        if ($driver === 'mysql') {
            // MySQL: usar ENUM
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal', 'yape', 'cash', 'card', 'transfer') DEFAULT 'paypal'");
        } else {
            // SQLite/otros: no soportan ENUM, ya funciona como string
            // No hacer nada, SQLite ya acepta cualquier valor
        }
        
        // Actualizar órdenes existentes basado en payment_method
        DB::table('orders')
            ->whereIn('payment_method', ['cash', 'card', 'transfer'])
            ->update(['source' => 'seller']);
        
        DB::table('orders')
            ->whereIn('payment_method', ['paypal', 'yape'])
            ->update(['source' => 'web']);
    }

    /**
     * Revertir los cambios
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
        
        // Revertir payment_method a solo paypal y yape
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal', 'yape') DEFAULT 'paypal'");
    }
};
