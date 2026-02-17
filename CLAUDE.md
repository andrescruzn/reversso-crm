# Reversso CRM

## Stack
- **Laravel 12** con estructura DDD modular (`app/Modules/`)
- **PHP 8.2+**
- Dual auth: Session (web) + JWT via `tymon/jwt-auth` (API)
- Tailwind CSS via CDN con config custom
- Base de datos: MySQL

## Arquitectura

### Estructura Modular
```
app/Modules/
├── Users/                          # Gestión de usuarios y autenticación
│   ├── Domain/                     # Interfaces (UserRepositoryInterface)
│   ├── Infrastructure/             # Modelos, Repositorios, Services
│   └── Presentation/Http/Controllers/
│       ├── AuthController.php      # Login web (session)
│       ├── ApiAuthController.php   # Login API (JWT)
│       ├── UserController.php      # CRUD usuarios (admin)
│       └── ProfileController.php   # Perfil (todos los roles)
│
└── Logistics/                      # Logística y control de conductores
    ├── TimeTracking/               # Viajes (modelo: TimeTracking, tabla: time_tracking)
    ├── Attendance/                  # Asistencia (modelo: UserAttendance, tabla: user_attendance)
    └── Overtime/                   # Horas extra (OvertimeCalculatorService)
```

### Patrones
- **Repository Pattern**: interfaces en `Domain/`, implementaciones en `Infrastructure/`, bindings en `AppServiceProvider`
- **ServiceResult**: retorna `ServiceResult::ok($data)` o `ServiceResult::fail('mensaje')` en vez de excepciones
- **Error Bags**: `startTrip`/`endTrip` para validación modal-specific

## Convenciones

### Roles
| DB `name` | DB `display_name` | Uso en UI |
|-----------|-------------------|-----------|
| admin | Administrador | Panel completo |
| conductor | Conductor | Panel conductor |

- Middleware verifica AMBOS `name` y `display_name` case-insensitively

### UI / Tailwind
- Color marca: `#E8960C` (amber/gold) → clases `.bg-reversso` / `.text-reversso`
- Palette `orange` está override completo en tailwind.config (tanto en layout como login)
- Sidebar: `bg-gray-950`, links activos con `bg-reversso`
- Mobile: `hidden md:block` para tabla desktop + `md:hidden` para cards mobile

### Modelo User
- Cast `'password' => 'hashed'` → **NUNCA** usar `Hash::make()` encima
- `assignRole()` es custom (usa `syncWithoutDetaching`)
- Campo `is_active` → siempre cast a bool

## Archivos Clave
| Archivo | Qué hace |
|---------|----------|
| `bootstrap/app.php` | Rutas API + handler TokenMismatchException |
| `routes/web.php` | Todas las rutas web |
| `resources/views/layouts/crm.blade.php` | Layout principal, sidebar, session expiration JS |
| `resources/views/auth/login.blade.php` | Login standalone (tiene su propio Tailwind config) |
| `config/overtime.php` | Límites semanales, horas nocturnas, recargos (ley colombiana) |

## Base de Datos

### Tablas principales
- `users` — usuarios con `is_active`, roles via pivot `role_user`
- `roles` — admin/conductor
- `time_tracking` — viajes (origin, destination, vehicle_plate, odometers, approval)
- `user_attendance` — check-in/check-out, índice compuesto `(user_id, check_in, check_out)`

### Sistema de Aprobación (time_tracking)
| Estado | `approved_by` | `approved_at` |
|--------|--------------|---------------|
| Pendiente | `NULL` | `NULL` |
| Aprobado | `> 0` (admin ID) | timestamp |
| Desaprobado | `0` | `NULL` |

## Performance
- `OvertimeCalculatorService`: algoritmo segment-based (no minuto a minuto)
- Cache: `Cache::remember()` con 10min TTL, key `overtime:{userId}:{start}:{end}`
- Cache se invalida en check-in/check-out via `clearCacheForUser()`

## Tests
```bash
php artisan test
```
- 41 tests, 106 assertions
- Archivos: AuthTest, UserManagementTest, RoleMiddlewareTest, AttendanceTest, TimeTrackingTest, ProfileTest, ExampleTest

## Deploy
- Repositorio en GitHub
- Hosting en Hostinger (deploy via SSH + `git pull origin main`)
- Después de deploy: `php artisan view:clear && php artisan cache:clear`

## Gotchas
- Campo login "remember" debe llamarse `remember` (no `remember-me`) para Laravel
- Export CSV debe usar `OvertimeCalculatorService` para horas exactas (no `diffInMinutes` raw)
- Todas las clases `orange-*` de Tailwind están override en ambos layouts
