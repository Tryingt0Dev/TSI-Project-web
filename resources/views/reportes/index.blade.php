@extends('layouts.app')

@section('title','Informes')

@section('content')
<div class="container py-4">
    <h3>Informes</h3>

    {{-- Formulario para generar informe (sin target) --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="report-form" action="{{ route('informes.generar') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de informe</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="prestamos_periodo">Préstamos por periodo</option>
                            <option value="libros_populares">Libros más populares</option>
                            <option value="libros_perdidos">Libros perdidos / dañados</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Periodo tipo</label>
                        <select name="periodo_tipo" id="periodo_tipo" class="form-select">
                            <option value="">(N/A)</option>
                            <option value="mes">Mes</option>
                            <option value="anio">Año</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Año</label>
                        <input name="year" type="number" class="form-control" value="{{ date('Y') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Mes</label>
                        <select name="month" class="form-select">
                            <option value="">--</option>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Top</label>
                        <input name="top_n" type="number" class="form-control" value="10">
                    </div>
                </div>

                <div class="mt-3">
                    <button id="btn-generate" type="submit" class="btn btn-primary">Generar PDF</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Estadísticas rápidas (proporcionadas por el controller) --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card p-3">
                        <div class="h6">Total Prestamos (30 días)</div>
                        <div class="fs-4 fw-bold">{{ $totalPrestamos30 ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3">
                        <div class="h6">Copias prestadas actualmente</div>
                        <div class="fs-4 fw-bold">{{ $copiasPrestadas ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3">
                        <div class="h6">Libros distintos con préstamos (años)</div>
                        <div class="fs-4 fw-bold">{{ $usuariosRecurrentes->count() ?? 0 }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3">
                        <div class="h6">Tasa de incumplimiento</div>
                        <div class="fs-4 fw-bold">{{ $tasaIncumplimiento ?? 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Métricas extendidas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="h6">Tasa de rotación</div>
                <div class="fs-4 fw-bold">{{ $tasaRotacion ?? 0 }}</div>
                <div class="small text-muted">Préstamos / libros</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <div class="h6">Tiempo promedio (días)</div>
                <div class="fs-4 fw-bold">{{ $tiempoPromedioDias ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <div class="h6">Proporción pérdidas (%)</div>
                <div class="fs-4 fw-bold">{{ $proporcionPerdidas ?? 0 }}%</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <div class="h6">En reparación (%)</div>
                <div class="fs-4 fw-bold">{{ $porcReparacion ?? 0 }}%</div>
            </div>
        </div>
    </div>

    {{-- Demanda por género y usuarios top --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card p-3">
                <div class="h6">Demanda por género (top)</div>
                <div>
                    @foreach($demandaGenero as $g)
                        <div class="small">{{ $g->nombre }} — <strong>{{ $g->veces }}</strong></div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <div class="h6">Usuarios más activos</div>
                <div>
                    @foreach($usuariosRecurrentes as $u)
                        <div class="small">{{ $u->rut_alumno }} — <strong>{{ $u->veces }}</strong></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
/*
  Opción AJAX que solicita el PDF y fuerza su descarga usando blob.
  Útil si el navegador abre nueva pestaña en vez de descargar.
*/
document.getElementById('btn-generate-ajax').addEventListener('click', async function () {
    const form = document.getElementById('report-form');
    const url = form.action;
    const fd = new FormData(form);
    // Añade _token CSRF si no lo tiene (ya está en el form)
    try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token },
            body: fd
        });

        if (!res.ok) {
            const txt = await res.text();
            alert('Error al generar PDF. Revisa consola / network.');
            console.error(txt);
            return;
        }

        const blob = await res.blob();
        // comprobar tipo
        if (blob.type !== 'application/pdf') {
            // mostrar contenido para debug
            const text = await blob.text();
            console.error('Respuesta no es PDF:', text);
            alert('La respuesta no es PDF. Revisa la consola.');
            return;
        }

        const filename = res.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g,'') || 'informe.pdf';
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (err) {
        console.error(err);
        alert('Error de red al generar PDF. Revisa la consola.');
    }
});
</script>
@endpush
