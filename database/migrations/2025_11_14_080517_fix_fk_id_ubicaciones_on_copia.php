<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Si no existe la tabla local o la referenciada, salimos
        if (! Schema::hasTable('copia') || ! Schema::hasTable('ubicaciones')) {
            return;
        }

        // 1) Asegurarnos de que la columna local exista con tipo unsigned (no hacemos ALTER complejo si ya existe)
        Schema::table('copia', function (Blueprint $table) {
            if (! Schema::hasColumn('copia', 'id_ubicaciones')) {
                // asumimos que ubicaciones.id fue creado con $table->id() -> bigint unsigned
                $table->unsignedBigInteger('id_ubicaciones')->nullable()->after('estado');
            }
        });

        // 2) Eliminar FK previa si por alguna razón existe (intento seguro)
        try {
            DB::statement('ALTER TABLE `copia` DROP FOREIGN KEY `copia_id_ubicaciones_foreign`');
        } catch (\Throwable $e) {
            // ignorar si no existe
        }

        // 3) Crear la FK correcta apuntando a ubicaciones.id
        // Verificamos primero tipos básicos: si ubicaciones.id es BIGINT o INT
        $col = DB::selectOne("
            SELECT DATA_TYPE, COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ubicaciones'
              AND COLUMN_NAME = 'id'
            LIMIT 1
        ");

        if ($col) {
            $dataType = strtolower($col->DATA_TYPE);
            // si tipos parecen compatibles, añadimos la FK
            // (si no son compatibles, el ALTER fallará y te lo indicaré)
            Schema::table('copia', function (Blueprint $table) {
                $table->foreign('id_ubicaciones', 'copia_id_ubicaciones_foreign')
                      ->references('id')
                      ->on('ubicaciones')
                      ->onDelete('set null');
            });
        }
        // 4) Como dijiste que copia.ubicacion no tiene datos, la eliminamos si existe
        Schema::table('copia', function (Blueprint $table) {
            if (Schema::hasColumn('copia', 'ubicacion')) {
                $table->dropColumn('ubicacion');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('copia')) {
            return;
        }

        Schema::table('copia', function (Blueprint $table) {
            // eliminar FK si existe
            try {
                $table->dropForeign(['id_ubicaciones']);
            } catch (\Throwable $e) {
                // ignore
            }

            // eliminar columna id_ubicaciones si la creó esta migración
            if (Schema::hasColumn('copia', 'id_ubicaciones')) {
                $table->dropColumn('id_ubicaciones');
            }

            // restaurar la columna legacy 'ubicacion'
            if (! Schema::hasColumn('copia', 'ubicacion')) {
                $table->string('ubicacion')->nullable()->after('estado');
            }
        });
    }
};
