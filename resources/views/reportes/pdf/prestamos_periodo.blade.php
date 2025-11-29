<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $data['titulo'] }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width:100%; border-collapse: collapse; margin-top:10px; }
    th, td { border:1px solid #ddd; padding:6px; text-align:left; }
    th { background:#f5f5f5; }
    .center { text-align:center; }
  </style>
</head>
<body>
  <div class="center">
    <h2>{{ $data['titulo'] }}</h2>
    @if(!empty($data['filtro']['periodo'])) <div>{{ $data['filtro']['periodo'] }}</div> @endif
    <div>Generado: {{ now()->format('Y-m-d H:i') }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Mes</th>
        <th>Total préstamos</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data['rows'] as $r)
        <tr>
          <td>{{ $r['mes_nombre'] ?? ($r['fecha'] ?? $r['mes'] ?? '') }}</td>
          <td class="center">{{ $r['total'] ?? $r['veces'] ?? 0 }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
