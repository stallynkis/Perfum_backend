# 🚀 Configuración de WebSockets con Laravel Reverb

## Paso 1: Instalar Laravel Reverb

```bash
# En la terminal del backend (perfum_backend):
composer require laravel/reverb

# Publicar configuración
php artisan reverb:install

# Esto creará:
# - config/reverb.php
# - Agregará variables en .env
```

## Paso 2: Configurar .env

Agregar/modificar en `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=perfumeria
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Paso 3: Iniciar Servidor Reverb

```bash
# En una terminal SEPARADA (dejar corriendo):
php artisan reverb:start

# O en modo debug:
php artisan reverb:start --debug
```

## Paso 4: Verificar que funciona

1. Iniciar servidor Laravel: `php artisan serve`
2. Iniciar Reverb: `php artisan reverb:start`
3. Ver logs en tiempo real cuando se emitan eventos

---

**IMPORTANTE**: Reverb debe estar corriendo en una terminal separada mientras desarrollas.
Para producción, usar Supervisor o PM2 para mantenerlo activo.
