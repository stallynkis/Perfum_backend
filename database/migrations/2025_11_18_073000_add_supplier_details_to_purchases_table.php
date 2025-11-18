<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_ruc', 20)->nullable()->after('supplier');
            $table->string('supplier_phone', 20)->nullable()->after('supplier_ruc');
            $table->string('supplier_email')->nullable()->after('supplier_phone');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['supplier_ruc', 'supplier_phone', 'supplier_email']);
        });
    }
};
