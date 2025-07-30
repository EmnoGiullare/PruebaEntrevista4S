<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Importamos la configuración inicial -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Meta tags de autenticación --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session('api_token'))
        <meta name="api-token" content="{{ session('api_token') }}">
    @endif
    <title>Sistema de Reportes de Afluencia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="{{ asset('css/RepMetro/styles.css') }}?v=1.0.0" rel="stylesheet">

</head>

<body class="standard-body">
    @include('RepMetro.barra_nav')

    <div class="container-fluid">
        <!-- Fragmentos del reporte -->
        <!--------------------------- Filtros ---------------------->
        <!-- Filtros y controles -->
        <div class="row mb-4">
            <!-- Filtro por Año -->
            <div class="col-md-3 col-6 mb-3">
                <label class="form-label">Año:</label>
                <select name="anio" class="form-select">
                    <option value="all">Todos los años</option>
                    @foreach ($aniosDisponibles as $anio)
                        <option value="{{ $anio }}" {{ request('anio') == $anio ? 'selected' : '' }}>
                            {{ $anio }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Mes -->
            <div class="col-md-3 col-6 mb-3">
                <label class="form-label">Mes:</label>
                <select name="mes" class="form-select">
                    <option value="all">Todos los meses</option>
                    @php
                        $nombresMeses = [
                            1 => 'Enero',
                            2 => 'Febrero',
                            3 => 'Marzo',
                            4 => 'Abril',
                            5 => 'Mayo',
                            6 => 'Junio',
                            7 => 'Julio',
                            8 => 'Agosto',
                            9 => 'Septiembre',
                            10 => 'Octubre',
                            11 => 'Noviembre',
                            12 => 'Diciembre',
                        ];
                    @endphp

                    @foreach ($mesesDisponibles as $mes)
                        <option value="{{ $mes }}" {{ request('mes') == $mes ? 'selected' : '' }}>
                            {{ $nombresMeses[$mes] ?? 'Mes ' . $mes }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Estación -->
            <div class="col-md-3 col-6 mb-3">
                <label class="form-label">Estación:</label>
                <select name="estacion" class="form-select">
                    <option value="all">Todas las estaciones</option>
                    @forelse($estaciones as $estacion)
                        <option value="{{ $estacion->id }}"
                            {{ request('estacion') == $estacion->id ? 'selected' : '' }}>
                            {{ $estacion->nombre }}
                        </option>
                    @empty
                        <option value="" disabled>No hay estaciones disponibles</option>
                    @endforelse
                </select>
            </div>

            <!-- Filtro por Forma de Pago -->
            <div class="col-md-3 col-6 mb-3">
                <label class="form-label">Forma de Pago:</label>
                <select name="tipo_pago" class="form-select">
                    <option value="all">Todas las formas de pago</option>
                    @forelse($tiposPago as $tipoPago)
                        <option value="{{ $tipoPago->id }}"
                            {{ request('tipo_pago') == $tipoPago->id ? 'selected' : '' }}>
                            {{ $tipoPago->nombre }}
                        </option>
                    @empty
                        <option value="" disabled>No hay formas de pago disponibles</option>
                    @endforelse
                </select>
            </div>

            <!-- Botones de Acción -->
            <div class="col-12 d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-primary me-2" id="btnFiltrar">
                    <i class="bi bi-filter-circle"></i> Aplicar Filtros
                </button>
                <button type="button" class="btn btn-success" id="exportarExcel">
                    <i class="bi bi-file-earmark-excel"></i> Exportar
                </button>
            </div>
        </div>

        <!--------------------------- Resumen ---------------------->
        <!-- Resumen general -->
        <div class="row mb-4" id="resumen-general">
            <div class="col-md-4">
                <div class="card revenue-card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Total Personas</h5>
                        {{-- ✅ Cambiar esto --}}
                        <h2 class="mb-0">{{ number_format($resumen['totalPersonas']) }}</h2>
                        <p class="text-muted mb-0" id="meses-anios-consulta">
                            {{-- ✅ Usar textos del controlador --}}
                            {!! $textos['periodo'] ?? 'Cargando información...' !!}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card revenue-card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Total Recaudado</h5>
                        {{-- ✅ Cambiar esto --}}
                        <h2 class="mb-0">${{ number_format($resumen['totalRecaudado'], 2) }}</h2>
                        <p class="text-muted mb-0" id="texto-tarifas">
                            {{-- ✅ Usar textos del controlador --}}
                            {{ $textos['tarifas'] ?? 'Cargando tarifas...' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card revenue-card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Promedio Mensual</h5>
                        {{-- ✅ Cambiar esto --}}
                        <h2 class="mb-0">{{ number_format($resumen['promedioMensual']) }}</h2>
                        <p class="text-muted mb-0">Personas/mes</p>
                    </div>
                </div>
            </div>
        </div>

        <!--------------------------- visualizacion ---------------------->
        <!-- Pestañas de visualización -->
        <ul class="nav nav-pills mb-4" id="view-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="table-tab" data-bs-toggle="pill" data-bs-target="#table-view"
                    type="button" role="tab">
                    <i class="bi bi-table"></i> Vista Tabular
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="chart-tab" data-bs-toggle="pill" data-bs-target="#chart-view"
                    type="button" role="tab">
                    <i class="bi bi-bar-chart"></i> Gráficos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analysis-tab" data-bs-toggle="pill" data-bs-target="#analysis-view"
                    type="button" role="tab">
                    <i class="bi bi-graph-up"></i> Análisis
                </button>
            </li>
        </ul>

        <!--------------------------- tabla ---------------------->
        <div class="tab-content" id="view-tabs-content">
            <!-- Vista Tabular -->
            <div class="tab-pane fade show active" id="table-view" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Reporte Detallado por Estación</h5>
                        <div class="d-flex align-items-center">
                            <!-- Mostrar información de filtros aplicados -->
                            <div id="filtros-activos" class="me-3 small text-muted">
                                <i class="bi bi-funnel-fill"></i>
                                <span id="texto-filtros">
                                    @if (request()->anyFilled(['anio', 'mes', 'estacion', 'tipo_pago']))
                                        Filtros aplicados:
                                        {{ request('anio', 'Todos los años') }} |
                                        {{ request('mes') ? \Carbon\Carbon::create()->month(request('mes'))->monthName : 'Todos los meses' }}
                                        |
                                        {{ request('estacion') ? $estaciones->firstWhere('id', request('estacion'))->nombre ?? 'Todas' : 'Todas las estaciones' }}
                                        |
                                        {{ request('tipo_pago') ? $tiposPago->firstWhere('id', request('tipo_pago'))->nombre ?? 'Todos' : 'Todos los tipos' }}
                                    @else
                                        Todos los filtros
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Mensaje cuando no hay datos -->
                        <div id="sin-datos-message"
                            class="alert alert-warning {{ $datosPaginados->isEmpty() ? '' : 'd-none' }}">
                            No se encontraron datos con los filtros seleccionados
                        </div>

                        <!-- Tabla de resultados -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle" id="report-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-nowrap">Estación</th>
                                        <th class="text-nowrap">Mes/Año</th>
                                        <th class="text-nowrap">Tipo de Pago</th>
                                        <th class="text-nowrap text-end">Personas</th>
                                        <th class="text-nowrap text-end">Tarifa</th>
                                        <th class="text-nowrap text-end">Importe</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body" class="table-group-divider">
                                    {{-- ✅ Cambiar $datos por $datosPaginados --}}
                                    @forelse($datosPaginados as $item)
                                        <tr>
                                            <td>{{ $item->estacion->nombre }}</td>
                                            <td>{{ $item->periodo->format('m/Y') }}</td>
                                            <td>{{ $item->tipoPago->nombre }}</td>
                                            <td class="text-end">{{ number_format($item->cantidad) }}</td>
                                            <td class="text-end">${{ number_format($item->tarifa->importe, 2) }}</td>
                                            <td class="text-end">
                                                ${{ number_format($item->cantidad * $item->tarifa->importe, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No hay datos disponibles</td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                {{-- ✅ Actualizar el tfoot también --}}
                                @unless ($datosPaginados->isEmpty())
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="3">Totales</th>
                                            <th class="text-end">{{ number_format($datosPaginados->sum('cantidad')) }}
                                            </th>
                                            <th class="text-end">-</th>
                                            <th class="text-end">
                                                ${{ number_format(
                                                    $datosPaginados->sum(function ($item) {
                                                        return $item->cantidad * $item->tarifa->importe;
                                                    }),
                                                    2,
                                                ) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                @endunless
                            </table>
                        </div>

                        <!-- Paginación -->
                        @if ($datosPaginados->hasPages())
                            <nav id="pagination-container">
                                <ul class="pagination justify-content-center mt-3">
                                    @if ($datosPaginados->onFirstPage())
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">Anterior</a>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $datosPaginados->previousPageUrl() }}">Anterior</a>
                                        </li>
                                    @endif

                                    @foreach (range(1, $datosPaginados->lastPage()) as $i)
                                        <li
                                            class="page-item {{ $datosPaginados->currentPage() == $i ? 'active' : '' }}">
                                            <a class="page-link"
                                                href="{{ $datosPaginados->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endforeach

                                    @if ($datosPaginados->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $datosPaginados->nextPageUrl() }}">Siguiente</a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#">Siguiente</a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!--------------------------- graficos ---------------------->
        <!-- Vista de Gráficos -->
        <div class="tab-pane fade" id="chart-view" role="tabpanel">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Afluencia por Estación</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="stationChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Distribución por Tipo de Pago</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--------------------------- Análisis ---------------------->
        <!-- Vista de Análisis -->
        <div class="tab-pane fade" id="analysis-view" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Análisis Comparativo</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Tendencia Mensual</h6>
                            <canvas id="trendChart" height="250"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6>Porcentaje de Uso por Estación</h6>
                            <canvas id="percentageChart" height="250"></canvas>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6>Observaciones</h6>
                        <ul>
                            <li>La estación B muestra mayor afluencia en febrero</li>
                            <li>El 65% de los accesos usan tarjeta interna</li>
                            <li>Marzo registró el mayor número de accesos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ✅ Pasar rutas desde Laravel
        window.rutas = {
            filtros: '{{ route('rep_metro.filtros') }}',
            exportar: '{{ route('rep_metro.exportar') }}',
            reporte: '{{ route('rep_metro.reporte') }}'
        };

        // ✅ Pasar datos iniciales para los gráficos
        window.graficosIniciales = @json($graficos ?? null);

        // ✅ Pasar datos iniciales
        window.datosIniciales = {
            filtros: @json($filtros),
            hayDatos: {{ count($datosCompletos) > 0 ? 'true' : 'false' }}
        };
    </script>

    {{-- Scripts externos --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/rep_metro/reporte.js') }}?v=1.0.1"></script>
</body>

</html>
