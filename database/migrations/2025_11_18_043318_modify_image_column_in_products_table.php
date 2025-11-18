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
            // SQLite no soporta ALTER COLUMN, recreamos la tabla
            Schema::table('products', function (Blueprint $table) {
                $table->text('image_temp')->nullable();
            });
            
            DB::statement('UPDATE products SET image_temp = image');
            
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('image');
            });
            
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('image_temp', 'image');
            });
        } else {
            // MySQL/PostgreSQL soportan ALTER COLUMN
            Schema::table('products', function (Blueprint $table) {
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
            // Para SQLite, no hacemos rollback complejo
            // La columna quedará como text
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image')->nullable()->change();
            });
        }
    }
};
