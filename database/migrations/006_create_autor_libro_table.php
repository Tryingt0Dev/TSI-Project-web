<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autor_libro', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_autor');
            $table->unsignedBigInteger('id_libro_interno');
            $table->foreign('id_libro_interno')->references('id_libro_interno')->on('libros')->cascadeOnDelete();
            $table->foreign('id_autor')->references('id_autor')->on('autores')->cascadeOnDelete();

            $table->timestamps();
            $table->unique(['id_libro_interno','id_autor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autor_libro');
    }
};
