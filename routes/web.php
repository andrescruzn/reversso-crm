<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\Users\Presentation\Http\Controllers\{AuthController, UserController};
use App\Modules\Logistics\Presentation\Http\Controllers\{
    DashboardController,
    TimeTrackingController,
    AttendanceController,
    LogisticsAdminController // <--- Asegúrate que este esté importado
};

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CORRECCIÓN 1: Logout ahora acepta GET y POST para que no te de error 405
    Route::any('/logout', [AuthController::class, 'logout'])->name('logout');

    // MÓDULO: ASISTENCIA
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('checkin');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('checkout');

        Route::middleware(['role:Administrador'])->group(function () {
            Route::get('/report', [AttendanceController::class, 'index'])->name('report');
            Route::get('/export', [AttendanceController::class, 'export'])->name('export');
        });
    });

    // MÓDULO: LOGÍSTICA
    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/', function (Request $request) {
            return auth()->user()->hasRole('Administrador')
                ? app(LogisticsAdminController::class)->index($request)
                : app(DashboardController::class)->logisticsModule($request);
        })->name('index');

        // Acciones del Conductor
        Route::post('/start', [TimeTrackingController::class, 'start'])->name('start');
        Route::post('/end', [TimeTrackingController::class, 'end'])->name('end');
        Route::get('/history', [TimeTrackingController::class, 'history'])->name('history');

        // Acciones del Administrador
        Route::middleware(['role:Administrador'])->group(function () {

            // CORRECCIÓN 2: Apuntar al controlador correcto (LogisticsAdminController)
            Route::get('/export/tracking', [LogisticsAdminController::class, 'exportTracking'])->name('export.tracking');

            // AQUÍ ESTABA EL ERROR: Antes decía TimeTrackingController
            Route::post('/approve/{id}', [LogisticsAdminController::class, 'approve'])->name('approve');

            Route::post('/disapprove/{id}', [LogisticsAdminController::class, 'disapprove'])->name('disapprove');

            Route::get('/trip/{id}', [LogisticsAdminController::class, 'showTrip'])->name('trip.show');
        });
    });

    // MÓDULO: USUARIOS
    Route::middleware(['role:Administrador'])->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
        });
    });
});
