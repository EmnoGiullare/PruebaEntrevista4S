<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserRol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles): Response
    {

        // Verificar si el usuario a iniciado sesion
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // obtener el usuario
        $user = Auth::user();
        $rol = $user->rol;

        // convertir en lista los roles recibidos
        $roles_permitidos = explode('-', $roles);

        // verificar si el rol se encuentra o no dentro de la lista de permitidos
        if (!in_array($rol, $roles_permitidos)) {
            // cerrar sesion
            Auth::logout();
            // invalidar la sesion generada
            $request->session()->invalidate();
            // restablecer los tokens generados para la sesion
            $request->session()->regenerateToken();
            // redirigir hacia el login
            return redirect('/login');
            //abort(403, 'No tienes permiso para acceder a esta pagina.');
        }

        return $next($request);
    }
}
