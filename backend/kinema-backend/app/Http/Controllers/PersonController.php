<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\TmdbService;

class PersonController extends Controller
{
    // Ficha de artista con filmografía (para artists/[artist]/profile)
    public function show($id, TmdbService $tmdb)
    {
        $person = Person::findOrFail($id);

        // Bio bajo demanda: si TMDB la tiene y aquí falta, se rellena
        if (empty($person->biography) && $person->tmdb_id) {
            $tmdb->personDetail($person);
            $person->refresh();
        }

        $filmography = $person->credits()
            ->with(['movie' => fn ($q) => $q->select(
                'id', 'title', 'poster_path', 'release_date', 'release_year', 'genres'
            )->withAvg('reviews as kinema_avg', 'rating')])
            ->orderBy('order')
            ->take(20)
            ->get();

        return response()->json([
            'person' => $person,
            'filmography' => $filmography,
            'genre_breakdown' => $this->genreBreakdown($filmography),
        ]);
    }

    // Distribución de géneros de la filmografía (para el radar).
    // Normaliza nombres ("Drama") e ids TMDB ("18"), como analytics/genres.
    protected function genreBreakdown($filmography): array
    {
        $names = SearchController::TMDB_GENRES;
        $counts = [];
        foreach ($filmography as $credit) {
            $raw = $credit->movie->genres ?? '';
            foreach (preg_split('/[,\|]/', (string) $raw) as $token) {
                $token = trim($token);
                if ($token === '') {
                    continue;
                }
                $label = is_numeric($token) ? ($names[(int) $token] ?? null) : $token;
                if ($label === null) {
                    continue;
                }
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }
        arsort($counts);
        $total = array_sum($counts) ?: 1;

        return collect($counts)->take(6)->map(fn ($count, $name) => [
            'name' => $name,
            'count' => $count,
            'share' => round($count / $total * 100, 1),
        ])->values()->toArray();
    }

    // Búsqueda simple de personas (para enlazar desde reparto)
    public function search(\Illuminate\Http\Request $request)
    {
        $q = trim($request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Person::select('id', 'name', 'profile_path', 'known_for_department')
                ->where('name', 'ILIKE', "%{$q}%")
                ->orderBy('name')
                ->take(20)
                ->get()
        );
    }
}
