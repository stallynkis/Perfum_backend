<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Crear usuario admin
$user = User::firstOrCreate(
    ['email' => 'admin@perfumeria.com'],
    [
        'name' => 'Administrador',
        'password' => Hash::make('admin123'),
        'role' => 'admin'
    ]
);

echo "✅ Usuario admin creado/actualizado:\n";
echo "Email: " . $user->email . "\n";
echo "Nombre: " . $user->name . "\n";
echo "Role: " . $user->role . "\n";
echo "\nCredenciales:\n";
echo "Username: admin@perfumeria.com\n";
echo "Password: admin123\n";
