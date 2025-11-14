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
                
                $table->id('id_copia');

                
                $table->unsignedBigInteger('id_libro_interno');

                $table->string('estado')->nullable();
                $table->string('ubicacion')->nullable();
                $table->timestamps();

                
                $table->foreign('id_libro_interno')
                      ->references('id')
                      ->on('libros')
                      ->onDelete('cascade');
            });
        }

    }
    public function down(): void
    {
        // Borrar la tabla
        Schema::dropIfExists('copia');
    }
};