<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Libro;
use App\Models\Ubicacion;
use App\Models\Prestamo;

class Copia extends Model
{
    protected $table = 'copia';
    protected $primaryKey = 'id_copia';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['id_libro_interno','estado','id_ubicacion'];

    // Relación: copia pertenece a un libro
    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro_interno', 'id_libro_interno');
    }

    // Copia pertenece a una ubicacion
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion', 'id_ubicacion');
    }

    public function prestamos()
    {
        return $this->belongsToMany(
            Prestamo::class,
            'copias_prestamos',
            'id_copia',
            'id_prestamo'
        )->withPivot(['estado','fecha_prestamo'])
         ->withTimestamps();
    }

    /**
     * Mapa clave DB-safe => etiqueta visible
     */
     public static function estadosMap(): array
    {
        return [
            'disponible'     => 'Disponible',
            'prestado'       => 'Prestado',
            'mal_estado'     => 'Mal estado',
            'perdido'        => 'Perdido',
            'no_disponible'  => 'No disponible',
        ];
    }

    /**
     * Valores permitidos para guardar en DB (las claves del mapa)
     */
    public static function estadosPermitidos(): array
    {
        return array_values(self::estadosMap());
    }

    /**
     * Normaliza una entrada libre a la clave DB-safe.
     * Retorna null si no reconoce nada (evita insertar valores no permitidos).
     */
    public static function normalizeEstado(?string $input): ?string
    {
        if (is_null($input)) return null;

        $s = mb_strtolower(trim($input));
        if ($s === '') return null;

        // Mapa de variantes a la clave interna
        $variants = [
            'mal estado' => 'mal_estado',
            'mal_estado' => 'mal_estado',
            'mal-estado' => 'mal_estado',
            'malestado'  => 'mal_estado',
            'no disponible' => 'no_disponible',
            'no_disponible' => 'no_disponible',
            'no-disponible' => 'no_disponible',
            'disponible' => 'disponible',
            'prestado' => 'prestado',
            'perdido' => 'perdido',
        ];

        if (isset($variants[$s])) {
            $key = $variants[$s];
            // devolvemos la etiqueta (valor real para DB)
            return self::estadosMap()[$key] ?? null;
        }

        // Intentamos mapear transformando espacios/guiones -> underscore y ver si coincide con key
        $keyGuess = str_replace([' ', '-'], '_', $s);
        if (array_key_exists($keyGuess, self::estadosMap())) {
            return self::estadosMap()[$keyGuess];
        }

        // También intentamos si la entrada ya coincide con alguna etiqueta (p. ej. "Mal estado")
        foreach (self::estadosMap() as $k => $label) {
            if (mb_strtolower($label) === $s) return $label;
        }

        // No reconocido -> null (evitamos guardar un valor inválido que genere truncado)
        return null;
    }

    /**
     * Obtener etiqueta legible para mostrar en UI
     */
    public static function labelForEstado(?string $value): string
    {
        if (empty($value)) return self::estadosMap()['disponible'] ?? 'Disponible';

        // Si ya es una etiqueta conocida, devolverla
        foreach (self::estadosMap() as $k => $label) {
            if ($label === $value) return $label;
        }

        // Si viene la clave, devolver la etiqueta
        if (array_key_exists($value, self::estadosMap())) {
            return self::estadosMap()[$value];
        }

        // Fallback
        return ucfirst(str_replace('_',' ', (string)$value));
    }

    // Observers para recalcular stock cuando se crean/actualizan/eliminan copias
    protected static function booted()
    {
        static::created(function (Copia $copia) {
            if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
                $copia->libro->recalcularStock();
            }
        });

        static::updated(function (Copia $copia) {
            if ($copia->isDirty('id_libro_interno')) {
                $originalLibroId = $copia->getOriginal('id_libro_interno');
                if ($originalLibroId) {
                    $old = Libro::find($originalLibroId);
                    if ($old && method_exists($old, 'recalcularStock')) $old->recalcularStock();
                }
            }
            if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
                $copia->libro->recalcularStock();
            }
        });

        static::deleted(function (Copia $copia) {
            if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
                $copia->libro->recalcularStock();
            }
        });
    }
}
