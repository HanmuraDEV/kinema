<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class AnalyticsController extends Controller
{
    public function movieSentiment($id)
    {
        // Definimos la ruta del archivo dentro de storage/app/
        $path = 'kinema_sentiment_data.json';

        // Si el archivo de Python aún no existe o el pipeline no ha corrido,
        // devolvemos un JSON vacío pero estructurado para no romper el Frontend.
        if (!Storage::exists($path)) {
            return response()->json([
                'movie_id' => (int) $id,
                'POSITIVE' => 0,
                'NEGATIVE' => 0
            ]);
        }

        // Leemos el archivo y lo decodificamos a un arreglo de PHP
        $jsonString = Storage::get($path);
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
