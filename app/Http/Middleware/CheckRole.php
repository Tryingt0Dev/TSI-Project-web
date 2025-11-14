<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = Auth::user()->rol;

        $normalized = array_map(fn($r) => is_numeric($r) ? (int)$r : $r, $roles);

        if (!in_array($userRole, $normalized)) {
            abort(403, 'Acceso denegado');
        }

        return $next($request);
    }
}
