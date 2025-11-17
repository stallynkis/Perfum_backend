@echo off
echo ========================================
echo 🚀 INICIANDO SERVIDOR LARAVEL
echo ========================================
echo.

cd /d "%~dp0"

echo 📋 Verificando admin en base de datos...
php artisan tinker --execute="echo 'Admin user: '; \$admin = \App\Models\User::where('role', 'admin')->first(); if(\$admin) { echo 'Email: ' . \$admin->email . PHP_EOL; echo 'Username: ' . \$admin->name . PHP_EOL; } else { echo 'NO HAY ADMIN - Ejecutando seeder...' . PHP_EOL; Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']); }"

echo.
echo 🔥 Iniciando servidor en http://localhost:8000
echo ========================================
echo.

php artisan serve --host=127.0.0.1 --port=8000

pause
