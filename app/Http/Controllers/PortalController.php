<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalController extends Controller
{
    //
    public function mostrarPUsuario()
    {
        // Retorna la vista de inicio
        return view('inicio');
    }
    public function mostrarPAdmin()
    {
        // Retorna la vista de inicio
        return view('admin');
    }
}
