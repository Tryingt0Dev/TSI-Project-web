<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['titulo'] }}</title>
    <style> /* estilos similares */ </style>
</head>
<body>
    <h2 style="text-align:center">{{ $data['titulo'] }}</h2>
    <div>Periodo: {{ $data['filtro']['periodo_tipo'] ?? 'Todos' }}</div>
    <table>
        <thead><tr><th>#</th><th>Título</th><th>Veces prestado</th></tr></thead>
        <tbody>
            @foreach($data['rows'] as $i => $r)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $r->titulo ?? $r['titulo'] ?? 'N/A' }}</td>
                    <td>{{ $r->veces ?? $r['total'] ?? '0' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
