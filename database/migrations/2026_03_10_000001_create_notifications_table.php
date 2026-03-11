<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->boolean('read')->default(false);
            $table->string('related_tab', 50)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('read');
            $table->index('order_id');
            $table->index('user_id');
            $table->index('vendor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
