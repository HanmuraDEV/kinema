<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Recomendaciones. Sin sesión devuelve tendencia (público);
     * con sesión personaliza por vibras y gustos (auth opcional).
     */
    public function index(Request $request)
    {
        $viewer = auth('sanctum')->user();

        if (!$viewer) {
            return response()->json([
                'personalized' => false,
                'items' => $this->trending(6),
            ]);
        }

        $liked = Review::with('movie:id,vibe_id,title,embedding')
            ->where('user_id', $viewer->id)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->take(10)
            ->get();

        if ($liked->isEmpty()) {
            return response()->json([
                'personalized' => false,
                'items' => $this->trending(6),
            ]);
        }

        $reviewedIds = Review::where('user_id', $viewer->id)->pluck('movie_id')->toArray();

        // Vibras favoritas (top 2 por frecuencia entre sus 4-5 estrellas)
        $topVibes = $liked->pluck('movie.vibe_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(2)
            ->keys()
            ->toArray();

        $items = collect();

        // 1) Similares por vector a sus 3 mejor valoradas (con embedding)
        foreach ($liked->take(3) as $review) {
            $movie = $review->movie;
            if (!$movie || !$movie->embedding) {
                continue;
            }
            $similar = Movie::select('id', 'title', 'poster_path', 'release_date', 'release_year', 'vibe_id')
                ->with('vibe:id,name')
                ->where('id', '!=', $movie->id)
                ->whereNotIn('id', $reviewedIds)
                ->orderByRaw('embedding <=> ?', [$movie->embedding])
                ->take(3)
                ->get()
                ->each(fn ($m) => $m->reason = "Porque te gustó {$movie->title}");
            $items = $items->concat($similar);
        }

        // 2) Más de sus vibras favoritas (con poster, no vistas)
        if (!empty($topVibes)) {
            $byVibe = Movie::select('id', 'title', 'poster_path', 'release_date', 'release_year', 'vibe_id')
                ->with('vibe:id,name')
                ->whereIn('vibe_id', $topVibes)
                ->whereNotNull('poster_path')
                ->whereNotIn('id', $reviewedIds)
                ->whereNotIn('id', $items->pluck('id')->toArray())
                ->orderByDesc('release_date')
                ->take(8)
                ->get()
                ->each(function ($m) {
                    $m->reason = $m->vibe ? "Más {$m->vibe->name}" : 'Basado en tu perfil';
                });
            $items = $items->concat($byVibe);
        }

        $items = $items->unique('id')->take(8)->values();

        // 3) Relleno con tendencia si quedó corto
        if ($items->count() < 6) {
            $filler = collect($this->trending(6 - $items->count()))
                ->reject(fn ($m) => $items->pluck('id')->contains($m['id'] ?? $m->id)
                    || in_array($m['id'] ?? $m->id, $reviewedIds));
            $items = $items->concat($filler)->take(8)->values();
        }

        return response()->json([
            'personalized' => true,
            'items' => $items,
        ]);
    }

    /**
     * Tendencia: lo más reseñado por la comunidad (público).
     */
    protected function trending(int $limit): array
    {
        return Movie::select('id', 'title', 'poster_path', 'release_date', 'release_year', 'vibe_id')
            ->with('vibe:id,name')
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->take($limit)
            ->get()
            ->each(fn ($m) => $m->reason = $m->reviews_count > 0
                ? "Tendencia ({$m->reviews_count} reseñas)"
                : 'Del catálogo')
            ->toArray();
    }
}
