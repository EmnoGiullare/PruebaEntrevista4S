<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\Api\ApiInventarioController;



// Rutas de API que requieren autenticación
// Route::middleware(['auth'])->group(function () {

// === RUTAS DE INVENTARIO (usando ApiInventarioController) ===
Route::prefix('inventario')->name('api.inventario.')->group(function () {
    // CRUD de productos
    Route::get('/productos', [ApiInventarioController::class, 'index'])->name('productos.index');
    Route::post('/productos', [ApiInventarioController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}', [ApiInventarioController::class, 'show'])->name('productos.show');
    Route::put('/productos/{id}', [ApiInventarioController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{id}', [ApiInventarioController::class, 'destroy'])->name('productos.destroy');

    // Estadísticas generales
    Route::get('/estadisticas', [ApiInventarioController::class, 'estadisticas'])->name('estadisticas');

    // Líneas de producto
    Route::get('/lineas-producto', [ApiInventarioController::class, 'lineasProducto'])->name('lineas');
});

// === RUTAS AJAX (usando InventarioController original) ===
Route::prefix('ajax')->name('api.ajax.')->group(function () {
    // Filtros dinámicos (tu lógica actual)
    Route::post('/productos', [InventarioController::class, 'obtenerProductos'])->name('productos');

    // Exportar datos
    Route::post('/exportar', [InventarioController::class, 'exportarCSV'])->name('exportar');
});
// });

// === RUTAS PÚBLICAS (si las necesitas) ===
Route::prefix('public')->name('api.public.')->group(function () {
    // Catálogo público de productos (sin autenticación)
    Route::get('/productos', [ApiInventarioController::class, 'index'])->name('productos');

    // Líneas de producto públicas
    Route::get('/lineas', [ApiInventarioController::class, 'lineasProducto'])->name('lineas');
});
