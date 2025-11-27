<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $table = 'prestamos';
    protected $primaryKey = 'id_prestamo';

    protected $fillable = [
        'id_usuario',
        'rut_alumno',
        'id_copia',
        'fecha_inicio',
        'fecha_limite',
        'estado',
    ];

    public $timestamps = false;
}