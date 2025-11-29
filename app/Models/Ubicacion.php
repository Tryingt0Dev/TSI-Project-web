<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    // <-- CORRECCIÓN IMPORTANTE: nombre real de la PK en la tabla
    protected $primaryKey = 'id_ubicacion';

    // si la PK es unsignedBigInteger auto-incrementing (como tu migration), mantener true
    public $incrementing = true;

    // tipo de la PK: 'int' o 'string' según corresponda; tu migration usa bigint -> int correcto
    protected $keyType = 'int';

    // columnas asignables (ajusta si tienes más)
    protected $fillable = [
        'estante',
        'seccion',
    ];

    // Relación: una ubicación puede tener muchos libros.
    // La FK en la tabla libros según tu migration es 'id_ubicacion'
    // y la PK local es 'id_ubicacion' (ya declarado arriba).
    public function libros()
    {
        return $this->hasMany(\App\Models\Libro::class, 'id_ubicacion', 'id_ubicacion');
    }
}
