document.addEventListener('DOMContentLoaded', function () {
    // ✅ Variables globales
    const filtroAnio = document.querySelector('[name="anio"]')
    const filtroMes = document.querySelector('[name="mes"]')
    const filtroEstacion = document.querySelector('[name="estacion"]')
    const filtroTipoPago = document.querySelector('[name="tipo_pago"]')
    const btnAplicar = document.getElementById('btnFiltrar')
    const btnExportar = document.getElementById('exportarExcel')

    // ✅ Variables para gráficos - Mejoradas
    let stationChart = null
    let paymentChart = null
    let graficosInicializados = false

    // ✅ Event listeners
    btnAplicar.addEventListener('click', function (e) {
        console.log('Botón Aplicar Filtros clickeado')
        e.preventDefault()
        aplicarFiltros()
    })

    btnExportar.addEventListener('click', function (e) {
        e.preventDefault()
        exportarDatos()
    })

    // ✅ Función principal para aplicar filtros
    async function aplicarFiltros () {
        console.log('Aplicando filtros...')
        try {
            mostrarLoading(true)

            const filtros = {
                anio: filtroAnio.value,
                mes: filtroMes.value,
                estacion: filtroEstacion.value,
                tipo_pago: filtroTipoPago.value
            }

            console.log('Filtros seleccionados:', filtros)

            // ✅ Usar la ruta global
            const response = await fetch(window.rutas.filtros, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify(filtros)
            })

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`)
            }

            const data = await response.json()

            if (data.success) {
                // ✅ Actualizar vista con datos del servidor
                console.log('Datos recibidos:', data)
                actualizarResumen(data.resumen)
                actualizarTextos(data.textos)
                actualizarTabla(data.data)
                actualizarGraficos(data.graficos)
            } else {
                throw new Error(data.message || 'Error desconocido')
            }
        } catch (error) {
            console.error('Error aplicando filtros:', error)
            mostrarError('Error al aplicar filtros: ' + error.message)
        } finally {
            mostrarLoading(false)
        }
    }

    // ✅ Función para exportar datos
    async function exportarDatos () {
        try {
            const filtros = {
                anio: filtroAnio.value,
                mes: filtroMes.value,
                estacion: filtroEstacion.value,
                tipo_pago: filtroTipoPago.value
            }

            // ✅ Usar la ruta global
            const response = await fetch(window.rutas.exportar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify(filtros)
            })

            if (!response.ok) {
                const errorData = await response.json()
                throw new Error(errorData.message || 'Error al exportar')
            }

            // ✅ Descargar archivo
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `reporte_metro_${new Date()
                .toISOString()
                .slice(0, 10)}.csv`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(url)

            mostrarExito('Archivo exportado correctamente')
        } catch (error) {
            console.error('Error exportando:', error)
            mostrarError('Error al exportar: ' + error.message)
        }
    }

    // ✅ Actualizar resumen
    function actualizarResumen (resumen) {
        const formatoNumero = new Intl.NumberFormat('es-MX')
        const formatoMoneda = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        })

        document.querySelector(
            '#resumen-general .col-md-4:nth-child(1) h2'
        ).textContent = formatoNumero.format(resumen.totalPersonas)

        document.querySelector(
            '#resumen-general .col-md-4:nth-child(2) h2'
        ).textContent = formatoMoneda.format(resumen.totalRecaudado)

        document.querySelector(
            '#resumen-general .col-md-4:nth-child(3) h2'
        ).textContent = formatoNumero.format(resumen.promedioMensual)
    }

    // ✅ Actualizar textos descriptivos
    function actualizarTextos (textos) {
        document.getElementById('meses-anios-consulta').innerHTML =
            textos.periodo
        document.getElementById('texto-tarifas').textContent = textos.tarifas
    }

    // ✅ Actualizar tabla
    function actualizarTabla (datos) {
        const tableBody = document.getElementById('table-body')
        const tableFooter = document.querySelector('#report-table tfoot')

        // Limpiar tabla
        tableBody.innerHTML = ''

        if (!datos || datos.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No hay datos con los filtros seleccionados
                    </td>
                </tr>
            `
            if (tableFooter) tableFooter.style.display = 'none'
            return
        }

        // Llenar tabla
        let totalPersonas = 0
        let totalRecaudado = 0

        datos.forEach(item => {
            totalPersonas += item.personas
            totalRecaudado += item.recaudacion

            const row = document.createElement('tr')
            row.innerHTML = `
                <td class="fw-bold">${escapeHtml(item.estacion_nombre)}</td>
                <td>${item.periodo}</td>
                <td>${escapeHtml(item.tipo_pago_nombre)}</td>
                <td class="text-end">${formatearNumero(item.personas)}</td>
                <td class="text-end">${formatearMoneda(item.tarifa)}</td>
                <td class="text-end fw-bold">${formatearMoneda(
                    item.recaudacion
                )}</td>
            `
            tableBody.appendChild(row)
        })

        // Actualizar totales
        if (tableFooter) {
            tableFooter.innerHTML = `
                <tr>
                    <th colspan="3">Totales Generales</th>
                    <th class="text-end">${formatearNumero(totalPersonas)}</th>
                    <th class="text-end">-</th>
                    <th class="text-end">${formatearMoneda(totalRecaudado)}</th>
                </tr>
            `
            tableFooter.style.display = ''
        }
    }

    // ✅ Destruir gráficos existentes - MEJORADA
    function destruirGraficos () {
        console.log('Destruyendo gráficos existentes...')

        // Destruir gráfico de estaciones
        if (stationChart) {
            try {
                console.log('Destruyendo gráfico de estaciones')
                stationChart.destroy()
            } catch (error) {
                console.warn('Error destruyendo gráfico de estaciones:', error)
            }
            stationChart = null
        }

        // Destruir gráfico de tipos de pago
        if (paymentChart) {
            try {
                console.log('Destruyendo gráfico de tipos de pago')
                paymentChart.destroy()
            } catch (error) {
                console.warn(
                    'Error destruyendo gráfico de tipos de pago:',
                    error
                )
            }
            paymentChart = null
        }

        // ✅ Destruir TODOS los gráficos de Chart.js si existen
        if (window.Chart && window.Chart.instances) {
            Object.keys(window.Chart.instances).forEach(key => {
                const instance = window.Chart.instances[key]
                if (instance && typeof instance.destroy === 'function') {
                    try {
                        instance.destroy()
                    } catch (e) {
                        console.warn(
                            'Error destruyendo instancia de gráfico:',
                            e
                        )
                    }
                }
            })
        }

        // Limpiar canvas manualmente
        limpiarCanvas('stationChart')
        limpiarCanvas('paymentChart')

        graficosInicializados = false
    }

    // ✅ Nueva función para limpiar canvas
    function limpiarCanvas (canvasId) {
        const canvas = document.getElementById(canvasId)
        if (canvas) {
            const ctx = canvas.getContext('2d')
            ctx.clearRect(0, 0, canvas.width, canvas.height)
            // Resetear el canvas completamente
            canvas.width = canvas.width
        }
    }

    // ✅ Actualizar gráficos - MEJORADA
    function actualizarGraficos (datosGraficos) {
        console.log('Actualizando gráficos...', datosGraficos)

        // Verificar que tenemos datos
        if (
            !datosGraficos ||
            !datosGraficos.estaciones ||
            !datosGraficos.tiposPago
        ) {
            console.warn('Datos de gráficos incompletos:', datosGraficos)
            return
        }

        // Destruir gráficos existentes de forma segura
        destruirGraficos()

        // Esperar un poco para asegurar que el canvas esté libre
        setTimeout(() => {
            try {
                crearGraficoEstaciones(datosGraficos.estaciones)
                crearGraficoTiposPago(datosGraficos.tiposPago)
                graficosInicializados = true
            } catch (error) {
                console.error('Error creando gráficos:', error)
            }
        }, 200)
    }

    // ✅ Crear gráfico de estaciones - MEJORADA
    function crearGraficoEstaciones (datos) {
        console.log('Creando gráfico de estaciones...', datos)

        const ctx = document.getElementById('stationChart')
        if (!ctx) {
            console.error('Canvas stationChart no encontrado')
            return
        }

        // Verificar que tenemos datos válidos
        if (
            !datos ||
            !datos.labels ||
            !datos.data ||
            datos.labels.length === 0
        ) {
            console.warn('Datos de estaciones inválidos:', datos)
            return
        }

        try {
            // ✅ Verificar si ya existe un gráfico en este canvas
            const existingChart = Chart.getChart(ctx)
            if (existingChart) {
                console.log('Gráfico existente encontrado, destruyendo...')
                existingChart.destroy()
            }

            stationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.labels,
                    datasets: [
                        {
                            label: 'Personas',
                            data: datos.data,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Afluencia por Estación',
                            font: { size: 16 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString()
                                }
                            }
                        }
                    }
                }
            })
            console.log('Gráfico de estaciones creado exitosamente')
        } catch (error) {
            console.error('Error creando gráfico de estaciones:', error)
            stationChart = null
        }
    }

    // ✅ Crear gráfico de tipos de pago - MEJORADA
    function crearGraficoTiposPago (datos) {
        console.log('Creando gráfico de tipos de pago...', datos)

        const ctx = document.getElementById('paymentChart')
        if (!ctx) {
            console.error('Canvas paymentChart no encontrado')
            return
        }

        // Verificar que tenemos datos válidos
        if (
            !datos ||
            !datos.labels ||
            !datos.data ||
            datos.labels.length === 0
        ) {
            console.warn('Datos de tipos de pago inválidos:', datos)
            return
        }

        try {
            // ✅ Verificar si ya existe un gráfico en este canvas
            const existingChart = Chart.getChart(ctx)
            if (existingChart) {
                console.log(
                    'Gráfico de tipos de pago existente encontrado, destruyendo...'
                )
                existingChart.destroy()
            }

            paymentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: datos.labels,
                    datasets: [
                        {
                            data: datos.data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)'
                            ]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribución por Tipo de Pago',
                            font: { size: 16 }
                        }
                    }
                }
            })
            console.log('Gráfico de tipos de pago creado exitosamente')
        } catch (error) {
            console.error('Error creando gráfico de tipos de pago:', error)
            paymentChart = null
        }
    }

    // ✅ Función auxiliar para inicializar gráficos por primera vez
    function inicializarGraficos () {
        console.log('Intentando inicializar gráficos...')

        // Verificar que no estén ya inicializados
        if (graficosInicializados) {
            console.log('Gráficos ya inicializados, saltando...')
            return
        }

        // Solo crear gráficos si tenemos datos iniciales
        if (
            window.graficosIniciales &&
            window.graficosIniciales.estaciones &&
            window.graficosIniciales.tiposPago
        ) {
            console.log(
                'Inicializando gráficos con datos iniciales...',
                window.graficosIniciales
            )

            // Esperar un poco para asegurar que la pestaña de gráficos esté disponible
            setTimeout(() => {
                // Verificar si estamos en la pestaña de gráficos o si debemos mostrarlos
                const chartTab = document.getElementById('chart-view')
                if (chartTab) {
                    actualizarGraficos(window.graficosIniciales)
                }
            }, 300)
        } else {
            console.warn('No hay datos iniciales para los gráficos')
        }
    }

    // ✅ Funciones auxiliares
    function escapeHtml (unsafe) {
        return (
            unsafe
                ?.toString()
                ?.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;') || ''
        )
    }

    function formatearNumero (num) {
        return new Intl.NumberFormat('es-MX').format(num)
    }

    function formatearMoneda (num) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(num)
    }

    function mostrarLoading (mostrar) {
        btnAplicar.disabled = mostrar
        btnExportar.disabled = mostrar
        btnAplicar.innerHTML = mostrar
            ? '<i class="bi bi-hourglass-split"></i> Aplicando...'
            : '<i class="bi bi-filter-circle"></i> Aplicar Filtros'
    }

    function mostrarError (mensaje) {
        // Implementar sistema de notificaciones
        alert(mensaje) // Temporal
    }

    function mostrarExito (mensaje) {
        // Implementar sistema de notificaciones
        console.log(mensaje) // Temporal
    }

    // ✅ Inicialización mejorada
    console.log('DOM cargado, inicializando...')

    // ✅ Agregar listener para cuando se cambie a la pestaña de gráficos
    const chartTabButton = document.getElementById('chart-tab')
    if (chartTabButton) {
        chartTabButton.addEventListener('shown.bs.tab', function () {
            console.log('Pestaña de gráficos activada')
            if (!graficosInicializados && window.graficosIniciales) {
                setTimeout(() => {
                    actualizarGraficos(window.graficosIniciales)
                }, 100)
            }
        })
    }

    // Inicializar gráficos con datos iniciales si están disponibles
    setTimeout(() => {
        inicializarGraficos()
    }, 500)

    // Aplicar filtros iniciales si es necesario
    if (window.location.search) {
        setTimeout(() => {
            aplicarFiltros()
        }, 1000)
    }

    // ✅ Limpiar gráficos antes de salir de la página
    window.addEventListener('beforeunload', function () {
        destruirGraficos()
    })
})
