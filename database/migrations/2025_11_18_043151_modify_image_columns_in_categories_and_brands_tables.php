<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: recrear columnas
            // Categories
            Schema::table('categories', function (Blueprint $table) {
                $table->text('image_temp')->nullable();
            });
            DB::statement('UPDATE categories SET image_temp = image');
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('image');
            });
            Schema::table('categories', function (Blueprint $table) {
                $table->renameColumn('image_temp', 'image');
            });
            
            // Brands
            Schema::table('brands', function (Blueprint $table) {
                $table->text('image_temp')->nullable();
            });
            DB::statement('UPDATE brands SET image_temp = image');
            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('image');
            });
            Schema::table('brands', function (Blueprint $table) {
                $table->renameColumn('image_temp', 'image');
            });
        } else {
            // MySQL/PostgreSQL
            Schema::table('categories', function (Blueprint $table) {
                $table->mediumText('image')->nullable()->change();
            });

            Schema::table('brands', function (Blueprint $table) {
                $table->mediumText('image')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // Para SQLite, no hacemos rollback
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('image')->nullable()->change();
            });

            Schema::table('brands', function (Blueprint $table) {
                $table->string('image')->nullable()->change();
            });
        }
    }
};
