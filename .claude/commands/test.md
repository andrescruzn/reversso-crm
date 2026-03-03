# Correr Tests

Ejecuta la suite de tests del proyecto Reversso CRM.

Argumento opcional: `$ARGUMENTS` = nombre del test o filtro (ej: `AttendanceTest`, `overtime`)

## Comandos

**Todos los tests:**
```bash
php artisan test
```

**Un test específico:**
```bash
php artisan test --filter=$ARGUMENTS
```

**Tests disponibles:**
- `AuthTest` — login web, logout, credenciales inválidas
- `UserManagementTest` — CRUD de usuarios (admin)
- `RoleMiddlewareTest` — acceso por rol
- `AttendanceTest` — check-in/check-out de conductores
- `TimeTrackingTest` — viajes (start/end trip)
- `ProfileTest` — edición de perfil
- `ExampleTest` — test base

## Nota sobre fallos conocidos
Los tests de `AttendanceTest` y algunos otros fallan con **419 CSRF** — esto es un problema **preexistente** en la configuración del entorno de tests, NO una regresión. No intentar "arreglarlos" sin confirmación del usuario.

## Después de cambios en rutas o middleware
```bash
php artisan route:clear && php artisan test
```
