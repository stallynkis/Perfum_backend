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
        Schema::table('sales', function (Blueprint $table) {
            // Agregar register_id para la caja registradora
            if (!Schema::hasColumn('sales', 'register_id')) {
                $table->unsignedBigInteger('register_id')->nullable()->after('id');
            }
            
            // Agregar cash_session_id para la sesión de caja
            if (!Schema::hasColumn('sales', 'cash_session_id')) {
                $table->unsignedBigInteger('cash_session_id')->nullable()->after('register_id');
            }
            
            // Agregar tipo de transacción
            if (!Schema::hasColumn('sales', 'type')) {
                $table->string('type')->default('sale')->after('cash_session_id'); // sale, expense, income, etc.
            }
            
            // Asegurar que payment_method existe
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('amount'); // cash, card, yape, plin, transfer, mixed
            }
            
            // Agregar reference_id y reference_type para polymorphic relationships
            if (!Schema::hasColumn('sales', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('customer_address');
            }
            
            if (!Schema::hasColumn('sales', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('reference_id');
            }
            
            // Agregar índices para mejorar rendimiento
            $table->index('register_id');
            $table->index('cash_session_id');
            $table->index('seller_id');
            $table->index('type');
            $table->index(['reference_id', 'reference_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sales_register_id_index']);
            $table->dropIndex(['sales_cash_session_id_index']);
            $table->dropIndex(['sales_seller_id_index']);
            $table->dropIndex(['sales_type_index']);
            $table->dropIndex(['sales_reference_id_reference_type_index']);
            
            $table->dropColumn([
                'register_id',
                'cash_session_id',
                'type',
                'reference_id',
                'reference_type'
            ]);
        });
    }
};
