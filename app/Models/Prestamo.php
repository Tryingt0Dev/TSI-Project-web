<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB; // <-- añadido

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
        )->withPivot(['estado','fecha_prestamo','fecha_devolucion_real'])
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

    /**
     * Adjunta copias al prestamo y marca cada copia como 'prestado' (normalizado).
     * Recibe array de ids de copias (id_copia).
     */
    public function attachCopiasConEstado(array $copiasIds)
    {
        if (empty($copiasIds)) return;

        DB::transaction(function () use ($copiasIds) {
            // obtener las copias actuales (asegura que existan)
            $copias = \App\Models\Copia::whereIn('id_copia', $copiasIds)->get();

            $attach = [];
            foreach ($copias as $c) {
                // normalizar estado en minúsculas
                $c->estado = 'prestado';
                $c->save();

                $attach[$c->id_copia] = [
                    'estado' => 'prestado',
                    'fecha_prestamo' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // attach al pivot
            $this->copias()->attach($attach);
        });
    }

    /**
     * Detach copias y marca como disponible (si lo necesitas usar en destroy)
     */
    public function detachCopiasYMarcarDisponibles(array $copiasIds = [])
    {
        DB::transaction(function () use ($copiasIds) {
            if (!empty($copiasIds)) {
                \App\Models\Copia::whereIn('id_copia', $copiasIds)->update(['estado' => 'disponible']);
                $this->copias()->detach($copiasIds);
                return;
            }

            // si no se pasan ids, detacha todas las copias asociadas
            foreach ($this->copias as $c) {
                $c->estado = 'disponible';
                $c->save();
            }
            $this->copias()->detach();
        });
    }
}
