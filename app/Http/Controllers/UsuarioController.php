<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'rut'       => 'required|string|max:10|unique:users,rut',
            'email'     => 'required|email|unique:users,email',
            'rol'       => 'required|integer|in:0,1',
            'password'  => 'required|string|min:6|confirmed'
        ]);

        User::create([
            'name'     => $validated['name'],
            'apellido' => $validated['apellido'],
            'rut'      => $validated['rut'],
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

        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'rut'       => 'required|string|max:10|unique:users,rut,' . $usuario->id,
            'email'     => 'required|email|unique:users,email,' . $usuario->id,
            'rol'       => 'required|integer|in:0,1',
            'password'  => 'nullable|string|min:6|confirmed'
        ]);

        $usuario->name     = $validated['name'];
        $usuario->apellido = $validated['apellido'];
        $usuario->rut      = $validated['rut'];
        $usuario->email    = $validated['email'];
        $usuario->rol      = $validated['rol'];

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
}
