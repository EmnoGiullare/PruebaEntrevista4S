<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Funcion para mostarar la vista del controlador
    public function showLoginForm()
    {
        if (Auth::check()) {  // Verifica si hay usuario autenticado
            return redirect('/'); // Redirige a la ruta raíz
        }

        return view("login");
    }

    // fucnion para la logica del inicio de sesion
    public function login(Request $request)
    {
        // validacion de los datos recividos
        // validando los datos
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // recuperra el credenciales
        $credenciales = [
            'username' => $request->get('username'),
            'password' => $request->get("password")
        ];

        // REvisar credenciales
        if (Auth::attempt($credenciales)) {

            // generar una sesion.
            $request->session()->regenerate();
            // redirigimos al usuario a la ruta que queria acceder
            return redirect()->intended('/');
        }

        // si las credenciales no corresponden, debemos de recargar el login con mensajes de error
        return back()->withErrors([
            "name" => "El nombre de usuario o contraseña no son validos."
        ])->onlyInput('name');
    }

    public function logout(Request $request)
    {
        // cerrar sesion
        Auth::logout();
        // invalidar la sesion generada
        $request->session()->invalidate();
        // reestablecer los tokens generados para la sesion
        $request->session()->regenerateToken();
        // redirigir hacia el loginQ
        return redirect('/login');
    }

    // funcion para actualizar los datos del usuario
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mothers_last_name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'color' => 'nullable|string|max:7',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Actualización segura
        User::where('id', Auth::id())->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'mothers_last_name' => $request->mothers_last_name,
            'username' => $request->username,
            'email' => $request->email,
            'color' => $request->color,
            'password' => $request->password ? Hash::make($request->password) : Auth::user()->password
        ]);

        return back()->with('success', 'Perfil actualizado correctamente');
    }
}
