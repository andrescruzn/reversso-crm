#!/bin/bash

echo "========================================"
echo "REVERSSO CRM - Instalación Automática"
echo "========================================"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "ERROR: Este script debe ejecutarse desde la raíz del proyecto Laravel"
    exit 1
fi

# Paso 1: Instalar dependencias
echo "[1/7] Instalando dependencias de Composer..."
composer install
if [ $? -ne 0 ]; then
    echo "ERROR: Falló la instalación de Composer"
    exit 1
fi
echo ""

# Paso 2: Instalar JWT
echo "[2/7] Instalando JWT Auth..."
composer require tymon/jwt-auth
if [ $? -ne 0 ]; then
    echo "ERROR: Falló la instalación de JWT"
    exit 1
fi
echo ""

# Paso 3: Copiar .env
echo "[3/7] Configurando archivo .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi
echo ""

# Paso 4: Generar claves
echo "[4/7] Generando claves de la aplicación..."
php artisan key:generate
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
echo ""

# Paso 5: Ejecutar migraciones
echo "[5/7] Ejecutando migraciones..."
php artisan migrate
if [ $? -ne 0 ]; then
    echo "ERROR: Falló la migración de la base de datos"
    echo "Verifica la configuración de .env"
    exit 1
fi
echo ""

# Paso 6: Ejecutar seeders
echo "[6/7] Creando datos iniciales..."
php artisan db:seed --class=RoleSeeder
echo ""

# Paso 7: Limpiar cache
echo "[7/7] Limpiando cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo ""

echo "========================================"
echo "¡INSTALACIÓN COMPLETADA CON ÉXITO!"
echo "========================================"
echo ""
echo "Puedes iniciar el servidor con:"
echo "  php artisan serve"
echo ""
echo "Y probar la API en:"
echo "  http://127.0.0.1:8000/api/v1/health"
echo ""
