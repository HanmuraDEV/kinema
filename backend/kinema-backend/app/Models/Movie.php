<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Vector;

class Movie extends Model
{
    use HasFactory;

    // Permitimos la inserción masiva de estos campos
    protected $fillable = [
        'tmdb_id',
        'title',
        'overview',
        'genres',
        'poster_path',
        'release_date',
        'release_year',
        'budget',
        'revenue',
        'runtime',
        'vibe_id',
        'x_coordinate',
        'y_coordinate',
        'embedding',
    ];

    // Casteamos el array de PHP a un Vector de PostgreSQL automáticamente
    protected $casts = [
        'embedding' => Vector::class,
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Créditos (reparto y equipo) de la película
    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    // Una película pertenece a un cluster/vibra (usado en index/search/show)
    public function vibe()
    {
        return $this->belongsTo(Vibe::class);
    }
}
