<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // Ver el perfil de un usuario (Público)
    public function show($id)
    {
        // Traemos al usuario junto con su perfil
        $user = User::with('profile')->findOrFail($id);

        return response()->json($user);
    }

    // Ver perfil por username (name) con contadores sociales
    public function showByUsername($username)
    {
        $user = User::with('profile')->where('name', $username)->firstOrFail();
        $user->loadCount(['followers', 'followings']);

        return response()->json($user);
    }

    // Actualizar el Top 4 y la biografía (Protegido)
    public function update(Request $request)
    {
        // Validamos que los IDs de las películas realmente existan en la tabla movies
        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'top_movie_1' => 'nullable|exists:movies,id',
            'top_movie_2' => 'nullable|exists:movies,id',
            'top_movie_3' => 'nullable|exists:movies,id',
            'top_movie_4' => 'nullable|exists:movies,id',
        ]);

        // Obtenemos el perfil del usuario autenticado
        $profile = $request->user()->profile;

        // Actualizamos los campos
        $profile->update([
            'bio' => $request->input('bio', $profile->bio),
            'top_movie_1' => $request->input('top_movie_1'),
            'top_movie_2' => $request->input('top_movie_2'),
            'top_movie_3' => $request->input('top_movie_3'),
            'top_movie_4' => $request->input('top_movie_4'),
        ]);

        return response()->json([
            'message' => 'Perfil actualizado con éxito',
            'profile' => $profile
        ]);
    }
}
