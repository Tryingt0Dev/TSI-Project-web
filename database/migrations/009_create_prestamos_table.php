<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            // clave primaria
            $table->id('id_prestamo');

            // columnas
            $table->date('fecha_inicio');
            $table->date('fecha_limite');
            $table->string('estado')->default('Pendiente'); // 0: Pendiente, 1: Devuelto, 2: Perdido

            // claves foraneas
            $table->unsignedBigInteger('id_copia');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('rut_alumno');
            $table->foreign('id_copia')->references('id_copia')->on('copia')->onDelete('cascade');
            $table->foreign('id_usuario')->references('id')->on('users')->onDelete('cascade'); // Usuario encargado del préstamo
            $table->foreign('rut_alumno')->references('rut_alumno')->on('alumnos')->onDelete('cascade'); // Rut del alumno involucrado
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
