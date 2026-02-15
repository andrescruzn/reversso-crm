<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\Users\Presentation\Http\Controllers\{AuthController, UserController, ProfileController};
use App\Modules\Logistics\Presentation\Http\Controllers\{
    DashboardController,
    TimeTrackingController,
    AttendanceController,
    LogisticsAdminController
};

Route::get('/', fn() => redirect()->route('login'));

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {

    /**
     * ==========================================================
     * DASHBOARD (según rol)
     * - Conductor: ve home con asistencia (crm.home)
     * - Administrador: ve home admin (crm.admin_home) SIN asistencia
     * ==========================================================
     */
    Route::get('/dashboard', function () {

        $user = auth()->user();

        // ✅ Conductor: home operativo con asistencia
        if ($user->hasRole('Conductor')) {
            return app(DashboardController::class)->index();
        }

        // ✅ Admin: home administrativo sin check-in/out
        if ($user->hasRole('Administrador')) {
            return view('crm.admin_home', [
                'user' => $user,
            ]);
        }

        // ✅ Cualquier otro rol: restringido
        abort(403);

    })->name('dashboard');

    Route::any('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==========================================================
    // MI PERFIL (todos los usuarios autenticados)
    // ==========================================================
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ==========================================================
    // MÓDULO: ASISTENCIA
    // ==========================================================
    Route::prefix('attendance')->name('attendance.')->group(function () {

        // ✅ Solo Conductor marca asistencia
        Route::middleware(['role:Conductor'])->group(function () {
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('checkin');
            Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('checkout');
        });

        // Reportes solo para Administrador
        Route::middleware(['role:Administrador'])->group(function () {
            Route::get('/report', [AttendanceController::class, 'index'])->name('report');
            Route::get('/export', [AttendanceController::class, 'export'])->name('export');
        });
    });

    // ==========================================================
    // MÓDULO: LOGÍSTICA
    // - Conductor: opera (start/end/history)
    // - Administrador: solo observa
    // ==========================================================
    Route::prefix('logistics')->name('logistics.')->group(function () {

        // Vista principal: cambia según rol
        Route::get('/', function (Request $request) {
            return auth()->user()->hasRole('Administrador')
                ? app(LogisticsAdminController::class)->index($request)
                : app(DashboardController::class)->logisticsModule($request);
        })->name('index');

        // ------------------------------------------
        // Acciones del CONDUCTOR (operativas)
        // ------------------------------------------
        Route::middleware(['role:Conductor'])->group(function () {
            Route::post('/start', [TimeTrackingController::class, 'start'])->name('start');
            Route::post('/end', [TimeTrackingController::class, 'end'])->name('end');
            Route::get('/history', [TimeTrackingController::class, 'history'])->name('history');
        });

        // ------------------------------------------
        // Acciones del ADMINISTRADOR (solo observar)
        // ------------------------------------------
        Route::middleware(['role:Administrador'])->group(function () {
            Route::get('/export/tracking', [LogisticsAdminController::class, 'exportTracking'])->name('export.tracking');
            Route::post('/approve/{id}', [LogisticsAdminController::class, 'approve'])->name('approve');
            Route::post('/disapprove/{id}', [LogisticsAdminController::class, 'disapprove'])->name('disapprove');
            Route::get('/trip/{id}', [LogisticsAdminController::class, 'showTrip'])->name('trip.show');
        });
    });

    // ==========================================================
    // MÓDULO: USUARIOS (Admin)
    // ==========================================================
    Route::middleware(['role:Administrador'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{id}/password', [UserController::class, 'editPassword'])->name('password.edit');
        Route::put('/{id}/password', [UserController::class, 'updatePassword'])->name('password.update');
    });
});
