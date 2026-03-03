# Deploy a Hostinger

Ejecuta el flujo completo de deploy del CRM Reversso a producción (Hostinger vía SSH).

## Pasos

1. Verifica que no hay cambios sin commitear localmente (`git status`)
2. Confirma con el usuario antes de hacer push
3. Hace push a `origin main`
4. Conecta por SSH a Hostinger y ejecuta:
   - `git pull origin main`
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan view:clear`
   - `php artisan cache:clear`
   - `php artisan config:clear`
5. Reporta el resultado

## Contexto del proyecto
- Branch principal: `main`
- Post-deploy SIEMPRE limpiar vistas y caché
- Si hay migraciones nuevas también correr: `php artisan migrate --force`

Argumentos opcionales: $ARGUMENTS (ej: `--with-migrate` para incluir migrate)

Antes de hacer cualquier push, muestra al usuario los commits que se van a enviar y pide confirmación explícita.
