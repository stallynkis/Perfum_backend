<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla existe antes de agregar índices
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Índice compuesto para consultas de vendedor
                try {
                    $table->index(['user_id', 'source', 'created_at'], 'idx_seller_orders');
                } catch (\Exception $e) {
                    // Índice ya existe
                }
                
                // Índice para búsqueda por fecha
                try {
                    $table->index('created_at', 'idx_created_at');
                } catch (\Exception $e) {
                    // Índice ya existe
                }
                
                // Índice para filtros de estado
                try {
                    $table->index('status', 'idx_status');
                } catch (\Exception $e) {
                    // Índice ya existe
                }
                
                try {
                    $table->index('payment_status', 'idx_payment_status');
                } catch (\Exception $e) {
                    // Índice ya existe
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_seller_orders');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_payment_status');
        });
    }
};
