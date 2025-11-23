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
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'business_partner_id')) {
                $table->foreignId('business_partner_id')->nullable()->after('id')->constrained('business_partners')->onDelete('set null');
            }
            if (!Schema::hasColumn('purchases', 'document_type')) {
                $table->string('document_type', 50)->nullable()->after('business_partner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'business_partner_id')) {
                $table->dropForeign(['business_partner_id']);
                $table->dropColumn('business_partner_id');
            }
            if (Schema::hasColumn('purchases', 'document_type')) {
                $table->dropColumn('document_type');
            }
        });
    }
};
