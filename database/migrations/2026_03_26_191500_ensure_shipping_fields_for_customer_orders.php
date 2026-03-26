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
            // Campos que el admin registra para el envío y que ve el cliente.
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('agency_address');
            }

            if (!Schema::hasColumn('orders', 'tracking_order_number')) {
                $table->string('tracking_order_number')->nullable()->after('tracking_number');
            }

            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('tracking_order_number');
            }

            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se eliminan columnas para evitar pérdida de datos históricos.
    }
};
