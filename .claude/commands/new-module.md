# Crear Nuevo Módulo DDD

Crea la estructura completa de un nuevo módulo siguiendo la arquitectura DDD del proyecto.

Argumento: `$ARGUMENTS` = nombre del módulo (ej: `Payments`, `Reports`, `Fleet`)

## Estructura a crear

```
app/Modules/{ModuleName}/
├── Domain/
│   ├── Contracts/
│   │   └── {ModuleName}RepositoryInterface.php
│   └── Models/  (o en Infrastructure)
├── Infrastructure/
│   ├── Models/
│   │   └── {ModelName}.php
│   └── Persistence/
│       └── Eloquent{ModuleName}Repository.php
└── Presentation/
    └── Http/
        └── Controllers/
            └── {ModuleName}Controller.php
```

## Pasos

1. Crear la interfaz del repositorio en `Domain/Contracts/`
2. Crear el modelo Eloquent en `Infrastructure/Models/` con:
   - `protected $table`
   - `protected $fillable`
   - `protected $casts`
   - SoftDeletes si aplica
3. Crear la implementación del repositorio en `Infrastructure/Persistence/`
4. Crear el controlador en `Presentation/Http/Controllers/` retornando `ServiceResult`
5. Registrar el binding en `app/Providers/AppServiceProvider.php`:
   ```php
   $this->app->bind(
       \App\Modules\{ModuleName}\Domain\Contracts\{ModuleName}RepositoryInterface::class,
       \App\Modules\{ModuleName}\Infrastructure\Persistence\Eloquent{ModuleName}Repository::class,
   );
   ```
6. Agregar rutas en `routes/web.php` dentro del grupo `auth` correspondiente
7. Crear la migración: `php artisan make:migration create_{table_name}_table`
8. Crear vistas en `resources/views/modules/{module-name}/`

## Convenciones a seguir
- Controladores con `declare(strict_types=1)` y `final class`
- Retornar `ServiceResult::ok($data)` o `ServiceResult::fail('mensaje')`
- Roles: middleware `role:Administrador` o `role:Conductor`
- UI: usar clases `.bg-reversso` / `.text-reversso` para color marca
