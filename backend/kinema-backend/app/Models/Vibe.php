<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vibe extends Model
{
    protected $fillable = ['name'];

    public function movies()
    {
        return $this->hasMany(Movie::class);
    }
}
