@echo off
echo Reseteando base de datos MySQL...

php artisan migrate:fresh

if %ERRORLEVEL% EQU 0 (
    echo Migraciones ejecutadas exitosamente
    echo Base de datos lista para usar con MySQL
) else (
    echo Error al ejecutar las migraciones
    exit /b 1
)

pause
