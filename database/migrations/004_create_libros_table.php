<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('libros', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            // clave primaria
            $table->id('id_libro_interno');

            // columnas
            $table->string('isbn_libro', 17)->unique();
            $table->string('titulo', 150);
            $table->date('fecha_publicacion')->nullable();
            $table->string('editorial');  
            $table->integer('stock_total')->default(0);
            $table->integer('stock_disponible')->default(0);
            $table->string('imagen')->nullable();

            // claves foreanas

            $table->unsignedBigInteger('id_autor');
            $table->unsignedBigInteger('id_ubicacion');
            $table->unsignedBigInteger('id_genero');
            $table->foreign('id_autor')->references('id_autor')->on('autores')->onDelete('cascade');
            $table->foreign('id_ubicacion')->references('id_ubicacion')->on('ubicaciones')->onDelete('cascade');
            $table->foreign('id_genero')->references('id_genero')->on('generos')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};
