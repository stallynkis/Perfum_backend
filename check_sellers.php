<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== USUARIOS VENDEDORES ===\n\n";

$sellers = User::where('role', 'seller')->get();

if ($sellers->isEmpty()) {
    echo "❌ No hay vendedores en la base de datos\n\n";
} else {
    echo "✅ Total vendedores: " . $sellers->count() . "\n\n";
    foreach ($sellers as $seller) {
        echo "- ID: {$seller->id}\n";
        echo "  Nombre: {$seller->name}\n";
        echo "  Email: {$seller->email}\n";
        echo "  Rol: {$seller->role}\n\n";
    }
}

echo "=== TODOS LOS USUARIOS ===\n\n";
$allUsers = User::all();
echo "Total usuarios: " . $allUsers->count() . "\n\n";
foreach ($allUsers as $user) {
    echo "- {$user->name} ({$user->email}) - Rol: {$user->role}\n";
}
