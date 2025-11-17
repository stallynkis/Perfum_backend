<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== RECREANDO TABLA NOTIFICATIONS SIN CONTACT_FORM_ID ===\n\n";

// 1. Respaldar datos existentes
echo "1. Respaldando notificaciones existentes...\n";
$notifications = DB::table('notifications')->get()->toArray();
$count = count($notifications);
echo "   ✅ {$count} notificaciones respaldadas\n\n";

// 2. Eliminar tabla
echo "2. Eliminando tabla notifications...\n";
Schema::dropIfExists('notifications');
echo "   ✅ Tabla eliminada\n\n";

// 3. Recrear tabla sin contact_form_id
echo "3. Recreando tabla notifications...\n";
Schema::create('notifications', function ($table) {
    $table->id();
    
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
    
    // Sin foreign keys para evitar errores de dependencias
    $table->unsignedBigInteger('order_id')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->unsignedBigInteger('vendor_id')->nullable();
    
    $table->json('data')->nullable();
    $table->timestamps();
    
    $table->index('type');
    $table->index('read');
    $table->index('priority');
    $table->index('created_at');
});
echo "   ✅ Tabla recreada sin contact_form_id\n\n";

// 4. Restaurar datos
echo "4. Restaurando notificaciones...\n";
foreach ($notifications as $notification) {
    $data = (array) $notification;
    unset($data['contact_form_id']); // Eliminar el campo problemático
    DB::table('notifications')->insert($data);
}
echo "   ✅ {$count} notificaciones restauradas\n\n";

echo "✅ PROCESO COMPLETADO\n";
