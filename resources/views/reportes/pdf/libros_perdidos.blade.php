<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $data['titulo'] }}</title></head><body>
<h2 style="text-align:center">{{ $data['titulo'] }}</h2>
<table>
<thead><tr><th>#</th><th>Título</th><th>Cantidad</th></tr></thead>
<tbody>
@foreach($data['rows'] as $i => $r)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $r['titulo'] }}</td>
        <td>{{ $r['total'] }}</td>
    </tr>
@endforeach
</tbody>
</table>
</body></html>
