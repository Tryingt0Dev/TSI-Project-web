<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('copias_prestamos', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            // Clave primaria
            $table->id('id_prestamo_copia');

            // Columnas
            $table->dateTime('fecha_devolucion_real')->nullable();
            $table->text('observaciones')->nullable();

            // Claves foraneas
            $table->unsignedBigInteger('id_copia');
            $table->unsignedBigInteger('id_prestamo');
            $table->foreign('id_copia')->references('id_copia')->on('copia')->onDelete('cascade');
            $table->foreign('id_prestamo')->references('id_prestamo')->on('prestamos')->onDelete('cascade');

            $table->timestamps();
            $table->unique(['id_prestamo','id_copia']);
            $table->index('id_prestamo');
            $table->index('id_copia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copias_prestamos');
    }
};