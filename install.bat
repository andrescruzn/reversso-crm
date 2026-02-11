@echo off
echo ========================================
echo REVERSSO CRM - Instalacion Automatica
echo ========================================
echo.

:: Verificar que estamos en el directorio correcto
if not exist "artisan" (
    echo ERROR: Este script debe ejecutarse desde la raiz del proyecto Laravel
    pause
    exit /b 1
)

:: Paso 1: Instalar dependencias
echo [1/7] Instalando dependencias de Composer...
call composer install
if errorlevel 1 (
    echo ERROR: Fallo la instalacion de Composer
    pause
    exit /b 1
)
echo.

:: Paso 2: Instalar JWT
echo [2/7] Instalando JWT Auth...
call composer require tymon/jwt-auth
if errorlevel 1 (
    echo ERROR: Fallo la instalacion de JWT
    pause
    exit /b 1
)
echo.

:: Paso 3: Copiar .env
echo [3/7] Configurando archivo .env...
if not exist ".env" (
    copy .env.example .env
)
echo.

:: Paso 4: Generar claves
echo [4/7] Generando claves de la aplicacion...
call php artisan key:generate
call php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
call php artisan jwt:secret
echo.

:: Paso 5: Ejecutar migraciones
echo [5/7] Ejecutando migraciones...
call php artisan migrate
if errorlevel 1 (
    echo ERROR: Fallo la migracion de la base de datos
    echo Verifica la configuracion de .env
    pause
    exit /b 1
)
echo.

:: Paso 6: Ejecutar seeders
echo [6/7] Creando datos iniciales...
call php artisan db:seed --class=RoleSeeder
echo.

:: Paso 7: Limpiar cache
echo [7/7] Limpiando cache...
call php artisan cache:clear
call php artisan config:clear
call php artisan route:clear
echo.

echo ========================================
echo INSTALACION COMPLETADA CON EXITO!
echo ========================================
echo.
echo Puedes iniciar el servidor con:
echo   php artisan serve
echo.
echo Y probar la API en:
echo   http://127.0.0.1:8000/api/v1/health
echo.
pause
