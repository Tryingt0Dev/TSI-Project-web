<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generos', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            // clave primaria
            $table->id('id_genero');

            // columnas
            $table->string('nombre')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generos');
    }
};
