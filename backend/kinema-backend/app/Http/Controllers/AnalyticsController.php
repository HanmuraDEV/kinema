<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

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
}
