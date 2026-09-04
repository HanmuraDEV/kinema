<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieList extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'is_public'];

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
