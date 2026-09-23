<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Support\Facades\Http;

/**
 * Enriquece películas con The Movie Database:
 * poster, sinopsis, géneros, tmdb_id y fecha real.
 * Los stubs del import Letterboxd convergen con el catálogo ML
 * vía updateOrCreate por tmdb_id.
 */
class TmdbService
{
    protected string $apiKey;
    protected string $imageBase;

    public function __construct()
    {
        $this->apiKey = (string) config('services.tmdb.key', env('TMDB_API_KEY'));
        $this->imageBase = rtrim((string) config('services.tmdb.image_base', env('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/w500')), '/');
    }

    public function configured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Busca en TMDB por título (+ año opcional). Devuelve el mejor match o null.
     * Se usa en-US para matchear (títulos originales estables) y se exige
     * un score mínimo: el primer resultado crudo suele ser basura
     * (ej. "More Animated Worker and Parasite" para "Parasite").
     */
    public function search(string $title, ?int $year = null): ?array
    {
        if (!$this->configured()) {
            return null;
        }

        $response = Http::timeout(15)->get('https://api.themoviedb.org/3/search/movie', [
            'api_key' => $this->apiKey,
            'query' => $title,
            'year' => $year,
            'language' => 'en-US',
            'include_adult' => false,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $this->pickBest($response->json('results', []), $title, $year);
    }

    protected function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return (string) preg_replace('/[^a-z0-9]+/', ' ', $s);
    }

    protected function pickBest(array $results, string $title, ?int $year): ?array
    {
        $want = trim($this->norm($title));
        $best = null;
        $bestScore = -1;

        foreach ($results as $r) {
            $score = 0;
            $t = trim($this->norm((string) ($r['title'] ?? '')));
            $o = trim($this->norm((string) ($r['original_title'] ?? '')));

            if ($want !== '' && ($t === $want || $o === $want)) {
                $score += 10;
            } elseif ($want !== '' && ($t !== '' && (str_contains($t, $want) || str_contains($want, $t)))) {
                $score += 4;
            }

            $ry = !empty($r['release_date']) ? (int) substr($r['release_date'], 0, 4) : null;
            if ($year && $ry === $year) {
                $score += 5;
            } elseif ($year && $ry) {
                $score -= 3;
            }

            $score += min((float) ($r['popularity'] ?? 0) / 20, 2);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $r;
            }
        }

        return $bestScore >= 8 ? $best : null;
    }

    /**
     * Rellena los huecos de una película (poster, overview, géneros,
     * tmdb_id, fecha). Con $force también corrige datos TMDB previos
     * (nunca toca tmdb_id existente, vibras ni vectores).
     *
     * @return bool|string true/false, o 'merged:{id}' si el stub se fusionó
     *   con una fila canónica (el stub queda eliminado: usar el id devuelto)
     */
    public function enrich(Movie $movie, bool $force = false): bool|string
    {
        $match = $this->search($movie->title, $movie->release_year ?? $this->yearFromDate($movie->release_date));

        if (!$match) {
            return false;
        }

        $changed = false;
        $patch = [];

        if (empty($movie->tmdb_id) && isset($match['id'])) {
            $patch['tmdb_id'] = (int) $match['id'];
            $changed = true;
        }
        if (($force || empty($movie->poster_path)) && !empty($match['poster_path'])) {
            $patch['poster_path'] = $this->imageBase . $match['poster_path'];
            $changed = true;
        }
        if (($force || empty($movie->overview)) && !empty($match['overview'])) {
            $patch['overview'] = $match['overview'];
            $changed = true;
        }
        if (($force || empty($movie->release_date)) && !empty($match['release_date'])) {
            $patch['release_date'] = $match['release_date'];
            $changed = true;
        }
        if (($force || empty($movie->genres)) && !empty($match['genre_ids'])) {
            $patch['genres'] = implode(',', $match['genre_ids']);
            $changed = true;
        }

        if (!$changed) {
            return false;
        }

        if (!empty($patch['tmdb_id'])) {
            $canonical = Movie::where('tmdb_id', $patch['tmdb_id'])->where('id', '!=', $movie->id)->first();
            if ($canonical) {
                $canonical->fill($patch);
                // Nunca pisamos vectores/vibras del catálogo con nulos del stub
                foreach (['vibe_id', 'x_coordinate', 'y_coordinate', 'embedding'] as $mlField) {
                    if (empty($canonical->getAttribute($mlField)) && !empty($movie->getAttribute($mlField))) {
                        $canonical->setAttribute($mlField, $movie->getAttribute($mlField));
                    }
                }
                if ($canonical->isDirty()) {
                    $canonical->save();
                }
                // El historial del stub (reseñas, items de listas) pasa a la fila canónica
                \App\Models\Review::where('movie_id', $movie->id)->update(['movie_id' => $canonical->id]);
                \App\Models\MovieListItem::where('movie_id', $movie->id)->update(['movie_id' => $canonical->id]);
                $movie->delete();

                return 'merged:' . $canonical->id;
            }
        }

        $movie->update($patch);

        return $changed;
    }

    protected function yearFromDate($date): ?int
    {
        if (empty($date)) {
            return null;
        }

        try {
            return (int) substr((string) $date, 0, 4) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Importa reparto (top 15) + dirección/escritura a people/credits.
     * Devuelve cantidad de créditos creados.
     */
    public function credits(Movie $movie): int
    {
        if (!$this->configured() || empty($movie->tmdb_id)) {
            return 0;
        }

        $response = Http::timeout(20)->get("https://api.themoviedb.org/3/movie/{$movie->tmdb_id}/credits", [
            'api_key' => $this->apiKey,
            'language' => 'en-US',
        ]);

        if (!$response->successful()) {
            return 0;
        }

        $data = $response->json();
        $count = 0;

        $import = function (array $members, string $defaultDepartment) use ($movie, &$count) {
            foreach ($members as $index => $m) {
                if (empty($m['id']) || empty($m['name'])) {
                    continue;
                }
                $department = $m['department'] ?? $defaultDepartment;
                $person = \App\Models\Person::updateOrCreate(
                    ['tmdb_id' => (int) $m['id']],
                    [
                        'name' => $m['name'],
                        'profile_path' => !empty($m['profile_path']) ? $this->imageBase . $m['profile_path'] : null,
                        'known_for_department' => $m['known_for_department'] ?? $department,
                    ]
                );
                $credit = \App\Models\Credit::firstOrCreate(
                    [
                        'movie_id' => $movie->id,
                        'person_id' => $person->id,
                        'job' => $m['job'] ?? ($department === 'Acting' ? 'Actor' : $department),
                    ],
                    [
                        'department' => $department,
                        'character' => $m['character'] ?? null,
                        'order' => $m['order'] ?? $index,
                    ]
                );
                if ($credit->wasRecentlyCreated) {
                    $count++;
                }
            }
        };

        $import(array_slice($data['cast'] ?? [], 0, 15), 'Acting');
        // Equipo clave: dirección y escritura (el resto saturaría la tabla)
        $crew = array_filter(
            $data['crew'] ?? [],
            fn ($c) => in_array($c['job'] ?? '', ['Director', 'Writer', 'Screenplay', 'Novel'], true)
        );
        $import(array_values($crew), 'Directing');

        return $count;
    }

    /**
     * Rellena bio y fechas de una persona bajo demanda. Devuelve true si cambió algo.
     */
    public function personDetail(\App\Models\Person $person): bool
    {
        if (!$this->configured() || empty($person->tmdb_id)) {
            return false;
        }

        $response = Http::timeout(15)->get("https://api.themoviedb.org/3/person/{$person->tmdb_id}", [
            'api_key' => $this->apiKey,
            'language' => 'es-MX',
        ]);

        if (!$response->successful()) {
            return false;
        }

        $data = $response->json();
        $patch = [];
        foreach (['biography', 'birthday', 'deathday'] as $field) {
            if (empty($person->{$field}) && !empty($data[$field])) {
                $patch[$field] = $data[$field];
            }
        }
        if (empty($person->profile_path) && !empty($data['profile_path'])) {
            $patch['profile_path'] = $this->imageBase . $data['profile_path'];
        }

        if ($patch) {
            $person->update($patch);
            return true;
        }

        return false;
    }
}
