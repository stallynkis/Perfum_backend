<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega la columna decants (JSON) a la tabla products.
     * Cada producto puede tener múltiples presentaciones en ml con su propio precio.
     * Ejemplo: [{"ml":5,"price":25},{"ml":10,"price":45},{"ml":30,"price":100}]
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('decants')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('decants');
        });
    }
};
