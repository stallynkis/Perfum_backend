#!/bin/bash

# Script para resetear y migrar la base de datos Laravel

echo "🔄 Reseteando base de datos..."
php artisan migrate:reset

echo ""
echo "🚀 Ejecutando migraciones..."
php artisan migrate

echo ""
echo "✅ Migraciones completadas!"
