<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\LineaProducto;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApiInventarioController extends Controller
{
    /**
     * Obtener lista de productos con filtros
     */
    public function index(Request $request)
    {
        try {
            $query = Producto::with(['linea']);

            // Aplicar filtros
            if ($request->filled('search')) {
                $query->buscar($request->search);
            }

            if ($request->filled('linea') && $request->linea !== 'all') {
                $query->deLinea($request->linea);
            }

            if ($request->filled('stock') && $request->stock !== 'all') {
                switch ($request->stock) {
                    case 'disponible':
                        $query->where('stock', '>', 10);
                        break;
                    case 'bajo':
                        $query->where('stock', '>', 0)->where('stock', '<=', 10);
                        break;
                    case 'agotado':
                        $query->where('stock', '<=', 0);
                        break;
                }
            }

            // Ordenamiento
            $orderBy = $request->get('order_by', 'nombre');
            $direction = $request->get('direction', 'asc');
            $query->orderBy($orderBy, $direction);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $productos = $query->paginate($perPage);

            return ProductoResource::collection($productos);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'linea_producto_id' => 'required|exists:lineas_producto,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Manejar imagen
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
                $imagen->storeAs('public/productos', $nombreImagen);
                $data['imagen'] = $nombreImagen;
            }

            $producto = Producto::create($data);
            $producto->load('linea');

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => new ProductoResource($producto)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar producto específico
     */
    public function show($id)
    {
        try {
            $producto = Producto::with('linea')->findOrFail($id);
            return new ProductoResource($producto);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        try {
            $producto = Producto::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'linea_producto_id' => 'required|exists:lineas_producto,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Manejar imagen
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior
                if ($producto->imagen) {
                    Storage::delete('public/productos/' . $producto->imagen);
                }

                $imagen = $request->file('imagen');
                $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
                $imagen->storeAs('public/productos', $nombreImagen);
                $data['imagen'] = $nombreImagen;
            }

            $producto->update($data);
            $producto->load('linea');

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => new ProductoResource($producto)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        try {
            $producto = Producto::findOrFail($id);

            // Eliminar imagen
            if ($producto->imagen) {
                Storage::delete('public/productos/' . $producto->imagen);
            }

            $producto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del inventario
     */
    public function estadisticas()
    {
        try {
            $totalProductos = Producto::count();
            $stockTotal = Producto::sum('stock');
            $stockBajo = Producto::where('stock', '>', 0)->where('stock', '<=', 10)->count();
            $agotados = Producto::where('stock', '<=', 0)->count();
            $valorInventario = Producto::selectRaw('SUM(precio * stock) as valor')->value('valor') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'totalProductos' => $totalProductos,
                    'stockTotal' => $stockTotal,
                    'stockBajo' => $stockBajo,
                    'agotados' => $agotados,
                    'valorInventario' => (float) $valorInventario
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener líneas de producto
     */
    public function lineasProducto()
    {
        try {
            $lineas = LineaProducto::activas()->orderBy('nombre')->get();
            return response()->json([
                'success' => true,
                'data' => $lineas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener líneas de producto: ' . $e->getMessage()
            ], 500);
        }
    }
}
