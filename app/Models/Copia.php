<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Copia extends Model
{
    protected $table = 'copia';
    protected $primaryKey = 'id_copia';
    protected $fillable = ['id_libro_interno','estado','id_ubicaciones'];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro_interno', 'id');
    }

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
