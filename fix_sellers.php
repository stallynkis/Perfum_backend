<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== ACTUALIZANDO VENDEDORES ===\n\n";

$vendedores = User::where('role', 'vendedor')->get();

if ($vendedores->isEmpty()) {
    echo "❌ No hay usuarios con rol 'vendedor'\n";
} else {
    echo "✅ Encontrados {$vendedores->count()} usuarios con rol 'vendedor'\n\n";
    
    foreach ($vendedores as $vendedor) {
        echo "Actualizando: {$vendedor->name} ({$vendedor->email})\n";
        $vendedor->role = 'seller';
        $vendedor->save();
    }
    
    echo "\n✅ Todos los vendedores actualizados a rol 'seller'\n";
}

echo "\n=== VENDEDORES FINALES ===\n\n";
$sellers = User::where('role', 'seller')->get();
echo "Total con rol 'seller': {$sellers->count()}\n\n";

foreach ($sellers as $seller) {
    echo "- {$seller->name} ({$seller->email}) - Activo: " . ($seller->is_active ? 'Sí' : 'No') . "\n";
}
