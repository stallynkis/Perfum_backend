<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Benefit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "🗑️  Limpiando base de datos...\n\n";

// Eliminar todos los productos
$productsDeleted = Product::count();
Product::truncate();
echo "✅ {$productsDeleted} productos eliminados\n";

// Eliminar todas las categorías
$categoriesDeleted = Category::count();
Category::truncate();
echo "✅ {$categoriesDeleted} categorías eliminadas\n";

// Eliminar todos los beneficios
$benefitsDeleted = Benefit::count();
Benefit::truncate();
echo "✅ {$benefitsDeleted} beneficios eliminados\n";

// Eliminar todos los usuarios EXCEPTO el admin
$usersDeleted = User::where('role', '!=', 'admin')->count();
User::where('role', '!=', 'admin')->delete();
echo "✅ {$usersDeleted} usuarios eliminados (admin mantenido)\n";

// Verificar que solo quede el admin
$adminCount = User::where('role', 'admin')->count();
echo "\n✅ Base de datos limpia\n";
echo "👤 Usuarios admin restantes: {$adminCount}\n";

if ($adminCount > 0) {
    $admin = User::where('role', 'admin')->first();
    echo "\nCredenciales del admin:\n";
    echo "Username: {$admin->name}\n";
    echo "Password: admin123\n";
}

echo "\n🎉 Listo! Base de datos limpia y lista para usar.\n";
