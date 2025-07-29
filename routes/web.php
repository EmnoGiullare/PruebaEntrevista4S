<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;

// definimos las rutas que no requieren autenticacion
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// definimos las rutas que requieren solo estar autenticado
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [PortalController::class, 'mostrarPUsuario'])->name('inicio');
});

// definimos las rutas que requieren un rol especifico
Route::middleware(['auth', 'mrol:admin'])->group(function () {
    // Aquí van las rutas que solo pueden acceder los usuarios con rol de admin
    Route::get('/admin', [PortalController::class, 'mostrarPAdmin'])->name('inicio');
});
