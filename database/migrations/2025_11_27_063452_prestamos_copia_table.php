<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestamo_copia', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('id_prestamo');
            $table->unsignedBigInteger('id_copia'); // FK -> copia.id_copia

            $table->dateTime('fecha_asignacion')->nullable();
            $table->dateTime('fecha_devolucion_real')->nullable();
            $table->boolean('devuelto')->default(false);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->foreign('id_prestamo')->references('id_prestamo')->on('prestamos')->onDelete('cascade');
            $table->foreign('id_copia')->references('id_copia')->on('copia')->onDelete('cascade');

            $table->unique(['id_prestamo','id_copia']);
            $table->index('id_prestamo');
            $table->index('id_copia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_copia');
    }
};
