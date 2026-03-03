# Verificar Estado de BD

Inspecciona el estado actual de la base de datos para diagnosticar problemas de datos.

Argumento opcional: `$ARGUMENTS` = user_id, tabla, o descripción del problema

## Queries útiles via tinker

```bash
php artisan tinker
```

**Jornadas activas (sin check_out):**
```php
App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance::whereNull('check_out')->with('user')->get(['id','user_id','check_in','status']);
```

**Viajes activos (sin end_time):**
```php
// Buscar en time_tracking viajes sin end_time
DB::table('time_tracking')->whereNull('end_time')->get();
```

**Registros de asistencia de un usuario:**
```php
$userId = $ARGUMENTS; // reemplazar con el ID
App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance::where('user_id', $userId)->orderBy('check_in','desc')->take(10)->get(['id','check_in','check_out','is_holiday','status']);
```

**Roles de un usuario:**
```php
App\Modules\Users\Infrastructure\Models\User::find($userId)->roles;
```

**Time tracking de un conductor:**
```php
DB::table('time_tracking')->where('user_id', $userId)->orderBy('start_time','desc')->take(5)->get();
```

## Tablas principales
| Tabla | Modelo | Descripción |
|-------|--------|-------------|
| `users` | `User` | Usuarios con `is_active`, roles via `role_user` |
| `roles` | `Role` | admin / conductor |
| `user_attendance` | `UserAttendance` | check-in/check-out, is_holiday |
| `time_tracking` | `TimeTracking` | viajes con origin, destination, odómetros |

## Sistema de aprobación (time_tracking)
- `approved_by = NULL` + `approved_at = NULL` → **Pendiente**
- `approved_by > 0` + `approved_at = timestamp` → **Aprobado**
- `approved_by = 0` + `approved_at = NULL` → **Desaprobado**
