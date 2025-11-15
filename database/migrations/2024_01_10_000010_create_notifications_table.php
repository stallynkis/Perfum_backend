<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();

                // Tipo
                $table->enum('type', [
                    'order', 'sale', 'contact', 'system', 'payment', 'stock',
                    'new_order', 'order_status_change', 'payment_confirmed'
                ])->default('system');

                $table->string('title');
                $table->text('message');

                $table->enum('priority', ['high','medium','low'])->default('medium');
                $table->boolean('read')->default(false);

                $table->string('related_tab')->nullable();
                $table->string('related_id')->nullable();

                // ❌ SIN foreign keys (evita errores)
                $table->foreignId('order_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('vendor_id')->nullable();
                $table->foreignId('contact_form_id')->nullable();

                $table->json('data')->nullable();
                $table->timestamps();

                $table->index('type');
                $table->index('read');
                $table->index('priority');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
