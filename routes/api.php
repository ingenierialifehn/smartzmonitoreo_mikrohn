<?php

use App\Http\Controllers\MonitoringApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('monitoring')->group(function () {
    Route::get('/routers', [MonitoringApiController::class, 'routers']);
    Route::get('/{router}/resources', [MonitoringApiController::class, 'resources']);
    Route::get('/{router}/interfaces', [MonitoringApiController::class, 'interfaces']);
    Route::get('/{router}/traffic', [MonitoringApiController::class, 'traffic']);
    Route::get('/{router}/logs', [MonitoringApiController::class, 'logs']);
    Route::get('/{router}/services', [MonitoringApiController::class, 'services']);
    Route::get('/{router}/ping', [MonitoringApiController::class, 'ping']);
});
