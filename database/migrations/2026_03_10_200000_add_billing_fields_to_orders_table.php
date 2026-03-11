<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('billing_status', 30)->nullable()->default(null)->after('document_type');
            // pendiente | emitida | error | no_configurado
            $table->string('billing_number', 50)->nullable()->after('billing_status');
            // Ej: B001-1
            $table->text('billing_error')->nullable()->after('billing_number');
            // Mensaje de error si falló
            $table->unsignedBigInteger('billing_document_id')->nullable()->after('billing_error');
            // FK opcional al billing_documents
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['billing_status', 'billing_number', 'billing_error', 'billing_document_id']);
        });
    }
};
