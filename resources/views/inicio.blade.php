<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistema de Inventario - Tienda Departamental</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1.0.1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>


    <!-- Contenedor Principal -->

    <div class="main-container">
        <!-- Header, sidebar, etc. -->

        <main class="main-content" id="mainContent">
            <!-- Header Superior -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Sistema de Inventario</h1>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar productos..." id="searchProducts">
                    </div>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span>{{ $empleado ?? 'Admin' }}</span>
                        @auth
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">Cerrar sesion</button>
                            </form>
                        @endauth
                    </div>
                </div>
            </header>

            <!-- Área de Contenido -->
            <div class="content-area">
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label">Línea de Producto:</label>
                        <select name="linea" class="form-select">
                            <option value="all">Todas las líneas</option>
                            @foreach ($lineasProducto as $linea)
                                <option value="{{ $linea->id }}">{{ $linea->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label">Estado de Stock:</label>
                        <select name="stock" class="form-select">
                            <option value="all">Todos</option>
                            <option value="disponible">En Stock</option>
                            <option value="bajo">Stock Bajo (≤10)</option>
                            <option value="agotado">Agotado</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label">Ordenar por:</label>
                        <select name="orden" class="form-select">
                            <option value="nombre">Nombre</option>
                            <option value="precio">Precio</option>
                            <option value="stock">Stock</option>
                            <option value="created_at">Fecha Creación</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label">Dirección:</label>
                        <select name="direccion" class="form-select">
                            <option value="asc">Ascendente</option>
                            <option value="desc">Descendente</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-success me-2" id="exportarExcel">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                        <button type="button" class="btn btn-info" id="nuevoProducto">
                            <i class="bi bi-plus-circle"></i> Nuevo Producto
                        </button>
                    </div>
                </div>

                <!-- Vista Tabular -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Inventario de Productos</h5>
                        <div id="filtros-activos" class="small text-muted">
                            <i class="bi bi-funnel-fill"></i>
                            <span id="texto-filtros">Todos los productos</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Loading State -->
                        <div id="loading-container" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando productos...</p>
                        </div>

                        <!-- Mensaje cuando no hay datos -->
                        <div id="sin-datos-message" class="alert alert-warning d-none">
                            No se encontraron productos con los filtros seleccionados
                        </div>

                        <!-- Tabla de resultados -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle" id="products-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Línea de Producto</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body">
                                    <!-- Los datos se cargan dinámicamente -->
                                </tbody>
                                <tfoot id="table-footer" class="table-secondary d-none">
                                    <!-- Los totales se calculan dinámicamente -->
                                </tfoot>
                            </table>
                        </div>

                        <!-- Paginación personalizada -->
                        <div id="pagination-wrapper" class="pagination-wrapper d-none">
                            <!-- Se genera dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4" id="resumen-general">
                    <div class="col-md-3">
                        <div class="card revenue-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Total Productos</h5>
                                <h2 class="mb-0" id="stat-totalProductos">
                                    {{ number_format($resumen['totalProductos']) }}</h2>
                                <p class="text-muted mb-0">Productos registrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card revenue-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Stock Total</h5>
                                <h2 class="mb-0" id="stat-stockTotal">{{ number_format($resumen['stockTotal']) }}
                                </h2>
                                <p class="text-muted mb-0">Unidades en inventario</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card revenue-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Stock Bajo</h5>
                                <h2 class="mb-0" id="stat-stockBajo">{{ number_format($resumen['stockBajo']) }}</h2>
                                <p class="text-muted mb-0">Productos con stock ≤10</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card revenue-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Valor Inventario</h5>
                                <h2 class="mb-0" id="stat-valorInventario">
                                    ${{ number_format($resumen['valorInventario'], 2) }}</h2>
                                <p class="text-muted mb-0">Valor total del inventario</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL PARA NUEVO PRODUCTO -->
    <div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoProductoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoProductoLabel">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoProducto" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Nombre del Producto -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="bi bi-tag me-1"></i>Nombre del Producto <span
                                        class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required
                                    maxlength="100" placeholder="Ej: Televisor LED 55 pulgadas">
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Línea de Producto -->
                            <div class="col-md-6 mb-3">
                                <label for="linea_producto_id" class="form-label">
                                    <i class="bi bi-collection me-1"></i>Línea de Producto <span
                                        class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="linea_producto_id" name="linea_producto_id" required>
                                    <option value="">Selecciona una línea</option>
                                    @foreach ($lineasProducto as $linea)
                                        <option value="{{ $linea->id }}">{{ $linea->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Precio -->
                            <div class="col-md-6 mb-3">
                                <label for="precio" class="form-label">
                                    <i class="bi bi-currency-dollar me-1"></i>Precio <span
                                        class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio" name="precio"
                                        required min="0" step="0.01" placeholder="0.00">
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Stock -->
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">
                                    <i class="bi bi-boxes me-1"></i>Stock Inicial <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="stock" name="stock" required
                                    min="0" placeholder="0">
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Descripción -->
                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">
                                    <i class="bi bi-card-text me-1"></i>Descripción
                                </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="500"
                                    placeholder="Describe las características del producto..."></textarea>
                                <div class="form-text">Máximo 500 caracteres</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Imagen del Producto -->
                            <div class="col-12 mb-3">
                                <label for="imagen" class="form-label">
                                    <i class="bi bi-image me-1"></i>Imagen del Producto
                                </label>
                                <input type="file" class="form-control" id="imagen" name="imagen"
                                    accept="image/jpeg,image/png,image/jpg,image/gif">
                                <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</div>
                                <div class="invalid-feedback"></div>

                                <!-- Vista previa de la imagen -->
                                <div id="vistaPrevia" class="mt-2 d-none">
                                    <img id="imagenPrevia" src="" alt="Vista previa"
                                        style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #e9ecef;">
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Información:</strong> Los campos marcados con <span class="text-danger">*</span>
                            son obligatorios.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarProducto">
                        <i class="bi bi-check-circle me-1"></i>Guardar Producto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de configuración -->
    <script>
        window.rutas = {
            productos: '{{ route('api.ajax.productos') }}',
            exportar: '{{ route('api.ajax.exportar') }}',
            crearProducto: '{{ route('api.ajax.crear-producto') }}'
        };

        window.csrfToken = '{{ csrf_token() }}';
        window.axiosDefaults = {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
    </script>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/inventario.js') }}?v=1.0.2"></script>
</body>

</html>
