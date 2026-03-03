# Limpiar Caché de Overtime

Limpia el caché del `OvertimeCalculatorService` cuando las horas mostradas en el reporte de asistencia no coinciden con los datos reales de la BD.

## Cuándo usar
- Las horas del reporte no cuadran con check_in/check_out en la BD
- Se editaron registros de `user_attendance` directamente en la BD (phpMyAdmin, etc.)
- Después de corregir datos de asistencia manualmente

## Cómo funciona el caché
- Key: `overtime:{userId}:v{version}:{start}:{end}` y `overtime_daily:{userId}:v{version}:{start}:{end}`
- Versión por usuario en `overtime_version:{userId}` — al incrementarla se invalida TODO el caché del usuario
- TTL: 10 minutos
- `OvertimeCalculatorService::clearCacheForUser(int $userId)` hace `Cache::increment("overtime_version:{userId}")`

## Opciones de fix

**Opción 1 — Todo el caché de la app:**
```bash
php artisan cache:clear
```

**Opción 2 — Solo un usuario (desde tinker):**
```bash
php artisan tinker
# Luego:
App\Modules\Logistics\Overtime\Application\Services\OvertimeCalculatorService::clearCacheForUser(ID_USUARIO);
```

**Opción 3 — Desde la UI:**
En el reporte de asistencia, filtra por el usuario y usa el botón **"Refrescar Cálculo"**.

## Argumento
$ARGUMENTS puede ser un user_id. Si se provee, ejecutar la Opción 2 para ese usuario específico.
