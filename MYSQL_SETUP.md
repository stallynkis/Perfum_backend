# 🔧 Guía de Configuración MySQL - Perfumería Herlinso

## 📋 Pasos para configurar MySQL

### 1. Crear la base de datos

Abre MySQL desde tu terminal o cliente MySQL:

```bash
mysql -u root -p
```

Ejecuta estos comandos SQL:

```sql
CREATE DATABASE perfumeria_herlinso CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES;
EXIT;
```

### 2. Configurar archivo .env

Copia el archivo de ejemplo:

```bash
cp .env.mysql.example .env
```

O manualmente crea un archivo `.env` con esta configuración:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perfumeria_herlinso
DB_USERNAME=root
DB_PASSWORD=tu_contraseña_mysql
```

### 3. Generar la clave de aplicación

```bash
php artisan key:generate
```

### 4. Ejecutar las migraciones

Opción A - Usando el script (Windows):
```bash
reset_mysql.bat
```

Opción B - Usando el script (Linux/Mac):
```bash
chmod +x reset_mysql.sh
./reset_mysql.sh
```

Opción C - Manualmente:
```bash
php artisan migrate:fresh
```

### 5. (Opcional) Ejecutar seeders

Si tienes datos de prueba:

```bash
php artisan db:seed
```

## ✅ Verificación

Comprueba que las tablas se crearon correctamente:

```bash
php artisan migrate:status
```

O desde MySQL:

```sql
USE perfumeria_herlinso;
SHOW TABLES;
```

## 🔍 Solución de problemas comunes

### Error: "Access denied for user"
- Verifica tu usuario y contraseña en `.env`
- Asegúrate de que MySQL esté corriendo

### Error: "Unknown database"
- Verifica que creaste la base de datos
- Comprueba el nombre en `.env`

### Error: "SQLSTATE[HY000] [2002]"
- Verifica que MySQL esté corriendo
- Comprueba el puerto y host en `.env`

### Error con foreign keys
- Las migraciones ya están optimizadas para MySQL
- Si persiste, ejecuta: `php artisan migrate:fresh --force`

## 📊 Estructura de tablas creadas

- ✅ users (usuarios con roles)
- ✅ products (productos de perfumería)
- ✅ sales (ventas)
- ✅ purchases (compras)
- ✅ transactions (transacciones)
- ✅ contact_infos (información de contacto)
- ✅ personal_access_tokens (tokens de API)
- ✅ sessions, cache, jobs (sistema Laravel)

## 🚀 Iniciar el servidor

```bash
php artisan serve
```

La API estará disponible en: http://localhost:8000

## 🔗 Conectar con el Frontend

Asegúrate de que en tu frontend (Vite React) la API_URL apunte a:
```
http://localhost:8000/api
```

## 📝 Notas importantes

1. Todas las migraciones están optimizadas para MySQL
2. Se usan claves foráneas explícitas en lugar de `foreignId()`
3. Los campos `enum` tienen valores por defecto
4. Los campos `decimal` tienen valores por defecto para evitar errores
5. Compatible con MySQL 5.7+ y MySQL 8.0+

---

Para más información, revisa la documentación de Laravel:
https://laravel.com/docs/11.x/database
