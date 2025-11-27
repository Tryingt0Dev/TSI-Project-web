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
    protected $table = 'libros';
    protected $primaryKey = 'id_libro_interno';
    public $incrementing = true;
    protected $keyType = 'int';
    
    protected $fillable = [
        'id_libro_interno',
        'isbn_libro',
        'titulo',
        'fecha_publicacion',
        'editorial',
        'id_genero',
        'stock_total',      
        'stock_disponible',
        'id_ubicacion',   
        'id_autor',       
        'imagen',
    ];
    
    public function autor()
    {
        return $this->belongsTo(Autor::class, 'id_autor', 'id_autor');
    }
    public function autores()
    {
        return $this->belongsToMany(
            \App\Models\Autor::class,
            'autor_libro',
            'id_libro_interno', // FK en la tabla pivote hacia libros
            'id_autor'         // FK en la tabla pivote hacia autores
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
    
    public function recalcularStock(): void
    {
        
        $total = $this->copias()->count();
        $disponible = $this->copias()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'no disponible');
        })->count();

        
        $dirty = false;
        if ($this->stock_total !== $total) {
            $this->stock_total = $total;
            $dirty = true;
        }
        if ($this->stock_disponible !== $disponible) {
            $this->stock_disponible = $disponible;
            $dirty = true;
        }

        if ($dirty) {
            
            $this->saveQuietly();
        }
    }
}