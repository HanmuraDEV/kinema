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
}
