<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (No requieren Token)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Requieren Token de Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Obtener la información del usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user()->load('profile');
    });

    // Cerrar sesión (destruye el token)
    Route::post('/logout', [AuthController::class, 'logout']);

    // Aquí agregaremos en el futuro:
    // Route::post('/reviews', [ReviewController::class, 'store']);
    // Route::post('/lists', [ListController::class, 'store']);
    // Route::post('/follows', [FollowController::class, 'store']);
});
