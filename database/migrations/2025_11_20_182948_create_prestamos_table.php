<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id('id_prestamo');
            $table->unsignedBigInteger('id_usuario'); // Usuario encargado del préstamo
            $table->string('rut_alumno', 10); // Rut del alumno involucrado
            $table->unsignedBigInteger('id_copia');
            $table->date('fecha_inicio');
            $table->date('fecha_limite');
            $table->tinyInteger('entregado')->default(0); // 0: Pendiente, 1: Devuelto, 2: Perdido
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
