<?php

/**
 * Script de optimización para el backend
 * Ejecutar: php optimize_backend.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🚀 Iniciando optimización del backend...\n\n";

// 1. Limpiar tokens antiguos (más de 7 días)
echo "📝 Eliminando tokens antiguos...\n";
$deletedTokens = DB::table('personal_access_tokens')
    ->where('created_at', '<', now()->subDays(7))
    ->delete();
echo "   ✅ Eliminados {$deletedTokens} tokens antiguos\n\n";

// 2. Optimizar base de datos SQLite
echo "🗄️  Optimizando base de datos...\n";
DB::statement('VACUUM');
echo "   ✅ Base de datos optimizada\n\n";

// 3. Analizar tablas para mejorar queries
echo "📊 Analizando tablas...\n";
DB::statement('ANALYZE');
echo "   ✅ Análisis completado\n\n";

// 4. Limpiar notificaciones leídas antiguas (más de 30 días)
echo "🔔 Limpiando notificaciones antiguas...\n";
$deletedNotifications = DB::table('notifications')
    ->where('read', true)
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
echo "   ✅ Eliminadas {$deletedNotifications} notificaciones antiguas\n\n";

// 5. Mostrar estadísticas
echo "📈 Estadísticas actuales:\n";
$stats = [
    'Usuarios' => DB::table('users')->count(),
    'Productos' => DB::table('products')->count(),
    'Pedidos' => DB::table('orders')->count(),
    'Notificaciones' => DB::table('notifications')->count(),
    'Tokens activos' => DB::table('personal_access_tokens')->count(),
];

foreach ($stats as $key => $value) {
    echo "   • {$key}: {$value}\n";
}

echo "\n✨ Optimización completada exitosamente!\n";
