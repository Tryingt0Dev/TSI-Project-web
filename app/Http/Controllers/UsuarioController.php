<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::paginate(10);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    /**
     * Normaliza y valida la entrada de RUT.
     *
     * - Acepta formatos: "12345678-5", "12.345.678-5", "123456785" (sin guion), "12345678" (sin DV).
     * - Si el usuario trae DV (con o sin guion) lo respeta y lo valida: si es incorrecto retorna error más arriba.
     * - Si no trae DV, calcula el DV y devuelve la versión normalizada "num-DV".
     *
     * Retorna array: [normalizedRut (string), num (string), dv (string), providedDv (bool)]
     */
    protected function normalizeRutStrict(string $value): array
    {
        $val = trim($value);
        // eliminar puntos y espacios
        $val = str_replace([' ', '.'], '', $val);
        $val = strtoupper($val);

        // Regex que captura: uno o más dígitos en grupo 1, opcionalmente un guion o no y un DV (0-9 o K)
        if (preg_match('/^(\d+)-?([0-9K])$/i', $val, $m)) {
            $num = $m[1];
            $dv = strtoupper($m[2]);
            $normalized = $num . '-' . $dv;
            return [$normalized, $num, $dv, true];
        }

        // Si solo son dígitos (sin DV)
        if (preg_match('/^(\d+)$/', $val, $m2)) {
            $num = $m2[1];
            $dv = User::calcRutDv($num);
            $normalized = $num . '-' . $dv;
            return [$normalized, $num, $dv, false];
        }

        // Formato inválido (le devolvemos lo crudo para que la capa superior lo maneje)
        return [$val, '', '', false];
    }

    /**
     * Lee la longitud máxima de la columna 'rut' en la tabla 'users' desde information_schema.
     * Si falla, devuelve un fallback (10).
     */
    protected function getRutColumnMaxLength(): int
    {
        try {
            $database = DB::getDatabaseName();
            $row = DB::table('information_schema.COLUMNS')
                ->select('CHARACTER_MAXIMUM_LENGTH')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'users')
                ->where('COLUMN_NAME', 'rut')
                ->first();

            if ($row && isset($row->CHARACTER_MAXIMUM_LENGTH) && is_numeric($row->CHARACTER_MAXIMUM_LENGTH)) {
                return (int)$row->CHARACTER_MAXIMUM_LENGTH;
            }
        } catch (\Throwable $e) {
            // Ignorar y usar fallback
        }

        return 10; // fallback seguro
    }

    public function store(Request $request)
    {
        // Validaciones básicas (no usamos unique sobre rut aquí porque lo hacemos sobre la versión normalizada)
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'rut'       => 'required|string|max:30',
            'email'     => 'required|email|unique:users,email',
            'rol'       => 'required|integer|in:0,1',
            'password'  => 'required|string|min:6|confirmed'
        ]);

        // Normalizar y examinar el RUT
        [$normalizedRut, $num, $dv, $providedDv] = $this->normalizeRutStrict($validated['rut']);

        // Si el formato fue inválido (num vacío) rechazamos
        if ($num === '') {
            return redirect()->back()
                ->withErrors(['rut' => 'Formato de RUT inválido. Usa 12345678-5 o 12345678.'])
                ->withInput();
        }

        // Verificar longitud de columna rut
        $maxLen = $this->getRutColumnMaxLength();
        if (strlen($normalizedRut) > $maxLen) {
            return redirect()->back()
                ->withErrors(['rut' => "El RUT normalizado ($normalizedRut) excede la longitud máxima de la columna (max $maxLen)."])
                ->withInput();
        }

        // Si el usuario proporcionó DV explícito, validar que coincida (no lo recalculamos para reemplazarlo)
        if ($providedDv) {
            $calc = User::calcRutDv($num);
            if ($dv !== $calc) {
                return redirect()->back()
                    ->withErrors(['rut' => 'Dígito verificador (DV) incorrecto para el RUT ingresado.'])
                    ->withInput();
            }
        }

        // Comprobar unicidad contra la versión normalizada
        $exists = DB::table('users')->where('rut', $normalizedRut)->exists();
        if ($exists) {
            return redirect()->back()
                ->withErrors(['rut' => 'Ya existe un usuario con ese RUT.'])
                ->withInput();
        }

        // Guardar usando la versión normalizada (num-DV)
        User::create([
            'name'     => $validated['name'],
            'apellido' => $validated['apellido'],
            'rut'      => $normalizedRut,
            'email'    => $validated['email'],
            'rol'      => $validated['rol'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente');
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        // Validaciones básicas
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'rut' => 'required|string|max:30',
            'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6|confirmed',
            'rol' => 'required|integer|in:0,1',
        ]);

        // Normalizar y examinar el RUT
        [$normalizedRut, $num, $dv, $providedDv] = $this->normalizeRutStrict($validated['rut']);

        if ($num === '') {
            return redirect()->back()
                ->withErrors(['rut' => 'Formato de RUT inválido. Usa 12345678-5 o 12345678.'])
                ->withInput();
        }

        // Verificar longitud de columna rut
        $maxLen = $this->getRutColumnMaxLength();
        if (strlen($normalizedRut) > $maxLen) {
            return redirect()->back()
                ->withErrors(['rut' => "El RUT normalizado ($normalizedRut) excede la longitud máxima de la columna (max $maxLen)."])
                ->withInput();
        }

        // Si el usuario proporcionó DV explícito, validar que coincida
        if ($providedDv) {
            $calc = User::calcRutDv($num);
            if ($dv !== $calc) {
                return redirect()->back()
                    ->withErrors(['rut' => 'Dígito verificador (DV) incorrecto para el RUT ingresado.'])
                    ->withInput();
            }
        }

        // Unicidad contra la versión normalizada (excluir al usuario actual)
        $exists = DB::table('users')
            ->where('rut', $normalizedRut)
            ->where('id', '<>', $usuario->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['rut' => 'Ya existe otro usuario con ese RUT.'])
                ->withInput();
        }

        // Proteccion: un usuario no puede quitarse el rol administrador a sí mismo.
        $editingSelf = auth()->check() && auth()->id() === $usuario->id;
        $currentlyAdmin = (int)$usuario->rol === 0;
        $requestedRol = (int)$request->input('rol', $usuario->rol);

        if ($editingSelf && $currentlyAdmin && $requestedRol !== 0) {
            return redirect()->back()
                ->withErrors(['rol' => 'No puedes quitarte el rol administrador a ti mismo. Pide a otro administrador que realice ese cambio.'])
                ->withInput();
        }

        // Aplicar cambios: guardar la versión normalizada del RUT
        $usuario->name = $validated['name'];
        $usuario->apellido = $validated['apellido'];
        $usuario->rut = $normalizedRut;
        $usuario->email = $validated['email'];
        $usuario->rol = $validated['rol'];

        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado');
    }

    public function perfil()
    {
        $user = auth()->user();
        return view('usuarios.perfil', compact('user'));
    }
}
