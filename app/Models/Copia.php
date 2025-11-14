<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Copia extends Model
{
    protected $table = 'copia';
    protected $primaryKey = 'id_copia';
    public $incrementing = true; 
    protected $keyType = 'int';

    protected $fillable = [
        'id_libro_interno',
        'estado',
        'ubicacion',
    ];

    public function libro(): BelongsTo
    {
        // Libro::class debe existir y tener PK id_libro_interno
        return $this->belongsTo(Libro::class, 'id_libro_interno', 'id_libro_interno');
    }
}