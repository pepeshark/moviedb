<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Director extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'tmdb_id', 'birthday', 'bio',
    ];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'director_movie', 'director_id', 'movie_id');//->withPivot('director_movie');
    }
}
