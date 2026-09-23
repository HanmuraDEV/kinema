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
            ->with('movie:id,title,poster_path,release_date,release_year')
            ->orderBy('order')
            ->take(20)
            ->get();

        return response()->json([
            'person' => $person,
            'filmography' => $filmography,
        ]);
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
