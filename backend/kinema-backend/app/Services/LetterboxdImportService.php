<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\MovieList;
use App\Models\MovieListItem;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/**
 * Importa el ZIP exportado por Letterboxd (diary.csv, ratings.csv,
 * watchlist.csv...). Detecta cada CSV por encabezados, no por nombre,
 * así tolera cambios de formato. Crea stubs de películas que luego
 * convergen con el catálogo vía TMDB.
 */
class LetterboxdImportService
{
    public function __construct(protected TmdbService $tmdb) {}

    /**
     * @return array{ratings:int, watched:int, watchlisted:int, movies_matched:int, movies_created:int, enriched:int, merged:int}
     */
    public function import(User $user, string $zipPath): array
    {
        $summary = [
            'ratings' => 0, 'watched' => 0, 'watchlisted' => 0,
            'movies_matched' => 0, 'movies_created' => 0,
            'enriched' => 0, 'merged' => 0,
        ];

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el ZIP de Letterboxd');
        }

        try {
            DB::beginTransaction();

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!str_ends_with(strtolower($name), '.csv')) {
                    continue;
                }
                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    continue;
                }
                $this->importCsv($user, $name, $contents, $summary);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            $zip->close();
        }

        return $summary;
    }

    protected function importCsv(User $user, string $filename, string $contents, array &$summary): void
    {
        $rows = $this->parseCsv($contents);
        if (empty($rows)) {
            return;
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), array_keys($rows[0]));
        if (!in_array('name', $headers) || !in_array('year', $headers)) {
            return; // No es un CSV de películas reconocido
        }

        // Clasificación: encabezados mandan, nombre de archivo desempata
        $isDiary = in_array('watched date', $headers) || str_contains(strtolower($filename), 'diar');
        $isRatings = !$isDiary && (in_array('rating', $headers) || str_contains(strtolower($filename), 'rating'));
        $type = $isDiary ? 'diary' : ($isRatings ? 'ratings' : 'watchlist');

        $watchlist = null;
        if ($type === 'watchlist') {
            $watchlist = MovieList::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Watchlist'],
                ['description' => 'Importada de Letterboxd', 'is_public' => false]
            );
        }

        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $title = trim($row['name'] ?? '');
            $year = is_numeric($row['year'] ?? null) ? (int) $row['year'] : null;
            if ($title === '') {
                continue;
            }

            [$movie, $created] = $this->resolveMovie($title, $year);
            $created ? $summary['movies_created']++ : $summary['movies_matched']++;

            // Enriquecimiento inmediato del stub (con pausa por rate limit)
            if ($created) {
                $result = $this->tmdb->enrich($movie);
                if ($result) {
                    usleep(300000);
                    if (is_string($result) && str_starts_with($result, 'merged:')) {
                        $summary['merged']++;
                        $movie = Movie::findOrFail((int) substr($result, 7));
                    } else {
                        $summary['enriched']++;
                    }
                }
            }

            $rating = $this->parseRating($row['rating'] ?? null);
            $uri = trim($row['letterboxd uri'] ?? $row['letterboxd_uri'] ?? '');

            if ($type === 'ratings' && $rating !== null) {
                Review::updateOrCreate(
                    ['user_id' => $user->id, 'movie_id' => $movie->id],
                    ['rating' => $rating, 'letterboxd_uri' => $uri ?: null]
                );
                $summary['ratings']++;
            } elseif ($type === 'diary') {
                $review = Review::firstOrNew(['user_id' => $user->id, 'movie_id' => $movie->id]);
                // Nunca pisamos un rating existente con un nulo del diario
                if ($rating !== null && $review->rating === null) {
                    $review->rating = $rating;
                }
                $watched = $this->parseDate($row['watched date'] ?? $row['date'] ?? null);
                if ($watched && empty($review->watched_at)) {
                    $review->watched_at = $watched;
                }
                if ($uri && empty($review->letterboxd_uri)) {
                    $review->letterboxd_uri = $uri;
                }
                $review->save();
                $summary['watched']++;
            } elseif ($type === 'watchlist' && $watchlist) {
                $maxOrder = $watchlist->items()->max('sort_order') ?? 0;
                $item = MovieListItem::firstOrCreate(
                    ['movie_list_id' => $watchlist->id, 'movie_id' => $movie->id],
                    ['sort_order' => $maxOrder + 1]
                );
                if ($item->wasRecentlyCreated) {
                    $summary['watchlisted']++;
                }
            }
        }
    }

    /**
     * @return array{0:Movie, 1:bool} película + si fue creada
     */
    protected function resolveMovie(string $title, ?int $year): array
    {
        // 1) Match exacto título + año
        $query = Movie::whereRaw('LOWER(title) = ?', [mb_strtolower($title)]);
        if ($year) {
            $query->where(function ($q) use ($year) {
                $q->where('release_year', $year)->orWhereYear('release_date', $year);
            });
        }
        $movie = $query->first();
        if ($movie) {
            return [$movie, false];
        }

        // 2) Fallback: fila del catálogo sin fechas (imports ML sin enriquecer).
        // Si hay varias con el mismo título, no adivinamos: creamos stub.
        $dateless = Movie::whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->whereNull('release_year')
            ->whereNull('release_date')
            ->take(2)
            ->get();
        if ($dateless->count() === 1) {
            return [$dateless->first(), false];
        }

        return [Movie::create(['title' => $title, 'release_year' => $year]), true];
    }

    protected function parseCsv(string $contents): array
    {
        // Quitamos BOM típico de exports de Letterboxd
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) {
                continue;
            }
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    protected function parseRating(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $rating = (float) $value;
        if ($rating < 0.5 || $rating > 5) {
            return null;
        }

        return $rating;
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return Carbon::parse(trim((string) $value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
