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
        Schema::table('orders', function (Blueprint $table) {
            // Índice compuesto para consultas de vendedor
            $table->index(['user_id', 'source', 'created_at'], 'idx_seller_orders');
            
            // Índice para búsqueda por fecha
            $table->index('created_at', 'idx_created_at');
            
            // Índice para filtros de estado
            $table->index('status', 'idx_status');
            $table->index('payment_status', 'idx_payment_status');
        });
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
