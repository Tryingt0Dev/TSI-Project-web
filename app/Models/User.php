<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'rut',
        'email',
        'password',
        'rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function esAdmin(): bool
    {
        return $this->rol === 0;
    }
    public function esBibliotecario(): bool
    {
        return $this->rol === 1;
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
     public function setRutAttribute($value)
    {
        if ($value === null) {
            $this->attributes['rut'] = null;
            return;
        }

        $value = trim((string)$value);
        // eliminar espacios extra y puntos
        $value = str_replace([' ', '.'], '', $value);
        $value = strtoupper($value);

        if (strpos($value, '-') !== false) {
            [$numPart, $dvPart] = explode('-', $value, 2);
            $num = preg_replace('/\D+/', '', $numPart);
            $dv = preg_replace('/\s+/', '', strtoupper($dvPart));
            // Guardar exactamente lo que el usuario ingresó (números limpios + '-' + DV en mayúscula)
            $this->attributes['rut'] = $num . '-' . $dv;
        } else {
            // el usuario no envió DV, calculamos y guardamos el RUT formateado
            $num = preg_replace('/\D+/', '', $value);
            if ($num === '') {
                $this->attributes['rut'] = $value; // dejar como vino (por si viene formato raro)
                return;
            }
            $dv = self::calcRutDv($num);
            $this->attributes['rut'] = $num . '-' . $dv;
        }
    }

    /**
     * Accesor: retorna el RUT formateado con DV.
     * - Si en la BD existe 'NNNNNNNN-X' lo devuelve tal cual (normalizado).
     * - Si en la BD hay solo números (o formato distinto) calcula el DV y lo devuelve formateado.
     */
    public function getRutConDvAttribute()
    {
        $rutRaw = $this->attributes['rut'] ?? null;

        if (!$rutRaw) {
            return null;
        }

        // Si ya contiene guion, asumimos viene con DV; normalizamos y devolvemos.
        if (strpos($rutRaw, '-') !== false) {
            [$numPart, $dvPart] = explode('-', $rutRaw, 2);
            $num = preg_replace('/\D+/', '', $numPart);
            $dv = strtoupper(preg_replace('/\s+/', '', $dvPart));
            if ($num === '') {
                return $rutRaw; // algo raro, devolver crudo
            }
            return $num . '-' . $dv;
        }

        // Si no tiene guion, podría ser solo números: calculamos DV y devolvemos formateado
        $rutNumbers = preg_replace('/\D+/', '', $rutRaw);
        if ($rutNumbers === '') {
            return $rutRaw;
        }

        $dv = self::calcRutDv($rutNumbers);
        return $rutNumbers . '-' . $dv;
    }

    /**
     * Calcula el dígito verificador (módulo 11).
     * Devuelve '0'..'9' o 'K'.
     */
    public static function calcRutDv(string $rutNumbers)
    {
        $reversed = strrev($rutNumbers);
        $factor = 2;
        $sum = 0;
        for ($i = 0, $len = strlen($reversed); $i < $len; $i++) {
            $sum += intval($reversed[$i]) * $factor;
            $factor++;
            if ($factor > 7) $factor = 2;
        }
        $rest = $sum % 11;
        $dv = 11 - $rest;
        if ($dv == 11) return '0';
        if ($dv == 10) return 'K';
        return (string)$dv;
    }
}
