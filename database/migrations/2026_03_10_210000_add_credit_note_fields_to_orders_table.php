<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('credit_note_number', 50)->nullable()->after('billing_document_id');
            $table->string('credit_note_status', 30)->nullable()->after('credit_note_number');
            $table->unsignedBigInteger('credit_note_document_id')->nullable()->after('credit_note_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['credit_note_number', 'credit_note_status', 'credit_note_document_id']);
        });
    }
};
