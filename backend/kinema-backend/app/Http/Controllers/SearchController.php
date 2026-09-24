<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieList;
use App\Models\Person;
use App\Models\Vibe;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public const TMDB_GENRES = [
        28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy',
        80 => 'Crime', 99 => 'Documentary', 18 => 'Drama', 10751 => 'Family',
        14 => 'Fantasy', 36 => 'History', 27 => 'Horror', 10402 => 'Music',
        9648 => 'Mystery', 10749 => 'Romance', 878 => 'Science Fiction',
        10770 => 'TV Movie', 53 => 'Thriller', 10752 => 'War', 37 => 'Western',
    ];

    /**
     * Catálogo de vibras con conteo (para los filtros de búsqueda).
     */
    public function vibes()
    {
        return response()->json(
            Vibe::withCount('movies')->orderByDesc('movies_count')->get()
        );
    }

    /**
     * Búsqueda unificada: películas, listas públicas y personas.
     * Filtros (solo películas): ?vibe=, ?year_from=, ?year_to=, ?genre=, ?person=
     * Las listas privadas nunca aparecen aquí.
     */
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        // filled() con arreglo no es fiable: chequeo explícito por filtro
        $hasFilters = $request->filled('vibe') || $request->filled('year_from')
            || $request->filled('year_to') || $request->filled('genre')
            || $request->filled('person');

        $movies = collect();
        if (mb_strlen($q) >= 2 || $hasFilters) {
            $movies = $this->searchMovies($request, $q);
        }

        $lists = collect();
        $people = collect();
        if (mb_strlen($q) >= 2) {
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
        }

        return response()->json([
            'movies' => $movies,
            'lists' => $lists,
            'people' => $people,
            'filters' => $request->only(['vibe', 'year_from', 'year_to', 'genre', 'person']),
        ]);
    }

    protected function searchMovies(Request $request, string $q)
    {
        $query = Movie::with('vibe:id,name')
            ->select('id', 'title', 'poster_path', 'release_date', 'release_year', 'vibe_id');
        if (mb_strlen($q) >= 2) {
            $query->where('title', 'ILIKE', "%{$q}%");
        }

        // Vibra exacta (nombre)
        if ($request->filled('vibe')) {
            $vibe = trim($request->query('vibe'));
            $query->whereHas('vibe', fn ($v) => $v->whereRaw('LOWER(name) = ?', [mb_strtolower($vibe)]));
        }

        // Rango de años (fecha real o año de importación)
        $from = $request->integer('year_from');
        $to = $request->integer('year_to');
        if ($from || $to) {
            $query->where(function ($w) use ($from, $to) {
                $w->where(function ($ww) use ($from, $to) {
                    if ($from) {
                        $ww->whereYear('release_date', '>=', $from);
                    }
                    if ($to) {
                        $ww->whereYear('release_date', '<=', $to);
                    }
                })->orWhere(function ($ww) use ($from, $to) {
                    $ww->whereNull('release_date');
                    if ($from) {
                        $ww->where('release_year', '>=', $from);
                    }
                    if ($to) {
                        $ww->where('release_year', '<=', $to);
                    }
                });
            });
        }

        // Género: matchea nombre ("Drama") o id TMDB ("18"), el catálogo mezcla ambos
        if ($request->filled('genre')) {
            $genre = trim($request->query('genre'));
            $id = array_search(mb_strtolower($genre), array_map('mb_strtolower', self::TMDB_GENRES), true);
            $query->where(function ($w) use ($genre, $id) {
                $w->where('genres', 'ILIKE', "%{$genre}%");
                if ($id !== false) {
                    $w->orWhereRaw("genres ~* ?", ['(^|,)\\s*' . $id . '\\s*(,|$)']);
                }
            });
        }

        // Persona (director/actor): películas donde aparece
        if ($request->filled('person')) {
            $person = trim($request->query('person'));
            $query->whereHas('credits.person', fn ($p) => $p->where('name', 'ILIKE', "%{$person}%"));
        }

        return $query->orderBy('release_date', 'desc')->take(24)->get();
    }
}
