<?php

declare(strict_types=1);

use App\Modules\Logistics\TimeTracking\Presentation\Http\Controllers\TimeTrackingController;
use App\Modules\Users\Presentation\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// API v1
// =========================================================================

Route::prefix('v1')->group(function () {

    // =====================================================================
    // AUTENTICACIÓN (Sin protección JWT)
    // =====================================================================

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

        // Rutas protegidas
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        });
    });

    // =====================================================================
    // TIME TRACKING
    // =====================================================================

    Route::middleware('auth:api')->prefix('time-tracking')->group(function () {

        // Rutas de conductores
        Route::middleware('role:conductor')->group(function () {
            Route::post('/start', [TimeTrackingController::class, 'start'])
                ->name('tracking.start');
            Route::post('/end', [TimeTrackingController::class, 'end'])
                ->name('tracking.end');
            Route::get('/active', [TimeTrackingController::class, 'active'])
                ->name('tracking.active');
            Route::get('/my-history', [TimeTrackingController::class, 'myHistory'])
                ->name('tracking.my-history');
        });

        // Rutas de administradores
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [TimeTrackingController::class, 'index'])
                ->name('tracking.index');
            Route::get('/{id}', [TimeTrackingController::class, 'show'])
                ->name('tracking.show');
            Route::post('/{id}/approve', [TimeTrackingController::class, 'approve'])
                ->name('tracking.approve');
            Route::post('/{id}/unapprove', [TimeTrackingController::class, 'unapprove'])
                ->name('tracking.unapprove');
            Route::put('/{id}', [TimeTrackingController::class, 'update'])
                ->name('tracking.update');
            Route::delete('/{id}', [TimeTrackingController::class, 'destroy'])
                ->name('tracking.destroy');
        });
    });

    // =====================================================================
    // HEALTH CHECK
    // =====================================================================

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'REVERSSO CRM API',
            'version' => 'v1.0.0',
        ]);
    })->name('health');
});
