<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_id',
        'content',
        'rating',
        'has_spoilers'
    ];

    // Una reseña pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Una reseña pertenece a una película
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
