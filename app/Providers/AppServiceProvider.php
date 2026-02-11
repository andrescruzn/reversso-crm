<?php

declare(strict_types=1);

namespace App\Providers;

use App\Common\Http\Middleware\RoleMiddleware;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Repositories\EloquentTimeTrackingRepository;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

/**
 * Service Provider de la aplicación.
 *
 * RESPONSABILIDAD:
 * Registrar bindings de dependencias y middleware.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios.
     */
    public function register(): void
    {
        // =====================================================================
        // BINDINGS DE REPOSITORIOS (SOLID: Inversión de Dependencia)
        // =====================================================================
        $this->app->bind(
            TimeTrackingRepositoryInterface::class,
            EloquentTimeTrackingRepository::class
        );
    }

    /**
     * Bootstrap de servicios.
     */
    public function boot(Router $router): void
    {
        // =====================================================================
        // REGISTRAR MIDDLEWARE DE ROLES
        // =====================================================================
        $router->aliasMiddleware('role', RoleMiddleware::class);

        // =====================================================================
        // CONFIGURAR PAGINACIÓN PARA TAILWIND
        // =====================================================================
        Paginator::useTailwind();
    }
}
