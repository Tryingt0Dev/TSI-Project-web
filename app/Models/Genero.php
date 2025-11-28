<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Genero extends Model
{
    protected $table = 'generos';
    protected $primaryKey = 'id_genero';
    protected $fillable = ['nombre'];

    public function libros()
    {
        return $this->hasMany(Libro::class, 'genero_id');
    }
}