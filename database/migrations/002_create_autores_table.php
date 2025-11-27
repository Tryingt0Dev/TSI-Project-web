<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('autores', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            // clave primaria
            $table->id('id_autor');

            // columnas
            $table->string('nombre');
            $table->string('apellido')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autores');
    }
    
};