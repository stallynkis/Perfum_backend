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
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('agency_address');
            }

            if (!Schema::hasColumn('orders', 'tracking_order_number')) {
                $table->string('tracking_order_number')->nullable()->after('tracking_number');
            }

            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('tracking_order_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('orders', 'shipped_at')) {
                $columnsToDrop[] = 'shipped_at';
            }

            if (Schema::hasColumn('orders', 'tracking_order_number')) {
                $columnsToDrop[] = 'tracking_order_number';
            }

            if (Schema::hasColumn('orders', 'tracking_number')) {
                $columnsToDrop[] = 'tracking_number';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
