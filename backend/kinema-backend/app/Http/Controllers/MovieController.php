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

    // 2. Detalle de una sola película
    public function show($id)
    {
        // fail() devuelve automáticamente un error 404 si el ID no existe
        $movie = Movie::with('vibe')->findOrFail($id);

        return response()->json($movie);
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
