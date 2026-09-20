<?php

namespace App\Http\Controllers;

use App\Models\MovieList;
use App\Models\MovieListItem;
use Illuminate\Http\Request;

class MovieListController extends Controller
{
    // Listas públicas de un usuario (para perfil)
    public function index(Request $request, $userId)
    {
        // Ruta pública: resolvemos el dueño manualmente para no filtrar sus privadas
        $viewer = auth('sanctum')->user();

        $lists = MovieList::withCount('items')
            ->where('user_id', $userId)
            ->when(!$viewer || (int) $viewer->id !== (int) $userId, function ($q) {
                $q->where('is_public', true);
            })
            ->latest()
            ->paginate(12);

        return response()->json($lists);
    }

    // 1. Crear una lista vacía
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean'
        ]);

        $list = MovieList::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_public' => $request->input('is_public', true),
        ]);

        return response()->json([
            'message' => 'Lista creada con éxito',
            'list' => $list
        ], 201);
    }

    // 2. Agregar una película a la lista
    public function addItem(Request $request, $listId)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id'
        ]);

        // Verificamos que la lista exista y que le pertenezca al usuario que hace la petición
        $list = MovieList::where('id', $listId)
                         ->where('user_id', $request->user()->id)
                         ->firstOrFail();

        // Calculamos cuál es la última posición para colocar esta película al final
        $maxOrder = $list->items()->max('sort_order') ?? 0;

        // Si la película ya está en la lista, no hace nada; si no, la agrega
        $item = MovieListItem::firstOrCreate(
            ['movie_list_id' => $list->id, 'movie_id' => $request->input('movie_id')],
            ['sort_order' => $maxOrder + 1]
        );

        return response()->json([
            'message' => 'Película agregada a la lista',
            'item' => $item
        ], 201);
    }

    // 3. Ver una lista y todas sus películas
    public function show($id)
    {
        // El poder de Eloquent: Trae la lista, y anida sus items junto con la información de la película
        $list = MovieList::with('items.movie')->findOrFail($id);

        return response()->json($list);
    }

    // 3b. Ver lista por username + slug (URL /{username}/lists/{slug}).
    // Cada usuario tiene su propio espacio de slugs: dos usuarios pueden
    // tener el mismo slug con películas distintas.
    public function showBySlug(Request $request, $username, $slug)
    {
        $user = \App\Models\User::where('name', $username)->firstOrFail();

        $list = MovieList::with(['items.movie', 'user:id,name'])
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->firstOrFail();

        // Las listas privadas solo las ve su dueño (ruta pública: auth manual vía Bearer)
        $viewer = auth('sanctum')->user();
        if (!$list->is_public && (!$viewer || (int) $viewer->id !== (int) $user->id)) {
            abort(403, 'Esta lista es privada');
        }

        return response()->json($list);
    }

    // 4. Actualizar nombre/descripción/visibilidad (solo dueño)
    public function update(Request $request, $listId)
    {
        $list = MovieList::where('id', $listId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $list->update([
            'name' => $request->input('name', $list->name),
            'description' => $request->input('description', $list->description),
            'is_public' => $request->input('is_public', $list->is_public),
        ]);

        return response()->json([
            'message' => 'Lista actualizada',
            'list' => $list->fresh(),
        ]);
    }

    // 5. Eliminar lista (solo dueño)
    public function destroy(Request $request, $listId)
    {
        $list = MovieList::where('id', $listId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $list->delete();

        return response()->json(['message' => 'Lista eliminada']);
    }

    // 6. Quitar una película de la lista (solo dueño)
    public function removeItem(Request $request, $listId, $movieId)
    {
        $list = MovieList::where('id', $listId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $deleted = MovieListItem::where('movie_list_id', $list->id)
            ->where('movie_id', $movieId)
            ->delete();

        return response()->json([
            'message' => $deleted ? 'Película eliminada de la lista' : 'La película no estaba en la lista',
        ]);
    }
}
