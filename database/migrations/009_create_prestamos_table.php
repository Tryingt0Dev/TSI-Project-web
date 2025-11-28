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
            $table->dateTime('fecha_prestamo');
            $table->dateTime('fecha_devolucion_prevista');
            $table->dateTime('fecha_devolucion_real')->nullable();
            $table->enum('estado', ['activo','devuelto','vencido'])->default('activo');
            $table->text('observaciones')->nullable();

            // claves foraneas
            $table->unsignedBigInteger('id_usuario');
            $table->string('rut_alumno');
            $table->foreign('id_usuario')->references('id')->on('users')->onDelete('cascade'); // Usuario encargado del préstamo
            $table->foreign('rut_alumno')->references('rut_alumno')->on('alumnos')->onDelete('cascade'); // Rut del alumno involucrado
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
