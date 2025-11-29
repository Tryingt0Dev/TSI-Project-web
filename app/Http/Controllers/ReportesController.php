<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Libro;
use App\Models\Copia;
use Barryvdh\DomPDF\Facade\Pdf; // facade correcto
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class ReportesController extends Controller
{
    public function index()
    {
        // Métricas a mostrar como mini tarjetas
        $totalPrestamos30 = Prestamo::where('fecha_prestamo', '>=', now()->subDays(30))->count();

        // Normalizamos la comparación usando LOWER en la consulta
        $copiasPrestadas = DB::table('copias_prestamos')
            ->whereRaw("TRIM(LOWER(COALESCE(estado, ''))) = ?", ['prestado'])
            ->count();
        $totalLibros = Libro::count();
        $totalCopias = Copia::count();
        $totalPrestamos = Prestamo::count();

        // Tasa de rotación de inventario = total préstamos / total ejemplares (evitar división por 0)
        $tasaRotacion = $totalLibros > 0 ? round($totalPrestamos / max(1, $totalLibros), 2) : 0;

        // Tiempo promedio de préstamo en días (solo prestamos con fecha_devolucion_real)
        $avgDays = Prestamo::whereNotNull('fecha_devolucion_real')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, fecha_prestamo, fecha_devolucion_real)) as avg_days')
            ->value('avg_days');
        $tiempoPromedioDias = $avgDays ? round($avgDays, 2) : null;

        // Proporción de pérdidas (perdido/dañado) sobre total copias
        $perdidasCount = Copia::whereIn(DB::raw('LOWER(estado)'), ['perdido','dañado','danado'])->count();
        $proporcionPerdidas = $totalCopias > 0 ? round(($perdidasCount / $totalCopias) * 100, 2) : 0;

        // Demanda por género (top 6)
        $demandaGenero = DB::table('copias_prestamos')
            ->join('copia', 'copias_prestamos.id_copia', '=', 'copia.id_copia')
            ->join('libros', 'copia.id_libro_interno', '=', 'libros.id_libro_interno')
            ->join('generos', 'libros.id_genero', '=', 'generos.id_genero')
            ->select('generos.nombre', DB::raw('COUNT(*) as veces'))
            ->groupBy('generos.nombre')
            ->orderByDesc('veces')
            ->limit(6)
            ->get();

        // Usuarios recurrentes (top 6 alumnos por número de préstamos)
        $usuariosRecurrentes = DB::table('prestamos')
            ->select('rut_alumno', DB::raw('COUNT(*) as veces'))
            ->groupBy('rut_alumno')
            ->orderByDesc('veces')
            ->limit(6)
            ->get();

        // Tasa de incumplimiento (vencidos %)
        $vencidosCount = Prestamo::whereRaw("LOWER(estado) = ?", ['vencido'])->count();
        $tasaIncumplimiento = $totalPrestamos > 0 ? round(($vencidosCount / $totalPrestamos) * 100, 2) : 0;

        // Porcentaje de ejemplares en reparación/perdidos (similar a proporcionPerdidas)
        $reparacionCount = Copia::whereRaw("LOWER(estado) = ?", ['reparacion'])->count();
        $porcReparacion = $totalCopias > 0 ? round(($reparacionCount / $totalCopias) * 100, 2) : 0;

        return view('reportes.index', compact(
            'totalPrestamos30',
            'copiasPrestadas',
            'tasaRotacion',
            'tiempoPromedioDias',
            'proporcionPerdidas',
            'demandaGenero',
            'usuariosRecurrentes',
            'tasaIncumplimiento',
            'porcReparacion'
        ));
    }

    /**
     * Generar el informe seleccionado y devolver PDF descargable/preview.
     */
    public function generar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'periodo_tipo' => 'nullable|string',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer',
            'top_n' => 'nullable|integer',
        ]);

        $tipo = $request->input('tipo');
        $periodoTipo = $request->input('periodo_tipo', null);
        $year = $request->filled('year') ? intval($request->input('year')) : Carbon::now()->year;
        $month = $request->filled('month') ? intval($request->input('month')) : Carbon::now()->month;
        $topN = $request->filled('top_n') ? intval($request->input('top_n')) : 10;

        $data = [
            'titulo' => '',
            'filtro' => [],
            'rows' => [],
        ];

        try {
            if ($tipo === 'prestamos_periodo') {
                if ($periodoTipo === 'mes') {
                    $start = Carbon::create($year, $month, 1)->startOfDay();
                    $end = (clone $start)->endOfMonth()->endOfDay();

                    $rows = Prestamo::selectRaw("DATE(fecha_prestamo) as fecha, COUNT(*) as total")
                        ->whereBetween('fecha_prestamo', [$start, $end])
                        ->groupBy('fecha')
                        ->orderBy('fecha')
                        ->get()
                        ->toArray();

                    $data['titulo'] = "Préstamos por día — " . $start->format('F Y');
                    $data['filtro'] = ['periodo' => $start->format('F Y')];
                    $data['rows'] = $rows;
                    $view = 'reportes.pdf.prestamos_periodo';
                } else { // año
                    $start = Carbon::create($year, 1, 1)->startOfYear();
                    $end = (clone $start)->endOfYear();

                    $raw = Prestamo::selectRaw("MONTH(fecha_prestamo) as mes, COUNT(*) as total")
                        ->whereBetween('fecha_prestamo', [$start, $end])
                        ->groupBy('mes')
                        ->orderBy('mes')
                        ->get()
                        ->keyBy('mes'); // clave por número de mes

                    // crear array meses 1..12 rellenando ceros donde no hay datos
                    $rows = [];
                    for ($m = 1; $m <= 12; $m++) {
                        if (isset($raw[$m])) {
                            $rows[] = [
                                'mes' => $m,
                                'mes_nombre' => Carbon::create()->month($m)->format('F'),
                                'total' => (int) $raw[$m]->total,
                            ];
                        } else {
                            $rows[] = [
                                'mes' => $m,
                                'mes_nombre' => Carbon::create()->month($m)->format('F'),
                                'total' => 0,
                            ];
                        }
                    }

                    $data['titulo'] = "Préstamos por mes — {$year}";
                    $data['filtro'] = ['periodo' => $year];
                    $data['rows'] = $rows;
                    $view = 'reportes.pdf.prestamos_periodo';
                }
            } elseif ($tipo === 'libros_populares') {
                // definir rango si existe periodo
                if ($periodoTipo === 'mes') {
                    $start = Carbon::create($year, $month, 1)->startOfDay();
                    $end = (clone $start)->endOfMonth()->endOfDay();
                } elseif ($periodoTipo === 'anio') {
                    $start = Carbon::create($year, 1, 1)->startOfDay();
                    $end = (clone $start)->endOfYear()->endOfDay();
                } else {
                    $start = null; $end = null;
                }

                $query = DB::table('copias_prestamos')
                    ->join('copia', 'copias_prestamos.id_copia', '=', 'copia.id_copia')
                    ->join('libros', 'copia.id_libro_interno', '=', 'libros.id_libro_interno')
                    ->select('libros.id_libro_interno', 'libros.titulo', DB::raw('COUNT(*) as veces'));

                if ($start && $end) {
                    $query->whereBetween('copias_prestamos.created_at', [$start, $end]);
                }

                $rows = $query->groupBy('libros.id_libro_interno','libros.titulo')
                    ->orderByDesc('veces')
                    ->limit($topN)
                    ->get();

                $periodLabel = 'Todos los tiempos';
                if ($periodoTipo === 'mes') { $periodLabel = Carbon::create($year, $month, 1)->format('F Y'); }
                if ($periodoTipo === 'anio') { $periodLabel = (string)$year; }

                $data['titulo'] = "Libros más prestados — {$periodLabel}";
                $data['rows'] = $rows;
                $data['filtro'] = ['periodo_tipo' => $periodoTipo, 'year' => $year, 'month' => $month, 'top' => $topN];
                $view = 'reportes.pdf.libros_populares';

            } elseif ($tipo === 'libros_perdidos') {
                // contar copias con estado 'perdido' o 'dañado' (normalizar valores)
                $rows = Copia::selectRaw('id_libro_interno, COUNT(*) as total')
                    ->whereIn(DB::raw('LOWER(estado)'), ['perdido','dañado','danado'])
                    ->groupBy('id_libro_interno')
                    ->get()
                    ->map(function($r){
                        $libro = Libro::find($r->id_libro_interno);
                        return [
                            'id_libro_interno' => $r->id_libro_interno,
                            'titulo' => $libro ? $libro->titulo : 'N/A',
                            'total' => $r->total,
                        ];
                    })->toArray();

                $data['titulo'] = "Libros perdidos / dañados";
                $data['rows'] = $rows;
                $view = 'reportes.pdf.libros_perdidos';

            } else {
                return back()->withErrors('Tipo de informe no soportado.');
            }

            // Generar PDF
            $pdf = Pdf::loadView($view, ['data' => $data])->setPaper('a4', 'portrait');

            // nombre del archivo (usar Str::slug)
            $fileName = Str::slug($data['titulo'] ?: 'informe') . '_' . now()->format('Ymd_His') . '.pdf';

            // Si la petición quiere preview (preview=1) mostramos inline, si no forzamos descarga
            if ($request->boolean('preview')) {
                return $pdf->stream($fileName);
            }

            return $pdf->download($fileName);

        } catch (Exception $e) {
            // registrar para debugging
            Log::error("Error generando informe [{$tipo}]: " . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);

            // Si es petición AJAX, devolvemos JSON con error
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error generando el informe. Revisa logs del servidor.'
                ], 500);
            }

            // fallback: redirigir con error
            return back()->withErrors('Ocurrió un error al generar el informe. Revisa los logs.');
        }
    }
}
