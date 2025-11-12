<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

// Actualizar todos los productos para que tengan is_active = true
$updated = Product::whereNull('is_active')->update(['is_active' => true]);

echo "✅ Productos actualizados: {$updated}\n";

// Mostrar todos los productos
$products = Product::all();
echo "\n📦 Total de productos: " . $products->count() . "\n";

foreach ($products as $product) {
    echo "\n- {$product->name}";
    echo "\n  Activo: " . ($product->is_active ? 'Sí' : 'No');
    echo "\n  Stock: {$product->stock}";
    echo "\n  Precio: S/ {$product->price}";
    echo "\n";
}
