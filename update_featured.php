<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Actualizar TODOS los productos para que sean destacados
$updated = DB::table('products')->update(['is_featured' => 1]);

echo "✅ Productos actualizados: $updated\n\n";

// Mostrar todos los productos
$products = DB::table('products')->select('id', 'name', 'is_featured', 'is_active')->get();

echo "📦 PRODUCTOS EN LA BD:\n";
foreach ($products as $product) {
    $featured = $product->is_featured ? '⭐ DESTACADO' : '  normal';
    $active = $product->is_active ? '✅' : '❌';
    echo "$active $featured - ID: $product->id - $product->name\n";
}
