<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backup previo: opcional pero recomendado
        // DB::statement('CREATE TABLE copia_backup AS SELECT * FROM copia');

        // Cambiamos la columna enum a varchar(50) permitiendo NULL y un default razonable
        DB::statement("ALTER TABLE `copia` 
            MODIFY `estado` VARCHAR(50) NULL DEFAULT 'Disponible'");
    }

    public function down(): void
    {
        // Nota: volver a ENUM puede fallar si hay valores no compatibles en la columna.
        // Si quieres revertir, asegúrate primero de normalizar los valores a los permitidos del ENUM.
        DB::statement("ALTER TABLE `copia` 
            MODIFY `estado` ENUM('Disponible','Prestado','Perdido') NOT NULL DEFAULT 'Disponible'");
    }
};
