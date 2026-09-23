<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'tmdb_id',
        'name',
        'biography',
        'profile_path',
        'birthday',
        'deathday',
        'known_for_department',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'deathday' => 'date',
        ];
    }

    // Películas donde aparece/trabaja esta persona
    public function credits()
    {
        return $this->hasMany(Credit::class, 'person_id');
    }
}
