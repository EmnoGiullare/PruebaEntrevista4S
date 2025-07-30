<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\InventarioController;

// Rutas que no requieren autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Rutas que requieren solo estar autenticado
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Vista principal del inventario (solo renderiza la vista)
    Route::get('/', [InventarioController::class, 'mostrarInventario'])->name('inicio');

    // Rutas para las vistas (solo frontend)
    Route::get('/productos', [InventarioController::class, 'mostrarInventario'])->name('productos.vista');
});

// Rutas que requieren rol específico de admin
Route::middleware(['auth', 'mrol:admin'])->group(function () {
    Route::get('/admin', [PortalController::class, 'mostrarPAdmin'])->name('admin.inicio');
});
