<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Console\Command;

class EnrichPosters extends Command
{
    // Límite por defecto bajo: TMDB permite ~40 req/10s
    protected $signature = 'app:enrich-posters {--limit=50 : Máximo de películas a procesar} {--only-missing : Solo las que no tienen poster} {--only-missing-boxoffice : Solo las que no tienen budget/revenue} {--force : Reescribe poster/sinopsis/fecha con el mejor match (repara datos mal matcheados)} {--ids= : Solo estos IDs, separados por coma}';
    protected $description = 'Rellena poster, sinopsis y tmdb_id desde TMDB (arregla el home sin imágenes)';

    public function handle(TmdbService $tmdb)
    {
        if (!$tmdb->configured()) {
            $this->error('Falta TMDB_API_KEY en el .env');
            return 1;
        }

        $query = Movie::query()->orderBy('id');
        if ($ids = $this->option('ids')) {
            $query->whereIn('id', array_map('intval', explode(',', $ids)));
        } elseif ($this->option('only-missing-boxoffice')) {
            $query->whereNotNull('tmdb_id')->whereNull('revenue');
        } elseif ($this->option('only-missing')) {
            $query->whereNull('poster_path');
        }

        $force = (bool) $this->option('force');

        $movies = $query->limit((int) $this->option('limit'))->get();
        $ok = 0;
        $merged = 0;

        foreach ($movies as $movie) {
            $result = $tmdb->enrich($movie, $force);
            if (is_string($result) && str_starts_with($result, 'merged:')) {
                $merged++;
                $this->line("  🔀 {$movie->title} -> {$result}");
            } elseif ($result) {
                $ok++;
            }
            // Respeto al rate limit de TMDB
            usleep(300000);
        }

        $this->info("✅ Enriquecidas: {$ok}, fusionadas con catálogo: {$merged}, de {$movies->count()} procesadas.");

        return 0;
    }
}
