<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            // clave primaria
            $table->id('id_ubicacion');

            // columnas
            $table->string('estante')->nullable();
            $table->string('seccion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
