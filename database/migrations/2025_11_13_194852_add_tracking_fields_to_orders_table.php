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
            // Campos para tracking de agencias (Olva y Shalom)
            $table->string('tracking_number')->nullable()->after('agency_address'); // Número de guía
            $table->string('tracking_order_number')->nullable()->after('tracking_number'); // Número de orden de agencia
            $table->timestamp('shipped_at')->nullable()->after('tracking_order_number'); // Fecha de envío
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'tracking_order_number', 'shipped_at']);
        });
    }
};
