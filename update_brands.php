<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "🔄 Actualizando marcas de productos...\n\n";

try {
    // Actualizar todos los productos que tienen "Elite Perfumes" o cualquier otra marca
    $updated = Product::where('brand', '!=', 'HERLINSO PERFUMERIA')
        ->orWhereNull('brand')
        ->update(['brand' => 'HERLINSO PERFUMERIA']);
    
    echo "✅ Se actualizaron {$updated} productos con la marca 'HERLINSO PERFUMERIA'\n";
    
    // Mostrar el total de productos actualizados
    $total = Product::where('brand', 'HERLINSO PERFUMERIA')->count();
    echo "📦 Total de productos con 'HERLINSO PERFUMERIA': {$total}\n";
    
    // Mostrar algunos productos de ejemplo
    echo "\n📋 Ejemplos de productos actualizados:\n";
    $examples = Product::where('brand', 'HERLINSO PERFUMERIA')->take(5)->get();
    foreach ($examples as $product) {
        echo "  - {$product->name} (ID: {$product->id}) - Marca: {$product->brand}\n";
    }
    
    echo "\n✅ ¡Actualización completada exitosamente!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
