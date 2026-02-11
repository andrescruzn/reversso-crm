# 🚀 REVERSSO CRM - GUÍA DE INSTALACIÓN COMPLETA

## 📋 REQUISITOS

- PHP 8.2+
- Composer
- MySQL 8.0+ o PostgreSQL
- Laravel 11

---

## 🔧 PASO 1: CONFIGURAR BASE DE DATOS

### Crear base de datos MySQL:

```bash
mysql -u root -p
CREATE DATABASE reversso_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Configurar .env:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reversso_crm
DB_USERNAME=root
DB_PASSWORD=tu_contraseña

JWT_SECRET=(se generará automáticamente)
```

---

## 📦 PASO 2: COPIAR ARCHIVOS

### Estructura de carpetas a crear:

```
reversso-crm/
├── app/
│   ├── Common/
│   │   ├── Enums/
│   │   │   ├── ErrorCode.php
│   │   │   ├── UserRole.php
│   │   │   └── TrackingStatus.php
│   │   ├── Http/Responses/
│   │   │   └── ApiResponse.php
│   │   └── Services/
│   │       └── ServiceResult.php
│   │
│   └── Modules/
│       ├── Users/
│       │   ├── Infrastructure/Models/
│       │   │   ├── User.php
│       │   │   └── Role.php
│       │   └── Presentation/Http/Controllers/
│       │       └── AuthController.php
│       │
│       └── Logistics/TimeTracking/
│           ├── Infrastructure/Models/
│           │   └── TimeTracking.php
│           └── Presentation/Http/Controllers/
│               └── TimeTrackingController.php
│
├── database/migrations/
│   ├── 2024_01_01_000001_create_roles_table.php
│   ├── 2024_01_01_000002_create_users_table.php
│   ├── 2024_01_01_000003_create_role_user_table.php
│   └── 2024_01_01_000004_create_time_tracking_table.php
│
└── routes/
    └── api.php
```

### IMPORTANTE:

Antes de copiar las migraciones, ELIMINA la migración de usuarios que viene por defecto con Laravel:

```bash
rm database/migrations/*_create_users_table.php
rm database/migrations/*_create_password_*
rm database/migrations/*_create_cache_*
rm database/migrations/*_create_jobs_*
```

---

## ⚙️ PASO 3: INSTALAR DEPENDENCIAS

```bash
cd reversso-crm

# Instalar JWT
composer require tymon/jwt-auth

# Publicar config de JWT
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Generar secret JWT
php artisan jwt:secret

# Herramientas de desarrollo
composer require --dev laravel/pint phpstan/phpstan
```

---

## 🗄️ PASO 4: EJECUTAR MIGRACIONES

```bash
# Ejecutar migraciones
php artisan migrate

# Deberías ver:
# ✓ create_roles_table
# ✓ create_users_table
# ✓ create_role_user_table
# ✓ create_time_tracking_table
```

---

## 🌱 PASO 5: CREAR DATOS INICIALES (SEEDERS)

### Crear RoleSeeder:

```bash
php artisan make:seeder RoleSeeder
```

Luego edita `database/seeders/RoleSeeder.php` y pega el contenido proporcionado.

### Ejecutar seeders:

```bash
php artisan db:seed --class=RoleSeeder
```

---

## 🔐 PASO 6: CONFIGURAR JWT EN User MODEL

Edita `app/Modules/Users/Infrastructure/Models/User.php` y asegúrate de implementar `JWTSubject`.

---

## 🛣️ PASO 7: CONFIGURAR RUTAS

Reemplaza el contenido de `routes/api.php` con el archivo proporcionado.

### Verificar rutas:

```bash
php artisan route:list
```

---

## ✅ PASO 8: VERIFICAR INSTALACIÓN

### Iniciar servidor:

```bash
php artisan serve
```

### Probar endpoint de salud:

```bash
curl http://127.0.0.1:8000/api/v1/health
```

Deberías recibir:

```json
{
    "status": "ok",
    "timestamp": "2024-02-10...",
    "service": "REVERSSO CRM API",
    "version": "v1.0.0"
}
```

---

## 🧪 PASO 9: PROBAR AUTENTICACIÓN

### Registrar usuario:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin Test",
    "email": "admin@reversso.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@reversso.com",
    "password": "password123"
  }'
```

Recibirás un token JWT que usarás en las siguientes peticiones.

---

## 📚 DOCUMENTACIÓN DE API

### Endpoints disponibles:

#### Autenticación:

- `POST /api/v1/auth/register` - Registro
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/me` - Usuario actual

#### Time Tracking (Conductores):

- `POST /api/v1/time-tracking/start` - Iniciar jornada
- `POST /api/v1/time-tracking/end` - Finalizar jornada
- `GET /api/v1/time-tracking/active` - Registro activo
- `GET /api/v1/time-tracking/my-history` - Historial

#### Time Tracking (Admins):

- `GET /api/v1/time-tracking` - Listar todos
- `GET /api/v1/time-tracking/{id}` - Ver detalle
- `POST /api/v1/time-tracking/{id}/approve` - Aprobar
- `PUT /api/v1/time-tracking/{id}` - Editar
- `DELETE /api/v1/time-tracking/{id}` - Eliminar

---

## 🛠️ COMANDOS ÚTILES

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Formatear código
./vendor/bin/pint

# Ver rutas
php artisan route:list

# Rollback migraciones
php artisan migrate:rollback

# Refrescar base de datos (CUIDADO: borra todo)
php artisan migrate:fresh --seed
```

---

## 🔍 SOLUCIÓN DE PROBLEMAS

### Error: "Class not found"

```bash
composer dump-autoload
```

### Error: "SQLSTATE[HY000] [1045]"

```bash
# Verifica credenciales en .env
# Asegúrate que MySQL esté corriendo
```

### Error: "No application encryption key"

```bash
php artisan key:generate
```

### Error: "JWT secret not set"

```bash
php artisan jwt:secret
```

---

## 📖 PRÓXIMOS PASOS

1. ✅ Copiar todos los archivos proporcionados
2. ✅ Configurar .env
3. ✅ Ejecutar migraciones
4. ✅ Ejecutar seeders
5. ✅ Probar endpoints
6. 🔜 Implementar frontend (Fase 3)
7. 🔜 Desplegar a producción

---

**Fecha**: Febrero 2026
**Versión**: 1.0.0
**Estado**: ✅ LISTO PARA DESARROLLO
