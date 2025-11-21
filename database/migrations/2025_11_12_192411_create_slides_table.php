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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->mediumText('image'); // Cambiado a mediumText para base64
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('buttonText')->nullable();
            $table->string('buttonLink')->nullable();
            $table->integer('order')->default(1);
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
