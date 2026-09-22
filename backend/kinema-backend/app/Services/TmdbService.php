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
            'language' => 'es-MX',
            'include_adult' => false,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $results = $response->json('results', []);

        return $results[0] ?? null;
    }

    /**
     * Rellena los huecos de una película (poster, overview, géneros,
     * tmdb_id, fecha). Devuelve true si cambió algo.
     */
    public function enrich(Movie $movie): bool
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
        if (empty($movie->poster_path) && !empty($match['poster_path'])) {
            $patch['poster_path'] = $this->imageBase . $match['poster_path'];
            $changed = true;
        }
        if (empty($movie->overview) && !empty($match['overview'])) {
            $patch['overview'] = $match['overview'];
            $changed = true;
        }
        if (empty($movie->release_date) && !empty($match['release_date'])) {
            $patch['release_date'] = $match['release_date'];
            $changed = true;
        }
        if (empty($movie->genres) && !empty($match['genre_ids'])) {
            $patch['genres'] = implode(',', $match['genre_ids']);
            $changed = true;
        }

        if ($changed) {
            // Si el tmdb_id ya existe en el catálogo ML, fusionamos:
            // el stub desaparece y el historial apunta a la fila canónica.
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
        }

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
