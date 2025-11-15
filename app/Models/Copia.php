<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Libro;
use App\Models\Ubicacion;

class Copia extends Model
{
    protected $table = 'copia';
    protected $primaryKey = 'id_copia';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['id_libro_interno','estado','id_ubicaciones'];

    // Relaciones

    // Copia pertenece a un libro
    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro_interno', 'id');
    }

    // Copia pertenece a una ubicacion (esta es la que faltaba)
    public function ubicacion()
    {
        // Si tu tabla ubicaciones tiene PK 'id' y la FK en copia es 'id_ubicaciones'
        return $this->belongsTo(Ubicacion::class, 'id_ubicaciones', 'id');
    }

    // Observers para recalcular stock (mantengo tu lógica)
    protected static function booted()
    {
        static::created(function (Copia $copia) {
            if ($copia->libro) $copia->libro->recalcularStock();
        });

        static::updated(function (Copia $copia) {
            if ($copia->isDirty('id_libro_interno')) {
                $originalLibroId = $copia->getOriginal('id_libro_interno');
                if ($originalLibroId) {
                    $old = Libro::find($originalLibroId);
                    if ($old) $old->recalcularStock();
                }
            }
            if ($copia->libro) $copia->libro->recalcularStock();
        });

        static::deleted(function (Copia $copia) {
            if ($copia->libro) $copia->libro->recalcularStock();
        });
    }
}
