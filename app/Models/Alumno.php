<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $fillable = [
        'rut_alumno',
        'nombre_alumno',
        'apellido_alumno',
        'fecha_registro',
        'atrasos',
        'permiso_prestamo',
    ];
}