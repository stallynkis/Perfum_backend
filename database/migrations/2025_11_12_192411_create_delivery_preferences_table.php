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
        Schema::create('delivery_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('deliveryOption', ['home', 'agency']);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('agencyType', ['olva', 'shalom'])->nullable();
            $table->string('selectedAgencyId')->nullable();
            $table->string('selectedAgencyName')->nullable();
            $table->string('selectedAgencyAddress')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_preferences');
    }
};
