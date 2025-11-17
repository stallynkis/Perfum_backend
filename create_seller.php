<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Datos del vendedor
$name = $argv[1] ?? 'Vendedor Test';
$email = $argv[2] ?? 'vendedor@herlinso.com';
$password = $argv[3] ?? '123456';

// Verificar si ya existe
$existingSeller = User::where('email', $email)->first();
if ($existingSeller) {
    echo "❌ Ya existe un usuario con el email: {$email}\n";
    echo "ID: {$existingSeller->id}\n";
    echo "Nombre: {$existingSeller->name}\n";
    echo "Email: {$existingSeller->email}\n";
    echo "Role: {$existingSeller->role}\n\n";
    
    // Actualizar a vendedor si no lo es
    if ($existingSeller->role !== 'vendedor') {
        $existingSeller->role = 'vendedor';
        $existingSeller->password = Hash::make($password);
        $existingSeller->is_active = true;
        $existingSeller->save();
        echo "✅ Usuario actualizado a vendedor con nueva contraseña\n";
    } else {
        echo "Actualizando contraseña...\n";
        $existingSeller->password = Hash::make($password);
        $existingSeller->is_active = true;
        $existingSeller->save();
        echo "✅ Contraseña actualizada\n";
    }
    exit;
}

// Crear nuevo vendedor
$seller = User::create([
    'name' => $name,
    'email' => $email,
    'password' => Hash::make($password),
    'role' => 'vendedor',
    'is_active' => true
]);

echo "✅ Vendedor creado exitosamente!\n\n";
echo "=== CREDENCIALES DEL VENDEDOR ===\n";
echo "ID: {$seller->id}\n";
echo "Nombre: {$seller->name}\n";
echo "Email: {$seller->email}\n";
echo "Contraseña: {$password}\n";
echo "Role: {$seller->role}\n";
echo "================================\n\n";
echo "Puedes usar estas credenciales para iniciar sesión en:\n";
echo "http://localhost:5173/vendedor/login\n";
