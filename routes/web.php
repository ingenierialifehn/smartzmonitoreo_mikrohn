<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoringApiController;
use Illuminate\Support\Facades\Route;

// Rutas de Invitados (Autenticación)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Rutas Protegidas (NOC Monitoreo)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Endpoints Asíncronos de Monitoreo en Tiempo Real (Autenticados vía sesión Web)
    Route::prefix('api/monitoring')->name('monitoring.')->group(function () {
        Route::get('/routers', [MonitoringApiController::class, 'routers'])->name('routers');
        Route::get('/{router}/resources', [MonitoringApiController::class, 'resources'])->name('resources');
        Route::get('/{router}/interfaces', [MonitoringApiController::class, 'interfaces'])->name('interfaces');
        Route::get('/{router}/traffic', [MonitoringApiController::class, 'traffic'])->name('traffic');
        Route::get('/{router}/logs', [MonitoringApiController::class, 'logs'])->name('logs');
        Route::get('/{router}/services', [MonitoringApiController::class, 'services'])->name('services');
        Route::get('/{router}/ping', [MonitoringApiController::class, 'ping'])->name('ping');
    });
});
