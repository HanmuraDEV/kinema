<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MovieList extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'is_public'];

    protected static function booted(): void
    {
        // Generar slug único por usuario al crear
        static::creating(function (MovieList $list) {
            if (empty($list->slug)) {
                $list->slug = static::uniqueSlugForUser($list->user_id, $list->name);
            }
        });

        // Regenerar slug si cambia el nombre (evita URLs rotas por colisión con sufijo)
        static::updating(function (MovieList $list) {
            if ($list->isDirty('name')) {
                $list->slug = static::uniqueSlugForUser($list->user_id, $list->name, $list->id);
            }
        });
    }

    public static function uniqueSlugForUser(int $userId, ?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name ?: 'lista') ?: 'lista';
        $slug = $base;
        $n = 2;
        while (
            static::where('user_id', $userId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    // Una lista le pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Una lista tiene muchas películas (items)
    public function items()
    {
        // Traemos los items ordenados por su posición (sort_order)
        return $this->hasMany(MovieListItem::class)->orderBy('sort_order');
    }
}
