<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Movie;
use App\Models\Vibe;

class AnalyticsController extends Controller
{
    public function movieSentiment($id)
    {
        // El pipeline escribe en storage/app/private/ (disco local).
        // Se acepta también la ruta legada storage/app/ por compatibilidad.
        $jsonString = null;
        if (Storage::exists('kinema_sentiment_data.json')) {
            $jsonString = Storage::get('kinema_sentiment_data.json');
        } elseif (is_readable(storage_path('app/kinema_sentiment_data.json'))) {
            $jsonString = file_get_contents(storage_path('app/kinema_sentiment_data.json'));
        }

        // Si el archivo de Python aún no existe o el pipeline no ha corrido,
        // devolvemos un JSON vacío pero estructurado para no romper el Frontend.
        if ($jsonString === null) {
            return response()->json([
                'movie_id' => (int) $id,
                'POSITIVE' => 0,
                'NEGATIVE' => 0
            ]);
        }

        // Leemos el archivo y lo decodificamos a un arreglo de PHP
        $data = json_decode($jsonString, true);

        // Convertimos el arreglo en una Colección y buscamos la película solicitada
        $movieData = collect($data)->firstWhere('movie_id', (int) $id);

        // Si la película existe en el análisis, devolvemos sus datos.
        // Si no (quizás nadie le ha dejado reseñas), devolvemos contadores en cero.
        if ($movieData) {
            return response()->json($movieData);
        }

        return response()->json([
            'movie_id' => (int) $id,
            'POSITIVE' => 0,
            'NEGATIVE' => 0
        ]);
    }

    // Actividad por día (heatmap del perfil). ?user_id= opcional, ?days=365.
    public function activity(Request $request)
    {
        $days = min($request->integer('days', 365), 730);
        $since = now()->subDays($days)->toDateString();

        $query = Review::selectRaw('DATE(COALESCE(watched_at, created_at)) as day, COUNT(*) as count')
            ->whereRaw('COALESCE(watched_at, created_at) >= ?', [$since])
            ->groupBy('day')
            ->orderBy('day');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        return response()->json($query->get());
    }

    // Radar de gustos: reparto de vibras en lo reseñado (?user_id=) o global.
    public function taste(Request $request)
    {
        $query = Review::join('movies', 'movies.id', '=', 'reviews.movie_id')
            ->join('vibes', 'vibes.id', '=', 'movies.vibe_id')
            ->selectRaw('vibes.id, vibes.name, COUNT(*) as count')
            ->groupBy('vibes.id', 'vibes.name')
            ->orderByDesc('count');

        if ($request->filled('user_id')) {
            $query->where('reviews.user_id', (int) $request->query('user_id'));
        }

        $rows = $query->take(8)->get();
        $total = $rows->sum('count') ?: 1;

        return response()->json(
            $rows->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'count' => (int) $r->count,
                'share' => round($r->count / $total * 100, 1),
            ])
        );
    }

    // Mapa semántico del catálogo: coordenadas x/y + vibra (plot del home).
    public function map()
    {
        return response()->json(
            Movie::select('id', 'title', 'poster_path', 'x_coordinate', 'y_coordinate', 'vibe_id')
                ->with('vibe:id,name')
                ->whereNotNull('x_coordinate')
                ->whereNotNull('y_coordinate')
                ->orderBy('id')
                ->take(500)
                ->get()
        );
    }

    // Desglose por género (normaliza ids numéricos TMDB y nombres).
    public function genres()
    {
        $names = [
            28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy',
            80 => 'Crime', 99 => 'Documentary', 18 => 'Drama', 10751 => 'Family',
            14 => 'Fantasy', 36 => 'History', 27 => 'Horror', 10402 => 'Music',
            9648 => 'Mystery', 10749 => 'Romance', 878 => 'Science Fiction',
            10770 => 'TV Movie', 53 => 'Thriller', 10752 => 'War', 37 => 'Western',
        ];

        $counts = [];
        foreach (Movie::whereNotNull('genres')->pluck('genres') as $raw) {
            foreach (preg_split('/[,\|]/', (string) $raw) as $token) {
                $token = trim($token);
                if ($token === '') {
                    continue;
                }
                $label = is_numeric($token) ? ($names[(int) $token] ?? "Género {$token}") : $token;
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }
        arsort($counts);

        return response()->json(
            collect($counts)->take(12)->map(fn ($count, $name) => ['name' => $name, 'count' => $count])->values()
        );
    }
}
