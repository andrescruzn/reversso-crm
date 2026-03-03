# Debug Overtime / Asistencia

Diagnostica por qué las horas de un usuario no cuadran en el reporte de asistencia.

Argumento esperado: `$ARGUMENTS` = user_id o nombre del usuario

## Pasos de diagnóstico

1. **Identificar el usuario** a partir de $ARGUMENTS (buscar en tabla `users` si es nombre)

2. **Revisar registros en BD** — ejecutar estas queries mentalmente o con tinker:
   ```sql
   -- Registros de asistencia del mes actual
   SELECT id, user_id, check_in, check_out, is_holiday, status, deleted_at
   FROM user_attendance
   WHERE user_id = ? AND check_in >= DATE_FORMAT(NOW(), '%Y-%m-01')
   ORDER BY check_in;

   -- ¿Hay registros con check_out NULL (jornadas abiertas)?
   SELECT * FROM user_attendance WHERE user_id = ? AND check_out IS NULL;

   -- ¿Hay soft-deletes que podrían confundir?
   SELECT * FROM user_attendance WHERE user_id = ? AND deleted_at IS NOT NULL;
   ```

3. **Verificar el caché** — el caché puede estar stale si los records fueron editados directamente en BD:
   - Fix: `OvertimeCalculatorService::clearCacheForUser(userId)` o botón "Refrescar Cálculo"

4. **Trazar el cálculo esperado** para el día problemático:
   - Duración real = check_out - check_in (en minutos)
   - Si > 6h: restar 60 min de almuerzo
   - Si es Domingo o is_holiday=1: TODAS las horas van a holiday_day/holiday_night
   - Si es Lunes–Sábado: primeras 8h de trabajo (9h reloj con almuerzo) = regular; resto = overtime
   - Nocturno 19:00–06:00: recargo 35%

5. **Comparar** lo calculado manualmente vs lo mostrado en UI

## Archivos clave para inspeccionar
- `app/Modules/Logistics/Overtime/Application/Services/OvertimeCalculatorService.php`
- `app/Modules/Logistics/Presentation/Http/Controllers/AttendanceController.php`
- `config/overtime.php`
