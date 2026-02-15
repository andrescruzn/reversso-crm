<?php

declare(strict_types=1);

use App\Modules\Logistics\TimeTracking\Presentation\Http\Controllers\TimeTrackingController;
use App\Modules\Users\Presentation\Http\Controllers\ApiAuthController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// API v1
// =========================================================================

Route::prefix('v1')->group(function () {

    // =====================================================================
    // AUTENTICACION JWT
    // =====================================================================

    Route::prefix('auth')->group(function () {
        Route::post('/register', [ApiAuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [ApiAuthController::class, 'login'])->name('auth.login');

        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [ApiAuthController::class, 'logout'])->name('auth.logout');
            Route::post('/refresh', [ApiAuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('/me', [ApiAuthController::class, 'me'])->name('auth.me');
        });
    });

    // =====================================================================
    // TIME TRACKING
    // =====================================================================

    Route::middleware('auth:api')->prefix('time-tracking')->group(function () {

        // Rutas de conductores
        Route::middleware('role:conductor')->group(function () {
            Route::post('/start', [TimeTrackingController::class, 'start'])->name('tracking.start');
            Route::post('/end', [TimeTrackingController::class, 'end'])->name('tracking.end');
            Route::get('/active', [TimeTrackingController::class, 'active'])->name('tracking.active');
            Route::get('/my-history', [TimeTrackingController::class, 'myHistory'])->name('tracking.my-history');
        });

        // Rutas de administradores
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [TimeTrackingController::class, 'index'])->name('tracking.index');
            Route::get('/{id}', [TimeTrackingController::class, 'show'])->name('tracking.show');
            Route::post('/{id}/approve', [TimeTrackingController::class, 'approve'])->name('tracking.approve');
            Route::post('/{id}/unapprove', [TimeTrackingController::class, 'unapprove'])->name('tracking.unapprove');
            Route::put('/{id}', [TimeTrackingController::class, 'update'])->name('tracking.update');
            Route::delete('/{id}', [TimeTrackingController::class, 'destroy'])->name('tracking.destroy');
        });
    });

    // =====================================================================
    // HEALTH CHECK
    // =====================================================================

    Route::get('/health', function () {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'REVERSSO CRM API',
            'version'   => 'v1.0.0',
        ]);
    })->name('health');
});
