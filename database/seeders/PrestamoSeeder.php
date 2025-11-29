<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prestamo;
use App\Models\Alumno;
use App\Models\Copia;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PrestamoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Asegurar datos básicos
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Seed Admin',
                'email' => 'admin@local.test',
                'password' => bcrypt('123456'),
                'rol' => 0,
            ]);
            $this->command->info('Se creó usuario admin por defecto.');
        }

        if (Alumno::count() === 0) {
            Alumno::factory(10)->create();
            $this->command->info('Se crearon 10 alumnos de ejemplo.');
        }

        if (Copia::count() === 0) {
            // crea libros/ubicaciones si hacen falta dentro de CopiaFactory
            Copia::factory(30)->create();
            $this->command->info('Se crearon 30 copias de ejemplo.');
        }

        $users = User::all();
        $alumnos = Alumno::all();
        $copias = Copia::all();

        if ($alumnos->isEmpty() || $copias->isEmpty()) {
            $this->command->error('PrestamoSeeder: faltan alumnos o copias. Aborting.');
            return;
        }

        // 2) Crear X prestamos (cada préstamo luego asocia 1..3 copias)
        $toCreate = min(50, max(5, (int) floor($copias->count() / 2)));

        for ($i = 0; $i < $toCreate; $i++) {
            $user = $users->random();
            $alumno = $alumnos->random();

            // fechas obligatorias en tu tabla: fecha_prestamo, fecha_devolucion_prevista
            $fechaPrestamo = now()->subDays(rand(0, 30));
            $fechaDevolucionPrevista = (clone $fechaPrestamo)->addDays(14);

            // crear prestamo con los campos requeridos
            $prestamo = Prestamo::create([
                'fecha_prestamo' => $fechaPrestamo->format('Y-m-d H:i:s'),
                'fecha_devolucion_prevista' => $fechaDevolucionPrevista->format('Y-m-d H:i:s'),
                // la columna fecha_devolucion_real es nullable según DESCRIBE, no hace falta setearla
                'estado' => 'activo',
                'observaciones' => null,
                'id_usuario' => $user->{$user->getKeyName()} ?? null,
                'rut_alumno' => $alumno->rut_alumno ?? ($alumno->{$alumno->getKeyName()} ?? null),
            ]);

            // 3) Asociar 1..3 copias al préstamo mediante la relación many-to-many
            $numCopias = rand(1, 3);
            $copiasSeleccionadas = $copias->random($numCopias)->values();

            // prepararmos array para attach: [copiaId => ['estado'=>..., 'fecha_prestamo'=>...], ...]
            $attachData = [];
            foreach ($copiasSeleccionadas as $c) {
                $cKeyName = $c->getKeyName();          // p.ej. 'id_copia'
                $cKeyValue = $c->{$cKeyName};

                // el pivot en tu modelo Prestamo define ->withPivot(['estado','fecha_prestamo'])
                $attachData[$cKeyValue] = [
                    'estado' => 'activo',
                    'fecha_prestamo' => $fechaPrestamo->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // attach usando la relación definida en Prestamo model
            $prestamo->copias()->attach($attachData);

            $this->command->info("Prestamo {$prestamo->getKey()} creado y asociado a " . count($attachData) . " copias.");
        }

        $this->command->info('PrestamoSeeder completado.');
    }
}
