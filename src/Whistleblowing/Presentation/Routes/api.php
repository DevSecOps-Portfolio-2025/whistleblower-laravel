<?php

use Illuminate\Support\Facades\Route;
use Src\Whistleblowing\Presentation\Http\Controllers\WhistleblowerController;
use Src\Whistleblowing\Presentation\Http\Controllers\KeyController;

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
    // Endpoint de llave pública para cifrado de reportes
    Route::get('/keys/public', [KeyController::class, 'getPublicKey']);
    
    // Endpoints de reportes
    Route::get('/reports', [WhistleblowerController::class, 'index']);
    Route::post('/reports', [WhistleblowerController::class, 'store']);
    Route::get('/reports/{id}', [WhistleblowerController::class, 'show']);
    Route::put('/reports/{id}', [WhistleblowerController::class, 'update']);
    Route::delete('/reports/{id}', [WhistleblowerController::class, 'destroy']);
});
