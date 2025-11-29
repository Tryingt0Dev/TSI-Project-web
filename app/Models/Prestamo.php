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
        'fecha_prestamo',
        'fecha_devolucion_prevista',
        'fecha_devolucion_real',
        'estado',
        'observaciones',
        'id_usuario',
        'rut_alumno',
    ];

    protected $casts = [
        'fecha_prestamo' => 'datetime',
        'fecha_devolucion_prevista' => 'datetime',
        'fecha_devolucion_real' => 'datetime',
    ];

    public function copias()
    {
        return $this->belongsToMany(
            \App\Models\Copia::class,
            'copias_prestamos',
            'id_prestamo',
            'id_copia'
        )->withPivot(['estado','fecha_prestamo'])
         ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_usuario', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(\App\Models\Alumno::class, 'rut_alumno', 'rut_alumno');
    }

}

