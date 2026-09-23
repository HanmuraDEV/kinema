<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MovieListController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\RecommendationController;

// Rutas Públicas (Cualquiera puede verlas)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/search', [SearchController::class, 'index']);
Route::get('/recommendations', [RecommendationController::class, 'index']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/similar', [MovieController::class, 'similar']);
Route::get('/movies/{id}/credits', [MovieController::class, 'credits']);
Route::get('/people/search', [PersonController::class, 'search']);
Route::get('/people/{id}', [PersonController::class, 'show']);
Route::get('/movies/{movieId}/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);
Route::get('/users/{id}/reviews', [ReviewController::class, 'byUser']);
Route::get('/reviews/{id}/comments', [CommentController::class, 'index']);
Route::get('/users/{username}/lists/{slug}', [MovieListController::class, 'showBySlug']);
Route::get('/profiles/{id}', [ProfileController::class, 'show']);
Route::get('/profiles/username/{username}', [ProfileController::class, 'showByUsername']);
Route::get('/users/{id}/lists', [MovieListController::class, 'index']);
Route::get('/users/{id}/followers', [FollowController::class, 'followers']);
Route::get('/users/{id}/following', [FollowController::class, 'following']);
Route::get('/lists/{id}', [MovieListController::class, 'show']);
Route::get('/movies/{id}/sentiment', [AnalyticsController::class, 'movieSentiment']);
Route::get('/analytics/activity', [AnalyticsController::class, 'activity']);
Route::get('/analytics/taste', [AnalyticsController::class, 'taste']);
Route::get('/analytics/map', [AnalyticsController::class, 'map']);
Route::get('/analytics/genres', [AnalyticsController::class, 'genres']);


// Rutas Protegidas (El muro de seguridad)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user()->load('profile');
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Reseñas: crear / editar / borrar (solo dueño)
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews', [ReviewController::class, 'indexAll']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Comentarios sobre reseñas (crear: auth; editar/borrar: solo dueño)
    Route::post('/reviews/{id}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    Route::put('/profiles/update', [ProfileController::class, 'update']);

    // Listas
    Route::post('/lists', [MovieListController::class, 'store']);
    Route::put('/lists/{id}', [MovieListController::class, 'update']);
    Route::delete('/lists/{id}', [MovieListController::class, 'destroy']);
    Route::post('/lists/{id}/items', [MovieListController::class, 'addItem']);
    Route::delete('/lists/{id}/items/{movieId}', [MovieListController::class, 'removeItem']);

    // Sistema Social
    Route::post('/users/{id}/follow', [FollowController::class, 'toggle']);
    Route::get('/users/{id}/follow-status', [FollowController::class, 'status']);
    Route::get('/feed', [FeedController::class, 'index']);

    // Importar ZIP de Letterboxd al historial propio
    Route::post('/import/letterboxd', [ImportController::class, 'letterboxd']);
});
