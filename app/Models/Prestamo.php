<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestamo extends Model
{
    use SoftDeletes;

    protected $table = 'prestamos';
    protected $primaryKey = 'id_prestamo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'rut_alumno',
        'id_libros_prestados',
        'fecha_prestamo',
        'fecha_devolucion_prevista',
        'fecha_devolucion_real',
        'estado',
        'observaciones',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(\App\Models\Alumno::class, 'rut_alumno', 'rut_alumno');
    }

    public function libro()
    {
        return $this->belongsTo(\App\Models\Libro::class, 'id_libros_prestados', 'id_libro_interno');
    }

    public function copias()
    {
        return $this->belongsToMany(
            \App\Models\Copia::class,
            'prestamo_copia',
            'id_prestamo',
            'id_copia'
        )->withPivot(['fecha_asignacion','fecha_devolucion_real','devuelto','observaciones'])
         ->withTimestamps();
    }

    // Ejemplo helper: retornar copias activas (no devueltas)
    public function copiasActivas()
    {
        return $this->copias()->wherePivot('devuelto', false);
    }
}

