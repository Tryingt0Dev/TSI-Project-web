<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $tablaLocal = 'copia';
    protected string $columnaLocal = 'id_ubicaciones';
    protected string $tablaRef = 'ubicaciones';

    public function up(): void
    {
        // Si no existe la tabla local, salir
        if (! Schema::hasTable($this->tablaLocal) || ! Schema::hasTable($this->tablaRef)) {
            return;
        }

        // 1) Determinar la columna PK de la tabla referenciada (ej. 'id' o 'id_ubicaciones')
        $pk = DB::selectOne(<<<'SQL'
            SELECT k.COLUMN_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
              ON k.CONSTRAINT_NAME = t.CONSTRAINT_NAME
             AND k.TABLE_SCHEMA = t.TABLE_SCHEMA
            WHERE t.CONSTRAINT_TYPE = 'PRIMARY KEY'
              AND t.TABLE_SCHEMA = DATABASE()
              AND t.TABLE_NAME = ?
            LIMIT 1
        SQL, [$this->tablaRef]);

        if (! $pk || ! isset($pk->COLUMN_NAME)) {
            // no hay PK detectable, abortamos
            return;
        }

        $referencedColumn = $pk->COLUMN_NAME;

        // 2) Obtener tipo de la columna referenciada (int/bigint y si es unsigned)
        $colInfo = DB::selectOne(<<<'SQL'
            SELECT DATA_TYPE, COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        SQL, [$this->tablaRef, $referencedColumn]);

        if (! $colInfo) {
            return;
        }

        $dataType = strtolower($colInfo->DATA_TYPE); // 'int' o 'bigint' etc.
        $columnTypeFull = strtolower($colInfo->COLUMN_TYPE); // contains 'unsigned' maybe

        // 3) Asegurarnos de que ambas tablas usan InnoDB
        $engines = DB::select(<<<'SQL'
            SELECT TABLE_NAME, ENGINE
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN (?, ?)
        SQL, [$this->tablaLocal, $this->tablaRef]);

        foreach ($engines as $e) {
            if (strtoupper($e->ENGINE) !== 'INNODB') {
                // no creamos la FK si alguna no es InnoDB
                // pero igual intentaremos crear la columna local con el tipo correcto
                $engineOk = false;
                // continuamos para crear la columna; no se crea FK
            }
        }

        // 4) Crear/ajustar la columna local con tipo acorde: unsignedBigInteger para bigint unsigned, unsignedInteger para int unsigned
        Schema::table($this->tablaLocal, function (Blueprint $table) use ($dataType) {
            if (! Schema::hasColumn($this->tablaLocal, $this->columnaLocal)) {
                if (str_contains($dataType, 'big')) {
                    $table->unsignedBigInteger($this->columnaLocal)->nullable()->after('estado');
                } else {
                    $table->unsignedInteger($this->columnaLocal)->nullable()->after('estado');
                }
            } else {
                // si ya existe, no hacemos cambios (evitar ALTERs complejos en migración automatizada)
            }
        });

        // 5) Intentar crear la FK si engines son InnoDB y tipos son compatibles
        // comprobar compatibilidad: si la columnTypeFull contiene 'unsigned' o no
        $referencedUnsigned = str_contains($columnTypeFull, 'unsigned');

        // obtener COLUMN_TYPE de la columna local también (por si ya existía)
        $localCol = DB::selectOne(<<<'SQL'
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        SQL, [$this->tablaLocal, $this->columnaLocal]);

        $localColumnTypeFull = $localCol->COLUMN_TYPE ?? '';

        $localUnsigned = str_contains(strtolower($localColumnTypeFull), 'unsigned');

        // Si ambos son unsigned o ambos no (preferimos unsigned), procedemos a crear FK
        // También nos aseguramos que el tipo base coincide en tamaño (int vs bigint)
        $localBase = str_contains($localColumnTypeFull, 'big') ? 'big' : 'int';
        $refBase = str_contains($columnTypeFull, 'big') ? 'big' : 'int';

        if ($refBase === $localBase && $referencedUnsigned === $localUnsigned) {
            // Verificar si ya existe la FK
            $fkExists = DB::selectOne(<<<'SQL'
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME = ?
                  AND REFERENCED_COLUMN_NAME = ?
                LIMIT 1
            SQL, [$this->tablaLocal, $this->columnaLocal, $this->tablaRef, $referencedColumn]);

            if (! $fkExists) {
                Schema::table($this->tablaLocal, function (Blueprint $table) use ($referencedColumn) {
                    $table->foreign($this->columnaLocal)
                          ->references($referencedColumn)
                          ->on($this->tablaRef)
                          ->onDelete('set null');
                });
            }
        } else {
            // no coinciden los tipos: no crear FK automáticamente; informar en logs (no hay logs aquí)
            // Dev: puedes ajustar manualmente la columna local o la referenciada para que coincidan.
        }

        // 6) Como no hay datos en copia.ubicacion, podemos eliminar la columna legacy si existe
        Schema::table($this->tablaLocal, function (Blueprint $table) {
            if (Schema::hasColumn($this->tablaLocal, 'ubicacion')) {
                // dropColumn puede fallar si la columna no existe, por eso comprobamos
                $table->dropColumn('ubicacion');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->tablaLocal)) {
            return;
        }

        Schema::table($this->tablaLocal, function (Blueprint $table) {
            // 1) eliminar FK si existe
            try {
                $table->dropForeign([$this->columnaLocal]);
            } catch (\Throwable $e) {
                // ignore
            }

            // 2) eliminar la columna local id_ubicaciones si existe
            if (Schema::hasColumn($this->tablaLocal, $this->columnaLocal)) {
                $table->dropColumn($this->columnaLocal);
            }

            // 3) restaurar la columna legacy 'ubicacion' (nullable string)
            if (! Schema::hasColumn($this->tablaLocal, 'ubicacion')) {
                $table->string('ubicacion')->nullable()->after('estado');
            }
        });
    }
};