<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Console\Command;

class EnrichCredits extends Command
{
    protected $signature = 'app:enrich-credits {--limit=50 : Máximo de películas a procesar} {--ids= : Solo estos IDs, separados por coma}';
    protected $description = 'Importa reparto y equipo (cast + dirección) desde TMDB a people/credits';

    public function handle(TmdbService $tmdb)
    {
        if (!$tmdb->configured()) {
            $this->error('Falta TMDB_API_KEY en el .env');
            return 1;
        }

        $query = Movie::query()
            ->whereNotNull('tmdb_id')
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)->from('credits')->whereColumn('credits.movie_id', 'movies.id');
            })
            ->orderBy('id');

        if ($ids = $this->option('ids')) {
            $query = Movie::query()->whereNotNull('tmdb_id')->whereIn('id', array_map('intval', explode(',', $ids)));
        }

        $movies = $query->limit((int) $this->option('limit'))->get();
        $total = 0;

        foreach ($movies as $movie) {
            $n = $tmdb->credits($movie);
            $total += $n;
            $this->line("  🎬 {$movie->title}: {$n} créditos");
            usleep(300000);
        }

        $this->info("✅ {$total} créditos importados en {$movies->count()} películas.");

        return 0;
    }
}
