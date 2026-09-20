<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Review;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Comentarios de una reseña (público, paginado, con autor)
    public function index($reviewId)
    {
        Review::findOrFail($reviewId);

        $comments = Comment::with('user:id,name')
            ->where('review_id', $reviewId)
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    // Comentar una reseña (protegido)
    public function store(Request $request, $reviewId)
    {
        Review::findOrFail($reviewId);

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'review_id' => (int) $reviewId,
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);

        return response()->json([
            'message' => 'Comentario publicado',
            'comment' => $comment->load('user:id,name'),
        ], 201);
    }

    // Editar comentario (solo dueño)
    public function update(Request $request, $id)
    {
        $comment = Comment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update(['content' => $request->input('content')]);

        return response()->json([
            'message' => 'Comentario actualizado',
            'comment' => $comment->fresh()->load('user:id,name'),
        ]);
    }

    // Borrar comentario (solo dueño)
    public function destroy(Request $request, $id)
    {
        $comment = Comment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Comentario eliminado']);
    }
}
