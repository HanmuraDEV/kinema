<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // 1. Catálogo General Paginado
    public function index(Request $request)
    {
        // Traemos las películas, incluyendo el nombre de su cluster/vibra
        // Paginamos de 20 en 20 para no saturar la red
        $movies = Movie::with('vibe:id,name')
            ->select('id', 'title', 'poster_path', 'release_date', 'vibe_id')
            ->orderBy('release_date', 'desc')
            ->paginate(20);

        return response()->json($movies);
    }

    // 2b. Búsqueda por título (para search.astro + añadir a listas)
    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $movies = Movie::with('vibe:id,name')
            ->select('id', 'title', 'poster_path', 'release_date', 'vibe_id')
            ->where('title', 'ILIKE', "%{$q}%")
            ->orderBy('release_date', 'desc')
            ->take(20)
            ->get();

        return response()->json($movies);
    }

    // 2. Detalle de una sola película (+ stats reales de la comunidad)
    public function show($id)
    {
        // fail() devuelve automáticamente un error 404 si el ID no existe
        $movie = Movie::with('vibe')->findOrFail($id);
        $movie->rating_stats = $this->ratingStats((int) $id);

        return response()->json($movie);
    }

    // Distribución de calificaciones 0.5-5.0 para el histograma del frontend
    protected function ratingStats(int $movieId): array
    {
        $buckets = [];
        for ($i = 1; $i <= 10; $i++) {
            $buckets[number_format($i * 0.5, 1)] = 0;
        }

        $ratings = \App\Models\Review::where('movie_id', $movieId)
            ->whereNotNull('rating')
            ->pluck('rating');

        foreach ($ratings as $rating) {
            $key = number_format((float) $rating, 1);
            if (array_key_exists($key, $buckets)) {
                $buckets[$key]++;
            }
        }

        $max = max($buckets) ?: 1;
        $bars = [];
        foreach ($buckets as $label => $count) {
            $bars[] = [
                'label' => "{$label} stars",
                'height' => round($count / $max * 100) . '%',
                'count' => $count,
            ];
        }

        return [
            'average' => $ratings->count() ? round($ratings->avg(), 1) : null,
            'count' => $ratings->count(),
            'bars' => $bars,
        ];
    }

    // Reparto principal + dirección (para la ficha de película)
    public function credits($id)
    {
        $movie = Movie::findOrFail($id);

        $cast = $movie->credits()
            ->with('person:id,name,profile_path')
            ->where('department', 'Acting')
            ->orderBy('order')
            ->take(15)
            ->get();

        $directors = $movie->credits()
            ->with('person:id,name,profile_path')
            ->where('job', 'Director')
            ->get();

        return response()->json([
            'cast' => $cast,
            'directors' => $directors,
        ]);
    }

    // 3. El Motor de Recomendaciones (Magia de pgvector)
    public function similar($id)
    {
        $movie = Movie::findOrFail($id);

        // Si la película no tiene vector por alguna razón, devolvemos un array vacío
        if (!$movie->embedding) {
            return response()->json([]);
        }

        /*
         * ¿Cómo funciona esto?
         * El operador <=> de pgvector calcula la "Distancia Coseno" entre dos vectores.
         * Es la medida estándar en Inteligencia Artificial para saber qué tan
         * "semánticamente parecidos" son dos textos o entidades.
         * Ordenamos de menor a mayor distancia (los más cercanos primero) y tomamos 5.
         */
        $similarMovies = Movie::select('id', 'title', 'poster_path', 'release_date')
            ->where('id', '!=', $movie->id)
            ->orderByRaw('embedding <=> ?', [$movie->embedding])
            ->take(5)
            ->get();

        return response()->json($similarMovies);
    }
}
