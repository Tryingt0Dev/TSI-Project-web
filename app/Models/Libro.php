<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Libro extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id',
        'isbn_libro',
        'titulo',
        'fecha_publicacion',
        'editorial',
        'genero_id',
        'stock_total',      
        'stock_disponible',
        'ubicacion_id',   
        'autor_id',       
        'imagen',
    ];
    

    public function autor()
    {
        return $this->belongsTo(Autor::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function genero()
    {
        return $this->belongsTo(GeneroLiterario::class, 'genero_id');
    }
    public function copias(): HasMany
    {
        return $this->hasMany(Copia::class, 'id_libro_interno', 'id_libro_interno');
    }
}
