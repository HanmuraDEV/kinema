<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, $id)
    {
        // 1. Evitar que el usuario se siga a sí mismo
        if ($request->user()->id == $id) {
            return response()->json(['message' => 'No puedes seguirte a ti mismo'], 400);
        }

        // 2. Verificar que el usuario que queremos seguir realmente exista
        $userToFollow = User::findOrFail($id);

        // 3. La magia de Laravel: toggle() hace el attach/detach automáticamente
        $changes = $request->user()->followings()->toggle($userToFollow->id);

        // 4. Determinamos qué pasó para mandar un mensaje claro al Frontend
        $isFollowing = count($changes['attached']) > 0;

        return response()->json([
            'message' => $isFollowing ? 'Ahora sigues a este usuario' : 'Dejaste de seguir a este usuario',
            'is_following' => $isFollowing
        ]);
    }

    // ¿Sigo a este usuario? (requiere auth para el check, público el conteo)
    public function status(Request $request, $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'is_following' => $request->user()
                ? $request->user()->followings()->where('users.id', $user->id)->exists()
                : false,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->followings()->count(),
        ]);
    }

    public function followers($id)
    {
        $user = User::findOrFail($id);

        return response()->json(
            $user->followers()->select('users.id', 'users.name')->paginate(20)
        );
    }

    public function following($id)
    {
        $user = User::findOrFail($id);

        return response()->json(
            $user->followings()->select('users.id', 'users.name')->paginate(20)
        );
    }
}
