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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite no soporta CHANGE, usamos workaround con columna temporal
            Schema::table('slides', function (Blueprint $table) {
                $table->mediumText('image_temp')->nullable()->after('image');
            });

            // Copiar datos
            DB::statement('UPDATE slides SET image_temp = image');

            // Eliminar columna original
            Schema::table('slides', function (Blueprint $table) {
                $table->dropColumn('image');
            });

            // Renombrar columna temporal
            Schema::table('slides', function (Blueprint $table) {
                $table->renameColumn('image_temp', 'image');
            });
        } else {
            // MySQL/MariaDB puede usar ALTER TABLE CHANGE directamente
            DB::statement('ALTER TABLE slides CHANGE image image MEDIUMTEXT NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('slides', function (Blueprint $table) {
                $table->string('image_temp')->nullable()->after('image');
            });

            DB::statement('UPDATE slides SET image_temp = image');

            Schema::table('slides', function (Blueprint $table) {
                $table->dropColumn('image');
            });

            Schema::table('slides', function (Blueprint $table) {
                $table->renameColumn('image_temp', 'image');
            });
        } else {
            DB::statement('ALTER TABLE slides CHANGE image image VARCHAR(255) NOT NULL');
        }
    }
};
