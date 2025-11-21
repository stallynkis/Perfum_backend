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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->nullable()->default(0);
            $table->string('category')->default('general');
            $table->string('brand')->nullable();
            $table->mediumText('image')->nullable(); // Cambiado a mediumText para base64
            $table->decimal('rating', 3, 1)->nullable()->default(4.5);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->json('notes')->nullable(); // Para las notas como array
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
