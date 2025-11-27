<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Alumno extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'alumnos';
    protected $primaryKey = 'rut_alumno';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rut_alumno',
        'nombre_alumno',
        'apellido_alumno',
        'fecha_registro',
        'retrasos',
        'permiso_prestamo',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'permiso_prestamo' => 'boolean',
        'retrasos' => 'integer',
    ];

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'rut_alumno', 'rut_alumno');
    }
    protected static function booted()
    {
        static::saving(function ($alumno) {
            $alumno->permiso_prestamo = $alumno->retrasos <= 3;
        });
    }
}
