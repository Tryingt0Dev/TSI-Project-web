<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id('id_prestamo'); // PK

            $table->unsignedBigInteger('user_id')->nullable(); // quien registró el préstamo (users.id)
            $table->string('rut_alumno', 20)->nullable();      // FK -> alumnos.rut_alumno
            $table->unsignedBigInteger('id_libros_prestados')->nullable(); // FK -> libros.id_libro_interno (opcional resumen)

            $table->dateTime('fecha_prestamo')->nullable();
            $table->dateTime('fecha_devolucion_prevista')->nullable();
            $table->dateTime('fecha_devolucion_real')->nullable();

            $table->enum('estado', ['activo','devuelto','vencido'])->default('activo');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rut_alumno')->references('rut_alumno')->on('alumnos')->onDelete('set null');
            $table->foreign('id_libros_prestados')->references('id_libro_interno')->on('libros')->onDelete('set null');

            $table->index(['rut_alumno']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
