<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\MovieList;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        // 1. Extraer en un arreglo (array) los IDs de todas las personas a las que sigues
        $followingIds = $request->user()->followings()->pluck('users.id')->toArray();

        // Si tu cuenta es nueva y no sigues a nadie, devolvemos un feed vacío
        if (empty($followingIds)) {
            return response()->json([]);
        }

        // 2. Traer las últimas 20 reseñas de tus amigos (incluyendo qué película y qué usuario fue)
        $reviews = Review::with(['user:id,name', 'movie:id,title,poster_path'])
            ->whereIn('user_id', $followingIds)
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($review) {
                // Inyectamos una bandera para que el Frontend sepa que renderizar
                $review->feed_type = 'review';
                return $review;
            });

        // 3. Traer las últimas 20 listas PÚBLICAS de tus amigos
        $lists = MovieList::with(['user:id,name'])
            ->whereIn('user_id', $followingIds)
            ->where('is_public', true)
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($list) {
                $list->feed_type = 'list';
                return $list;
            });

        // 4. La magia de las Colecciones: Unimos ambas consultas, las ordenamos
        // de la más reciente a la más antigua, y tomamos las 20 principales globales.
        $feed = $reviews->concat($lists)
            ->sortByDesc('created_at')
            ->values() // Resetea los índices del array para que sea un JSON limpio
            ->take(20);

        return response()->json($feed);
    }
}
