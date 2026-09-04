<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MovieListController;

// Rutas Públicas (Cualquiera puede verlas)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/similar', [MovieController::class, 'similar']);
Route::get('/profiles/{id}', [ProfileController::class, 'show']);
Route::get('/lists/{id}', [MovieListController::class, 'show']);

// Rutas Protegidas (El muro de seguridad)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user()->load('profile');
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Ruta protegida de reseñas
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/profiles/update', [ProfileController::class, 'update']);
    // Adentro del grupo de middleware('auth:sanctum') añade:
    Route::post('/lists', [MovieListController::class, 'store']);
    Route::post('/lists/{id}/items', [MovieListController::class, 'addItem']);
});
