<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'content' => 'nullable|string|max:2000',
            // Calificación de 0.5 a 5.0, típica de Letterboxd
            'rating' => 'nullable|numeric|min:0.5|max:5.0',
            'has_spoilers' => 'boolean'
        ]);

// updateOrCreate evita que un usuario deje 5 reseñas distintas a la misma película;
        // si ya la reseñó, simplemente actualiza su texto y calificación.
        $review = Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'movie_id' => $request->input('movie_id'),
            ],
            [
                // Usamos input() para evitar chocar con la variable protegida interna de Symfony
                'content' => $request->input('content'),
                'rating' => $request->input('rating'),
                // input() permite un segundo parámetro que funciona como valor por defecto
                'has_spoilers' => $request->input('has_spoilers', false),
            ]
        );

        return response()->json([
            'message' => 'Reseña guardada con éxito',
            'review' => $review
        ], 201);
    }
}
