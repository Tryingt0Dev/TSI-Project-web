<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        
        if (! Schema::hasTable('copia')) {
            Schema::create('copia', function (Blueprint $table) {
                // clave primaria
                $table->id('id_copia');

                // columnas
                $table->enum('estado', ['Disponible','Prestado','Perdido']);

                // claves foraneas
                $table->unsignedBigInteger('id_libro_interno');
                $table->unsignedBigInteger('id_ubicacion');
                $table->foreign('id_ubicacion')->references('id_ubicacion')->on('ubicaciones')->onDelete('cascade');
                $table->foreign('id_libro_interno')->references('id_libro_interno')->on('libros')->onDelete('cascade');

                $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        // Borrar la tabla
        Schema::dropIfExists('copia');
    }
};