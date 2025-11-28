<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Libro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'libros';
    protected $primaryKey = 'id_libro_interno';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Define aquí los campos que realmente existen en tu tabla libros.
     * Evita incluir PKs o columnas que no existan en todas las instalaciones.
     */
    protected $fillable = [
        'isbn_libro',
        'titulo',
        'fecha_publicacion',
        'editorial',
        'id_genero',
        'stock_total',
        'stock_disponible',
        'id_ubicacion',
        'imagen',
    ];

    // Relaciones
    public function autor()
    {
        return $this->belongsTo(Autor::class, 'id_autor', 'id_autor');
    }

    public function autores()
    {
        return $this->belongsToMany(
            \App\Models\Autor::class,
            'autor_libro',
            'id_libro_interno',
            'id_autor'
        )->withTimestamps();
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion', 'id_ubicacion');
    }

    public function genero()
    {
        return $this->belongsTo(Genero::class, 'id_genero', 'id_genero');
    }

    public function copias(): HasMany
    {
        return $this->hasMany(Copia::class, 'id_libro_interno', 'id_libro_interno');
    }

    /**
     * Recalcula stock_total y stock_disponible en base a las copias reales.
     * Usa saveQuietly para evitar eventos anidados (si corresponde).
     */
    public function recalcularStock(): void
    {
        $total = $this->copias()->count();

        // Disponibles: estado NULL o distinto a cualquiera de las formas de "prestado"
        $disponible = $this->copias()->where(function ($q) {
            $q->whereNull('estado')
              ->orWhereNotIn('estado', ['prestado', 'Prestada', 'Prestado', 'no disponible']);
        })->count();

        $dirty = false;
        if ((int) $this->stock_total !== (int) $total) {
            $this->stock_total = $total;
            $dirty = true;
        }
        if ((int) $this->stock_disponible !== (int) $disponible) {
            $this->stock_disponible = $disponible;
            $dirty = true;
        }

        if ($dirty) {
            // saveQuietly para no disparar observers que puedan provocar loops
            $this->saveQuietly();
        }
    }
}
