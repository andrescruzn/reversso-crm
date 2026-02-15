<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Repositories\EloquentTimeTrackingRepository;
use App\Modules\Logistics\Attendance\Domain\Contracts\AttendanceRepositoryInterface;
use App\Modules\Logistics\Attendance\Infrastructure\Persistence\EloquentAttendanceRepository;
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

        // =====================================================================
        // BINDING: AttendanceRepository (para cálculo Colombia)
        // =====================================================================
        $this->app->bind(
            AttendanceRepositoryInterface::class,
            EloquentAttendanceRepository::class
        );
    }

    /**
     * Bootstrap de servicios.
     */
    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
