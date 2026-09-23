<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieList;
use App\Models\Person;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Búsqueda unificada: películas, listas públicas y personas.
     * Las listas privadas nunca aparecen aquí.
     */
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['movies' => [], 'lists' => [], 'people' => []]);
        }

        $movies = Movie::with('vibe:id,name')
            ->select('id', 'title', 'poster_path', 'release_date', 'release_year', 'vibe_id')
            ->where('title', 'ILIKE', "%{$q}%")
            ->orderBy('release_date', 'desc')
            ->take(12)
            ->get();

        $lists = MovieList::with('user:id,name')
            ->withCount('items')
            ->where('is_public', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'ILIKE', "%{$q}%")
                    ->orWhere('description', 'ILIKE', "%{$q}%");
            })
            ->latest()
            ->take(6)
            ->get();

        $people = Person::select('id', 'name', 'profile_path', 'known_for_department')
            ->where('name', 'ILIKE', "%{$q}%")
            ->orderBy('name')
            ->take(6)
            ->get();

        return response()->json([
            'movies' => $movies,
            'lists' => $lists,
            'people' => $people,
        ]);
    }
}
