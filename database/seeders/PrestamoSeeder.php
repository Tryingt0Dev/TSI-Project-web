<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prestamo;
use App\Models\Alumno;
use App\Models\User;
use App\Models\Copia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrestamoSeeder extends Seeder
{
    public function run(): void
    {
        // Evitar crear duplicados si ya existen prestamos
        if (Prestamo::count() > 0) {
            $this->command->info('Prestamos ya existen en la BD, se omite PrestamoSeeder.');
            return;
        }

        // Verificar dependencias mínimas
        $alumnos = Alumno::pluck('rut_alumno')->toArray();
        $users = User::pluck('id')->toArray();
        $copiasDisponibles = Copia::where('estado', 'disponible')->pluck('id_copia')->toArray();

        if (empty($alumnos) || empty($users) || empty($copiasDisponibles)) {
            $this->command->info('Faltan datos previos: asegúrate de tener Alumnos, Users y Copias (disponibles).');
            return;
        }

        $numPrestamos = min(200, (int) floor(count($copiasDisponibles) / 1)); // limitar según copias disponibles

        // Mezcla aleatoria los ids de copias para repartir
        shuffle($copiasDisponibles);

        DB::beginTransaction();
        try {
            $copiasIndex = 0;
            for ($i = 0; $i < $numPrestamos; $i++) {
                // seleccionar datos aleatorios
                $rut = $alumnos[array_rand($alumnos)];
                $userId = $users[array_rand($users)];

                $fechaPrestamo = Carbon::now()->subDays(rand(0, 120));
                $fechaPrevista = (clone $fechaPrestamo)->addDays(rand(7, 30));

                $prestamo = Prestamo::create([
                    'id_usuario' => $userId,
                    'rut_alumno' => $rut,
                    'fecha_prestamo' => $fechaPrestamo->toDateString(),
                    'fecha_devolucion_prevista' => $fechaPrevista->toDateString(),
                    'estado' => 'activo',
                    'observaciones' => null,
                ]);

                // Asociar entre 1 y 3 copias (si quedan)
                $take = rand(1, 3);
                $selected = array_slice($copiasDisponibles, $copiasIndex, $take);
                $copiasIndex += count($selected);

                if (empty($selected)) {
                    break;
                }

                foreach ($selected as $id_copia) {
                    $copia = Copia::find($id_copia);
                    if (!$copia) continue;

                    // actualizar estado de la copia
                    $copia->estado = 'prestado';
                    $copia->save();

                    // attach en la tabla pivot 'copias_prestamos'
                    $prestamo->copias()->attach($id_copia, [
                        'estado' => 'prestado',
                        'fecha_prestamo' => $fechaPrestamo->toDateString(),
                    ]);
                }
            }

            DB::commit();
            $this->command->info("PrestamoSeeder: creado {$i} préstamos (aprox.).");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("PrestamoSeeder falló: " . $e->getMessage());
            throw $e;
        }
    }
}
