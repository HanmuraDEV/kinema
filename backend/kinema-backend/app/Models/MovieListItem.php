<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieListItem extends Model
{
    use HasFactory;

    protected $fillable = ['movie_list_id', 'movie_id', 'sort_order'];

    // Este item representa a una película
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
