<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('payment_breakdown')->nullable()->after('payment_method');
            $table->decimal('cash_received', 10, 2)->nullable()->after('payment_breakdown');
            $table->decimal('change_amount', 10, 2)->nullable()->after('cash_received');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->json('payment_breakdown')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_breakdown');
            $table->decimal('cash_received', 10, 2)->nullable()->after('payment_reference');
            $table->decimal('change_amount', 10, 2)->nullable()->after('cash_received');
        });

        DB::statement("ALTER TABLE cash_movements MODIFY COLUMN payment_method ENUM('cash','card','yape','plin','transfer','mixed') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE cash_movements MODIFY COLUMN payment_method ENUM('cash','card','yape','transfer','mixed') NULL");

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropColumn(['payment_breakdown', 'payment_reference', 'cash_received', 'change_amount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_breakdown', 'cash_received', 'change_amount']);
        });
    }
};
