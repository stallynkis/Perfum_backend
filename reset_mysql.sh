#!/bin/bash

# Script para resetear y migrar la base de datos MySQL

echo "🔄 Reseteando base de datos MySQL..."

# Limpiar todas las tablas y migrar desde cero
php artisan migrate:fresh

if [ $? -eq 0 ]; then
    echo "✅ Migraciones ejecutadas exitosamente"
    
    # Opcional: Ejecutar seeders si los tienes
    # php artisan db:seed
    
    echo "🎉 Base de datos lista para usar con MySQL"
else
    echo "❌ Error al ejecutar las migraciones"
    exit 1
fi
