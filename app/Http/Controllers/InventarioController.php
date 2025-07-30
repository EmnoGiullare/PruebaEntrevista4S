<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\LineaProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventarioController extends Controller
{
    /**
     * Mostrar la vista principal del inventario 
     */
    public function mostrarInventario(Request $request)
    {
        // Solo obtener datos para los filtros y vista inicial
        $lineasProducto = LineaProducto::activas()->orderBy('nombre')->get();
        $resumen = $this->calcularResumen();

        return view('inicio', [
            'empleado' => Auth::user()->name ?? 'Admin',
            'lineasProducto' => $lineasProducto,
            'resumen' => $resumen
        ]);
    }

    /**
     * ENDPOINT Filtros + Paginación + Datos
     */
    public function obtenerProductos(Request $request)
    {
        try {
            // Validar entrada
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'linea' => 'nullable|string',
                'stock' => 'nullable|string',
                'orden' => 'nullable|string',
                'direccion' => 'nullable|string|in:asc,desc',
                'busqueda' => 'nullable|string|max:255'
            ]);

            // Procesar parámetros
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);
            $filtros = $this->procesarFiltros($request);

            Log::info('Obteniendo productos:', [
                'page' => $page,
                'per_page' => $perPage,
                'filtros' => $filtros
            ]);

            // Construir query con filtros
            $query = $this->construirQuery($filtros);

            // Obtener total antes de paginar
            $total = $query->count();

            // Aplicar paginación
            $productos = $query->forPage($page, $perPage)->get();

            // Calcular paginación manualmente
            $lastPage = ceil($total / $perPage);
            $from = ($page - 1) * $perPage + 1;
            $to = min($page * $perPage, $total);

            // Convertir productos para JS
            $datosProductos = $this->convertirProductosParaJS($productos);

            // Calcular resumen con filtros aplicados
            $resumenFiltrado = $this->calcularResumenFiltrado($query, $filtros);

            return response()->json([
                'success' => true,
                'data' => $datosProductos,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'from' => $total > 0 ? $from : null,
                    'to' => $total > 0 ? $to : null,
                    'has_more_pages' => $page < $lastPage,
                    'on_first_page' => $page === 1
                ],
                'resumen' => $resumenFiltrado,
                'filtros_aplicados' => $filtros
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo productos:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * AJAX: Exportar datos 
     */
    public function exportarCSV(Request $request)
    {
        try {
            $filtros = $this->procesarFiltros($request);
            $query = $this->construirQuery($filtros);
            $productos = $query->get();
            $datos = $this->convertirProductosParaJS($productos);

            if (empty($datos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos para exportar'
                ], 400);
            }

            $csvContent = $this->generarCSV($datos);
            $nombreArchivo = $this->generarNombreArchivo($filtros);

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"")
                ->header('Content-Encoding', 'UTF-8');
        } catch (\Exception $e) {
            Log::error('Error exportando CSV:', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }


    // ================== MÉTODOS DE ADMINISTARCION DB ==================

    /**
     * AJAX: Crear nuevo producto
     */
    public function crearProducto(Request $request)
    {
        try {
            // Validar entrada
            $request->validate([
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'linea_producto_id' => 'required|exists:lineas_producto,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $data = $request->only(['nombre', 'descripcion', 'precio', 'stock', 'linea_producto_id']);

            // Manejar imagen
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
                $imagen->storeAs('public/productos', $nombreImagen);
                $data['imagen'] = $nombreImagen;
            }

            // Crear producto
            $producto = Producto::create($data);
            $producto->load('linea');

            // Convertir para respuesta
            $productoData = $this->convertirProductosParaJS(collect([$producto]))[0];

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $productoData
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando producto:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== MÉTODOS PRIVADOS ==================

    private function procesarFiltros(Request $request)
    {
        return [
            'linea' => $request->input('linea', 'all'),
            'stock' => $request->input('stock', 'all'),
            'orden' => $request->input('orden', 'nombre'),
            'direccion' => $request->input('direccion', 'asc'),
            'busqueda' => trim($request->input('busqueda', ''))
        ];
    }

    private function construirQuery($filtros)
    {
        $query = Producto::with(['linea']);

        // Filtro por búsqueda
        if (!empty($filtros['busqueda'])) {
            $query->buscar($filtros['busqueda']);
        }

        // Filtro por línea de producto
        if ($filtros['linea'] !== 'all') {
            $query->deLinea($filtros['linea']);
        }

        // Filtro por estado de stock
        if ($filtros['stock'] !== 'all') {
            switch ($filtros['stock']) {
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
        $query->orderBy($filtros['orden'], $filtros['direccion']);

        return $query;
    }

    private function convertirProductosParaJS($productos)
    {
        return $productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre ?? 'Sin nombre',
                'descripcion' => $producto->descripcion ?? '',
                'precio' => (float) ($producto->precio ?? 0),
                'stock' => (int) ($producto->stock ?? 0),
                'imagen_url' => $producto->imagen_url,
                'linea_id' => $producto->linea_producto_id,
                'linea_nombre' => $producto->linea->nombre ?? 'Sin línea',
                'disponible' => $producto->disponible,
                'valor_total' => $producto->valor_total,
                'estado_stock' => $producto->estado_stock,
                'created_at' => $producto->created_at ? $producto->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    private function calcularResumenFiltrado($query, $filtros)
    {
        try {
            // Clonar query para no afectar la consulta principal
            $queryResumen = clone $query;
            $productos = $queryResumen->get();

            $totalProductos = $productos->count();
            $stockTotal = $productos->sum('stock') ?? 0;
            $stockBajo = $productos->where('stock', '<=', 10)->where('stock', '>', 0)->count();
            $valorInventario = $productos->sum(function ($producto) {
                return ($producto->precio ?? 0) * ($producto->stock ?? 0);
            });

            return [
                'totalProductos' => $totalProductos,
                'stockTotal' => $stockTotal,
                'stockBajo' => $stockBajo,
                'valorInventario' => round($valorInventario, 2),
                'agotados' => $productos->where('stock', '<=', 0)->count(),
                'filtrado' => $this->tieneFiltrosAplicados($filtros)
            ];
        } catch (\Exception $e) {
            Log::error('Error calculando resumen filtrado: ' . $e->getMessage());

            return [
                'totalProductos' => 0,
                'stockTotal' => 0,
                'stockBajo' => 0,
                'valorInventario' => 0,
                'agotados' => 0,
                'filtrado' => false
            ];
        }
    }

    private function calcularResumen()
    {
        try {
            $productos = Producto::all();

            $totalProductos = $productos->count();
            $stockTotal = $productos->sum('stock') ?? 0;
            $stockBajo = $productos->where('stock', '<=', 10)->where('stock', '>', 0)->count();
            $valorInventario = $productos->sum(function ($producto) {
                return ($producto->precio ?? 0) * ($producto->stock ?? 0);
            });

            return [
                'totalProductos' => $totalProductos,
                'stockTotal' => $stockTotal,
                'stockBajo' => $stockBajo,
                'valorInventario' => round($valorInventario, 2),
                'agotados' => $productos->where('stock', '<=', 0)->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error calculando resumen: ' . $e->getMessage());

            return [
                'totalProductos' => 0,
                'stockTotal' => 0,
                'stockBajo' => 0,
                'valorInventario' => 0,
                'agotados' => 0
            ];
        }
    }

    private function tieneFiltrosAplicados($filtros)
    {
        return $filtros['linea'] !== 'all' ||
            $filtros['stock'] !== 'all' ||
            !empty($filtros['busqueda']);
    }

    private function generarCSV($datos)
    {
        $encabezados = [
            'ID',
            'Nombre',
            'Descripción',
            'Línea de Producto',
            'Precio',
            'Stock',
            'Valor Total',
            'Estado',
            'Fecha Creación'
        ];

        $filas = [];
        $filas[] = implode(',', $encabezados);

        foreach ($datos as $item) {
            $filas[] = implode(',', [
                $item['id'] ?? '',
                '"' . str_replace('"', '""', ($item['nombre'] ?? 'Sin nombre')) . '"',
                '"' . str_replace('"', '""', ($item['descripcion'] ?? '')) . '"',
                '"' . str_replace('"', '""', ($item['linea_nombre'] ?? 'Sin línea')) . '"',
                number_format($item['precio'] ?? 0, 2),
                $item['stock'] ?? 0,
                number_format($item['valor_total'] ?? 0, 2),
                '"' . ucfirst($item['estado_stock'] ?? 'desconocido') . '"',
                '"' . ($item['created_at'] ?? date('Y-m-d H:i:s')) . '"'
            ]);
        }

        return "\xEF\xBB\xBF" . implode("\n", $filas);
    }

    private function generarNombreArchivo($filtros)
    {
        $nombre = 'inventario_productos';

        if ($filtros['linea'] !== 'all') {
            $linea = LineaProducto::find($filtros['linea']);
            if ($linea) {
                $nombre .= '_' . str_replace(' ', '_', strtolower($linea->nombre));
            }
        }

        if ($filtros['stock'] !== 'all') {
            $nombre .= '_' . $filtros['stock'];
        }

        return $nombre . '_' . date('Y-m-d') . '.csv';
    }
}
