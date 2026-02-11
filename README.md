# 🚛 REVERSSO CRM - Sistema de Gestión Logística

API REST para gestión de tracking de conductores y cálculo de nómina según legislación colombiana.

---

## 📋 CARACTERÍSTICAS

✅ **Autenticación JWT** - Login seguro para Web y Mobile
✅ **Gestión de Roles** - Admin y Conductor
✅ **Time Tracking** - Registro de entrada/salida con odómetro
✅ **Cálculo de Horas Extras** - Según legislación colombiana
✅ **Aprobación Administrativa** - Workflow de aprobación
✅ **API RESTful** - Endpoints estándar y documentados
✅ **Arquitectura Limpia** - SOLID, DRY, Modular Monolith

---

## 🛠️ TECNOLOGÍAS

- **Backend**: Laravel 11 + PHP 8.2+
- **Base de Datos**: MySQL 8.0+ / PostgreSQL
- **Autenticación**: JWT (tymon/jwt-auth)
- **Estándares**: PSR-12, PHPStan Level 8

---

## 🚀 INSTALACIÓN RÁPIDA

### Opción 1: Script Automático (Recomendado)

#### Windows:

```bash
install.bat
```

#### Linux/Mac:

```bash
chmod +x install.sh
./install.sh
```

### Opción 2: Manual

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/reversso-crm.git
cd reversso-crm

# 2. Instalar dependencias
composer install

# 3. Configurar .env
cp .env.example .env
# Edita .env con tus credenciales de BD

# 4. Generar claves
php artisan key:generate
php artisan jwt:secret

# 5. Migrar base de datos
php artisan migrate

# 6. Crear datos iniciales
php artisan db:seed --class=RoleSeeder

# 7. Iniciar servidor
php artisan serve
```

---

## 📁 ESTRUCTURA DEL PROYECTO

```
reversso-crm/
├── app/
│   ├── Common/                    # Código compartido
│   │   ├── Enums/                 # ErrorCode, UserRole, TrackingStatus
│   │   ├── Http/Responses/        # ApiResponse
│   │   └── Services/              # ServiceResult
│   │
│   └── Modules/                   # Módulos de negocio
│       ├── Users/                 # Gestión de usuarios
│       └── Logistics/             # Logística (sub-modularizado)
│           ├── TimeTracking/      # Registro de horas
│           ├── Overtime/          # Cálculo de extras
│           └── Reports/           # Reportes administrativos
│
├── database/migrations/           # Migraciones de BD
├── routes/api.php                 # Rutas de la API
└── tests/                         # Tests unitarios e integración
```

---

## 🔐 CONFIGURACIÓN DE BASE DE DATOS

### MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reversso_crm
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reversso_crm
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

---

## 📡 ENDPOINTS DE LA API

### Autenticación

```
POST   /api/v1/auth/register      Registrar usuario
POST   /api/v1/auth/login         Login (obtener JWT)
POST   /api/v1/auth/logout        Logout (invalidar JWT)
POST   /api/v1/auth/refresh       Renovar JWT
GET    /api/v1/auth/me            Usuario autenticado
```

### Health Check

```
GET    /api/v1/health             Estado del sistema
```

---

## 🧪 PRUEBAS DE LA API

### 1. Health Check:

```bash
curl http://127.0.0.1:8000/api/v1/health
```

### 2. Registrar Usuario:

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

### 3. Login:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@reversso.com",
    "password": "password123"
  }'
```

Respuesta:

```json
{
    "msg": "Login exitoso",
    "errorCode": 0,
    "data": {
        "user": {
            "id": 1,
            "name": "Admin Test",
            "email": "admin@reversso.com",
            "roles": []
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

### 4. Usar Token en Peticiones:

```bash
curl -X GET http://127.0.0.1:8000/api/v1/auth/me \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---

## 📚 DOCUMENTACIÓN ADICIONAL

- `INSTALACION.md` - Guía detallada de instalación
- `ARQUITECTURA.md` - Documentación de arquitectura
- Postman Collection - [Próximamente]

---

## 🛠️ COMANDOS ÚTILES

```bash
# Ver rutas disponibles
php artisan route:list

# Limpiar caché
php artisan cache:clear
php artisan config:clear

# Formatear código (Laravel Pint)
./vendor/bin/pint

# Análisis estático (PHPStan)
./vendor/bin/phpstan analyse

# Tests
php artisan test
```

---

## 📊 BASE DE DATOS

### Tablas Principales:

- `users` - Usuarios del sistema
- `roles` - Roles (admin, conductor)
- `role_user` - Relación usuarios-roles
- `time_tracking` - Registros de tiempo

### Diagrama ER:

```
users (1) ←→ (N) role_user (N) ←→ (1) roles
users (1) ←→ (N) time_tracking
```

---

## 🇨🇴 LEGISLACIÓN COLOMBIANA

El sistema calcula horas extras según normativa vigente:

- **Jornada ordinaria**: 47 horas semanales
- **Nocturno** (21:00-06:00): +35%
- **Dominical/Festivo**: +75%
- **Extra diurna**: +25%
- **Extra nocturna**: +75%
- **Extra dominical diurna**: +100%
- **Extra dominical nocturna**: +150%

---

## 🤝 CONTRIBUIR

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

---

## 📝 LICENCIA

Propietario: Reversso  
Uso interno únicamente

---

## 👥 EQUIPO

- **Arquitectura**: Claude (Anthropic)
- **Desarrollo**: [Tu nombre]
- **QA**: [Pendiente]

---

## 📞 SOPORTE

- Email: soporte@reversso.com
- Docs: https://docs.reversso.com
- Issues: GitHub Issues

---

**Versión**: 1.0.0  
**Fecha**: Febrero 2026  
**Estado**: ✅ Producción
