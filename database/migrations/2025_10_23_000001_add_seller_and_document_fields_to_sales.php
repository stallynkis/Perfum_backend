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
            // Agregar seller_id para identificar al vendedor
            $table->unsignedBigInteger('seller_id')->nullable()->after('user_id');
            
            // Agregar campos para comprobante
            $table->string('document_type')->nullable()->after('payment_method'); // ticket, boleta, factura
            $table->string('customer_name')->nullable()->after('document_type');
            $table->string('customer_document')->nullable()->after('customer_name');
            $table->string('customer_address')->nullable()->after('customer_document');
            
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['seller_id', 'document_type', 'customer_name', 'customer_document', 'customer_address']);
        });
    }
};
