<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    // Definimos la tabla asociada al modelo
    protected $table = 'profiles';
   // Autorizamos estos campos para asignación masiva
    protected $fillable = [
        'bio',
        'top_movie_1',
        'top_movie_2',
        'top_movie_3',
        'top_movie_4',
    ];

    // Aprovechamos para definir la relación de vuelta al usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
