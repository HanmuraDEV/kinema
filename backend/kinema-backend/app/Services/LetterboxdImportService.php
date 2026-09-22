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
 * Importa el ZIP exportado por Letterboxd tal cual se descarga:
 * ratings, diary, watched, reviews (con texto), watchlist,
 * lists/*.csv (listas propias, slug = nombre de archivo),
 * likes/films.csv y profile.csv.
 *
 * Se ignoran: deleted/*, orphaned/* (contenido borrado),
 * likes de terceros, comments (apuntan a reseñas ajenas).
 * Cada CSV se detecta por encabezados; los campos multilínea
 * (texto de reseñas) exigen parseo con fgetcsv, no por líneas.
 */
class LetterboxdImportService
{
    public function __construct(protected TmdbService $tmdb) {}

    public function import(User $user, string $zipPath): array
    {
        $summary = [
            'ratings' => 0, 'watched' => 0, 'reviews_text' => 0,
            'watchlisted' => 0, 'liked' => 0, 'lists' => 0, 'list_items' => 0,
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
                // Contenido borrado o huérfano: no se importa
                if (str_starts_with(strtolower($name), 'deleted/') || str_starts_with(strtolower($name), 'orphaned/')) {
                    continue;
                }
                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    continue;
                }

                if (str_starts_with(strtolower($name), 'lists/')) {
                    $this->importListCsv($user, $name, $contents, $summary);
                } elseif (str_ends_with(strtolower($name), 'profile.csv')) {
                    $this->importProfile($user, $contents);
                } elseif (str_contains(strtolower($name), 'likes/')) {
                    // Solo likes de películas propias; los de terceros se ignoran
                    if (str_ends_with(strtolower($name), 'films.csv')) {
                        $this->importSimpleList($user, $contents, 'Me gusta de Letterboxd', 'Películas que marcaste con like', $summary, 'liked');
                    }
                } else {
                    $this->importHistoryCsv($user, $name, $contents, $summary);
                }
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

    // ---- Listas propias (lists/*.csv con preámbulo de metadatos) ----

    protected function importListCsv(User $user, string $filename, string $contents, array &$summary): void
    {
        // Formato: "Letterboxd list export vX" + fila meta + fila vacía + items
        $contents = $this->toUtf8($contents);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $listName = pathinfo($filename, PATHINFO_FILENAME);
        $listDescription = null;
        $itemsHeader = null;
        $items = [];

        // Leemos la primera línea: ¿preámbulo o encabezado directo?
        $first = fgetcsv($stream);
        if ($first && str_starts_with(trim($first[0] ?? ''), 'Letterboxd list export')) {
            $metaHeader = $this->lowerRow(fgetcsv($stream)); // Date,Name,Tags,URL,Description
            $metaValues = fgetcsv($stream);
            if ($metaHeader && $metaValues) {
                $meta = $this->combine($metaHeader, $metaValues);
                $listName = trim($meta['name'] ?? '') ?: $listName;
                $listDescription = trim($meta['description'] ?? '') ?: null;
            }
            fgetcsv($stream); // línea vacía separadora
            $itemsHeader = $this->lowerRow(fgetcsv($stream)); // Position,Name,Year,URL,Description
        } else {
            $itemsHeader = $this->lowerRow($first);
        }

        if (!$itemsHeader || !in_array('name', $itemsHeader)) {
            fclose($stream);
            return;
        }

        while (($values = fgetcsv($stream)) !== false) {
            if (count($values) !== count($itemsHeader)) {
                continue;
            }
            $items[] = $this->combine($itemsHeader, $values);
        }
        fclose($stream);

        // El slug viene del nombre de archivo (ya es slug en Letterboxd)
        $slug = strtolower(trim(pathinfo($filename, PATHINFO_FILENAME)));
        $list = MovieList::firstOrCreate(
            ['user_id' => $user->id, 'slug' => $slug],
            ['name' => $listName, 'description' => $listDescription, 'is_public' => true]
        );
        $summary['lists']++;

        // Orden original según Position
        usort($items, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));
        $order = ($list->items()->max('sort_order') ?? 0);
        foreach ($items as $row) {
            $title = trim($row['name'] ?? '');
            $year = is_numeric($row['year'] ?? null) ? (int) $row['year'] : null;
            if ($title === '') {
                continue;
            }
            [$movie] = $this->resolveMovie($title, $year, $summary);
            $item = MovieListItem::firstOrCreate(
                ['movie_list_id' => $list->id, 'movie_id' => $movie->id],
                ['sort_order' => ++$order]
            );
            if ($item->wasRecentlyCreated) {
                $summary['list_items']++;
            }
        }
    }

    protected function importSimpleList(User $user, string $contents, string $name, ?string $description, array &$summary, string $counter): void
    {
        $rows = $this->parseCsv($contents);
        if (empty($rows)) {
            return;
        }
        $list = MovieList::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            ['description' => $description, 'is_public' => false]
        );
        $order = ($list->items()->max('sort_order') ?? 0);
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $title = trim($row['name'] ?? '');
            $year = is_numeric($row['year'] ?? null) ? (int) $row['year'] : null;
            if ($title === '') {
                continue;
            }
            [$movie] = $this->resolveMovie($title, $year, $summary);
            $item = MovieListItem::firstOrCreate(
                ['movie_list_id' => $list->id, 'movie_id' => $movie->id],
                ['sort_order' => ++$order]
            );
            if ($item->wasRecentlyCreated) {
                $summary[$counter]++;
            }
        }
    }

    protected function importProfile(User $user, string $contents): void
    {
        $rows = $this->parseCsv($contents);
        $bio = trim($rows[0]['bio'] ?? $rows[0]['Bio'] ?? '');
        if ($bio !== '' && $user->profile && empty($user->profile->bio)) {
            $user->profile->update(['bio' => $bio]);
        }
    }

    // ---- Historial: ratings, diary, watched, reviews, watchlist ----

    protected function importHistoryCsv(User $user, string $filename, string $contents, array &$summary): void
    {
        $rows = $this->parseCsv($contents);
        if (empty($rows)) {
            return;
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), array_keys($rows[0]));
        if (!in_array('name', $headers)) {
            return;
        }

        $lower = strtolower($filename);
        $hasReviewText = in_array('review', $headers);
        $hasWatchedDate = in_array('watched date', $headers);
        $hasRating = in_array('rating', $headers);

        if ($hasReviewText) {
            $type = 'reviews';
        } elseif ($hasWatchedDate || str_contains($lower, 'diar')) {
            $type = 'diary';
        } elseif ($hasRating || str_contains($lower, 'rating')) {
            $type = 'ratings';
        } elseif (str_contains($lower, 'watchlist')) {
            $type = 'watchlist';
        } elseif (str_contains($lower, 'watch')) {
            $type = 'watched';
        } else {
            return;
        }

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

            [$movie] = $this->resolveMovie($title, $year, $summary);
            $rating = $this->parseRating($row['rating'] ?? null);
            $uri = trim($row['letterboxd uri'] ?? $row['letterboxd_uri'] ?? $row['url'] ?? '');

            if ($type === 'watchlist' && $watchlist) {
                $maxOrder = $watchlist->items()->max('sort_order') ?? 0;
                $item = MovieListItem::firstOrCreate(
                    ['movie_list_id' => $watchlist->id, 'movie_id' => $movie->id],
                    ['sort_order' => $maxOrder + 1]
                );
                if ($item->wasRecentlyCreated) {
                    $summary['watchlisted']++;
                }
                continue;
            }

            // ratings manda: el archivo de valoraciones es autoritativo
            if ($type === 'ratings' && $rating !== null) {
                $review = Review::firstOrNew(['user_id' => $user->id, 'movie_id' => $movie->id]);
                $review->rating = $rating;
                if ($uri && empty($review->letterboxd_uri)) {
                    $review->letterboxd_uri = $uri;
                }
                $review->save();
                $summary['ratings']++;
                continue;
            }

            // diary, watched y reviews convergen en una reseña por película
            $review = Review::firstOrNew(['user_id' => $user->id, 'movie_id' => $movie->id]);
            if ($type === 'reviews') {
                $text = trim($row['review'] ?? '');
                if ($text !== '' && empty($review->content)) {
                    $review->content = $text;
                    $summary['reviews_text']++;
                }
            }
            // Nunca pisamos un rating existente con un nulo del diario
            if ($rating !== null && $review->rating === null) {
                $review->rating = $rating;
            }
            $watched = $this->parseDate($row['watched date'] ?? $row['date'] ?? null);
            if ($watched && (empty($review->watched_at) || $watched < $review->watched_at)) {
                $review->watched_at = $watched;
            }
            if ($uri && empty($review->letterboxd_uri)) {
                $review->letterboxd_uri = $uri;
            }
            $review->save();
            $summary['watched']++;
        }
    }

    /**
     * @return array{0:Movie, 1:bool} película + si fue creada
     */
    protected function resolveMovie(string $title, ?int $year, array &$summary): array
    {
        $query = Movie::whereRaw('LOWER(title) = ?', [mb_strtolower($title)]);
        if ($year) {
            $query->where(function ($q) use ($year) {
                $q->where('release_year', $year)->orWhereYear('release_date', $year);
            });
        }
        $movie = $query->first();
        if ($movie) {
            $summary['movies_matched']++;
            return [$movie, false];
        }

        // Fallback: fila del catálogo sin fechas (imports ML sin enriquecer)
        $dateless = Movie::whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->whereNull('release_year')
            ->whereNull('release_date')
            ->take(2)
            ->get();
        if ($dateless->count() === 1) {
            $summary['movies_matched']++;
            return [$dateless->first(), false];
        }

        $movie = Movie::create(['title' => $title, 'release_year' => $year]);
        $summary['movies_created']++;

        // Enriquecimiento inmediato del stub (con pausa por rate limit)
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

        return [$movie, true];
    }

    /**
     * Parseo CSV real con fgetcsv (tolera comillas y saltos de línea
     * dentro de campos, como el texto de las reseñas).
     */
    protected function parseCsv(string $contents): array
    {
        $contents = $this->toUtf8($contents);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers) {
            fclose($stream);
            return [];
        }

        $rows = [];
        while (($values = fgetcsv($stream)) !== false) {
            if (count($values) !== count($headers)) {
                continue;
            }
            // Filas totalmente vacías fuera
            if (!array_filter($values, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $rows[] = array_combine($headers, $values);
        }
        fclose($stream);

        return $rows;
    }

    protected function lowerRow(?array $row): ?array
    {
        if (!$row) {
            return null;
        }

        return array_map(fn ($h) => strtolower(trim((string) $h)), $row);
    }

    protected function combine(array $headers, array $values): array
    {
        $headers = $this->lowerRow($headers);

        return array_combine($headers, $values);
    }

    /**
     * Normaliza a UTF-8 válido: BOM fuera y, si la cadena no es
     * UTF-8 válido, conversión desde Windows-1252.
     */
    protected function toUtf8(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        if (!mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        return $contents;
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
