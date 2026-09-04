<?php

namespace App\Http\Controllers;

use App\Models\MovieList;
use App\Models\MovieListItem;
use Illuminate\Http\Request;

class MovieListController extends Controller
{
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
}
