<?php

use Illuminate\Support\Facades\Route;
use Src\Whistleblowing\Presentation\Http\Controllers\WhistleblowerController;

/*
|--------------------------------------------------------------------------
| Whistleblowing API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas del módulo Whistleblowing
| Estas rutas están bajo el prefijo 'api/v1' definido en el ServiceProvider
|
*/

// Ruta de prueba para verificar que el módulo funciona
Route::get('/whistleblowing/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Whistleblowing module is working',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Rutas del módulo Whistleblowing
Route::prefix('whistleblowing')->group(function () {
    Route::get('/reports', [WhistleblowerController::class, 'index']);
    Route::post('/reports', [WhistleblowerController::class, 'store']);
    Route::get('/reports/{id}', [WhistleblowerController::class, 'show']);
    Route::put('/reports/{id}', [WhistleblowerController::class, 'update']);
    Route::delete('/reports/{id}', [WhistleblowerController::class, 'destroy']);
});
