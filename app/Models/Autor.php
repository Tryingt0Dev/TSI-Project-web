<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Autor extends Model
{
    use HasFactory;

    protected $table = 'autores';       // si tu tabla se llama autores
    protected $primaryKey = 'id_autor'; // <- CORRECTO a tu esquema
    public $incrementing = true;        // o false si no autoincrement
    protected $keyType = 'int';         // o 'string' si es texto

    protected $fillable = ['nombre'];   // añade otros campos si los tienes

    public function libros()
    {
        return $this->belongsToMany(
            \App\Models\Libro::class,
            'autor_libro',
            'id_autor',
            'id_libro_interno'
        )->withTimestamps();
    }
}
