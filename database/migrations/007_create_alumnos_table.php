<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            // PK RUT
            $table->string('rut_alumno', 15)->primary();

            // Datos personales
            $table->string('nombre_alumno', 60);
            $table->string('apellido_alumno', 60);

            // Fecha de registro
            $table->date('fecha_registro')->default(now());

            // Cantidad de retrasos acumulados
            $table->unsignedInteger('retrasos')->default(0);

            // Permiso para realizar préstamos (TRUE/FALSE)
            $table->boolean('permiso_prestamo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
