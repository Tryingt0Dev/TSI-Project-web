<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    // La tabla tiene PK 'id' por lo que no hace falta definir $primaryKey.
    // Si prefieres explicitarlo, usa: protected $primaryKey = 'id';

    protected $fillable = [
        'estante',
        'seccion'
        ];

    // Relación: una ubicación puede tener muchos libros (si aplica)
    public function libros()
    {
        return $this->hasMany(\App\Models\Libro::class, 'ubicacion_id', 'id');
        // Ajusta 'ubicacion_id' si en la tabla libros la FK tiene otro nombre.
    }
}