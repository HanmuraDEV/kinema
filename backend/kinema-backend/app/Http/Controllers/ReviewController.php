<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Reseñas de una película (público, paginado)
    public function index(Request $request, $movieId)
    {
        $reviews = Review::with('user:id,name')
            ->where('movie_id', $movieId)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function show($id)
    {
        $review = Review::with(['user:id,name', 'movie:id,title,poster_path'])->findOrFail($id);

        return response()->json($review);
    }

    // Diario de un usuario: sus reseñas/vistas con película (para /diary)
    public function byUser($userId)
    {
        $reviews = Review::with('movie:id,title,poster_path,release_date,release_year')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }

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

    public function update(Request $request, $id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'content' => 'nullable|string|max:2000',
            'rating' => 'nullable|numeric|min:0.5|max:5.0',
            'has_spoilers' => 'boolean',
        ]);

        $review->update([
            'content' => $request->input('content', $review->content),
            'rating' => $request->input('rating', $review->rating),
            'has_spoilers' => $request->input('has_spoilers', $review->has_spoilers),
        ]);

        return response()->json([
            'message' => 'Reseña actualizada',
            'review' => $review->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $review->delete();

        return response()->json(['message' => 'Reseña eliminada']);
    }
}
