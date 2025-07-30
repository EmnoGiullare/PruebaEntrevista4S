document.addEventListener('DOMContentLoaded', function () {
    // Variables globales
    const filtroLinea = document.querySelector('[name="linea"]')
    const filtroStock = document.querySelector('[name="stock"]')
    const filtroOrden = document.querySelector('[name="orden"]')
    const filtroDireccion = document.querySelector('[name="direccion"]')
    const searchInput = document.getElementById('searchProducts')
    const btnExportar = document.getElementById('exportarExcel')
    const btnNuevo = document.getElementById('nuevoProducto')

    // Elementos de interfaz
    const loadingContainer = document.getElementById('loading-container')
    const sinDatosMessage = document.getElementById('sin-datos-message')
    const tableBody = document.getElementById('table-body')
    const tableFooter = document.getElementById('table-footer')
    const paginationWrapper = document.getElementById('pagination-wrapper')

    // Variables del modal
    const modalNuevoProducto = new bootstrap.Modal(
        document.getElementById('modalNuevoProducto')
    )
    const formNuevoProducto = document.getElementById('formNuevoProducto')
    const btnGuardarProducto = document.getElementById('btnGuardarProducto')
    const imagenInput = document.getElementById('imagen')
    const vistaPrevia = document.getElementById('vistaPrevia')
    const imagenPrevia = document.getElementById('imagenPrevia')

    // Estado actual
    let paginaActual = 1
    let productosPorPagina = 15
    let ultimaPaginacion = null

    // Event listeners
    btnExportar?.addEventListener('click', exportarDatos)
    btnNuevo?.addEventListener('click', nuevoProducto)

    // Event listeners del modal
    btnGuardarProducto?.addEventListener('click', guardarNuevoProducto)
    imagenInput?.addEventListener('change', mostrarVistaPrevia)

    // Búsqueda en tiempo real
    let searchTimeout
    searchInput?.addEventListener('input', function () {
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            paginaActual = 1 // Resetear a página 1
            cargarProductos()
        }, 500)
    })

    // Auto-aplicar filtros cuando cambian los selects
    ;[filtroLinea, filtroStock, filtroOrden, filtroDireccion].forEach(
        elemento => {
            elemento?.addEventListener('change', function () {
                paginaActual = 1 // Resetear a página 1
                cargarProductos()
            })
        }
    )

    // Cargar datos iniciales
    cargarProductos()

    // ============= FUNCIÓN PRINCIPAL =============
    async function cargarProductos () {
        try {
            mostrarLoading(true)
            ocultarMensajes()

            const datos = {
                page: paginaActual,
                per_page: productosPorPagina,
                linea: filtroLinea?.value || 'all',
                stock: filtroStock?.value || 'all',
                orden: filtroOrden?.value || 'nombre',
                direccion: filtroDireccion?.value || 'asc',
                busqueda: searchInput?.value || ''
            }

            console.log('Cargando productos:', datos)

            const response = await fetch(window.rutas.productos, {
                method: 'POST',
                headers: window.axiosDefaults.headers,
                body: JSON.stringify(datos)
            })

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            const result = await response.json()
            console.log('Respuesta del servidor:', result)

            if (result.success) {
                actualizarTabla(result.data)
                actualizarPaginacion(result.pagination)
                actualizarResumen(result.resumen)
                actualizarTextoFiltros(result.filtros_aplicados)
                ultimaPaginacion = result.pagination
            } else {
                throw new Error(result.message || 'Error desconocido')
            }
        } catch (error) {
            console.error('Error cargando productos:', error)
            mostrarError('Error al cargar productos: ' + error.message)
        } finally {
            mostrarLoading(false)
        }
    }

    // ============= ACTUALIZAR TABLA =============
    function actualizarTabla (productos) {
        if (!tableBody) return

        if (!productos || productos.length === 0) {
            tableBody.innerHTML =
                '<tr><td colspan="8" class="text-center py-4">No se encontraron productos</td></tr>'
            tableFooter.classList.add('d-none')
            mostrarSinDatos(true)
            return
        }

        mostrarSinDatos(false)

        let html = ''
        let totalValor = 0
        let totalStock = 0

        productos.forEach(producto => {
            totalValor += producto.valor_total || 0
            totalStock += producto.stock || 0

            html += `
                <tr>
                    <td><input type="checkbox" class="product-checkbox" value="${
                        producto.id
                    }"></td>
                    <td>
                        <img src="${producto.imagen_url}" 
                             alt="${producto.nombre}" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                             onerror="this.src='{{ asset('images/no-image.png') }}'">
                    </td>
                    <td>
                        <div>
                            <strong>${producto.nombre}</strong>
                            ${
                                producto.descripcion
                                    ? `<br><small class="text-muted">${producto.descripcion.substring(
                                          0,
                                          50
                                      )}${
                                          producto.descripcion.length > 50
                                              ? '...'
                                              : ''
                                      }</small>`
                                    : ''
                            }
                        </div>
                    </td>
                    <td>${producto.linea_nombre || 'Sin línea'}</td>
                    <td class="text-end">$${parseFloat(
                        producto.precio || 0
                    ).toFixed(2)}</td>
                    <td class="text-center">
                        <span class="stock-badge ${getStockClass(
                            producto.stock
                        )}">${producto.stock || 0}</span>
                    </td>
                    <td class="text-center">
                        ${getStockBadge(producto.stock)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary me-1" onclick="editProduct(${
                            producto.id
                        })" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteProduct(${
                            producto.id
                        })" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `
        })

        tableBody.innerHTML = html

        // Actualizar footer con totales
        if (productos.length > 0) {
            tableFooter.innerHTML = `
                <tr>
                    <th colspan="4">Totales (página actual)</th>
                    <th class="text-end">$${totalValor.toFixed(2)}</th>
                    <th class="text-center">${totalStock}</th>
                    <th colspan="2"></th>
                </tr>
            `
            tableFooter.classList.remove('d-none')
        } else {
            tableFooter.classList.add('d-none')
        }
    }

    // ============= ACTUALIZAR PAGINACIÓN =============
    function actualizarPaginacion (pagination) {
        if (!paginationWrapper || !pagination || pagination.total === 0) {
            paginationWrapper.classList.add('d-none')
            return
        }

        paginationWrapper.classList.remove('d-none')

        const { current_page, last_page, per_page, total, from, to } =
            pagination

        let html = `
            <div class="pagination-info">
                <div class="results-info">
                    Mostrando <span class="fw-bold">${from || 0}</span> a 
                    <span class="fw-bold">${to || 0}</span>
                    de <span class="fw-bold">${total}</span> productos
                </div>
                <div class="page-size-selector">
                    <label for="pageSize">Mostrar:</label>
                    <select id="pageSize" onchange="cambiarTamanoPagina(this.value)">
                        <option value="10" ${
                            per_page == 10 ? 'selected' : ''
                        }>10</option>
                        <option value="15" ${
                            per_page == 15 ? 'selected' : ''
                        }>15</option>
                        <option value="25" ${
                            per_page == 25 ? 'selected' : ''
                        }>25</option>
                        <option value="50" ${
                            per_page == 50 ? 'selected' : ''
                        }>50</option>
                        <option value="100" ${
                            per_page == 100 ? 'selected' : ''
                        }>100</option>
                    </select>
                    <span>por página</span>
                </div>
            </div>
            
            <nav class="custom-pagination">
                <ul class="pagination-list">
        `

        // Botón Primera página
        if (current_page > 1) {
            html += `
                <li class="pagination-item">
                    <a href="#" class="pagination-link first-page" onclick="irAPagina(1)" title="Primera página">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
            `
        }

        // Botón Anterior
        if (current_page > 1) {
            html += `
                <li class="pagination-item">
                    <a href="#" class="pagination-link prev-page" onclick="irAPagina(${
                        current_page - 1
                    })">
                        <i class="fas fa-angle-left"></i>
                        <span class="d-none d-sm-inline">Anterior</span>
                    </a>
                </li>
            `
        } else {
            html += `
                <li class="pagination-item disabled">
                    <span class="pagination-link">
                        <i class="fas fa-angle-left"></i>
                        <span class="d-none d-sm-inline">Anterior</span>
                    </span>
                </li>
            `
        }

        // Números de página
        const start = Math.max(1, current_page - 2)
        const end = Math.min(last_page, current_page + 2)

        // Página 1 si no está en el rango
        if (start > 1) {
            html += `<li class="pagination-item"><a href="#" class="pagination-link" onclick="irAPagina(1)">1</a></li>`
            if (start > 2) {
                html += `<li class="pagination-item disabled"><span class="pagination-link dots">...</span></li>`
            }
        }

        // Páginas numeradas
        for (let page = start; page <= end; page++) {
            if (page === current_page) {
                html += `<li class="pagination-item active"><span class="pagination-link current">${page}</span></li>`
            } else {
                html += `<li class="pagination-item"><a href="#" class="pagination-link" onclick="irAPagina(${page})">${page}</a></li>`
            }
        }

        // Última página si no está en el rango
        if (end < last_page) {
            if (end < last_page - 1) {
                html += `<li class="pagination-item disabled"><span class="pagination-link dots">...</span></li>`
            }
            html += `<li class="pagination-item"><a href="#" class="pagination-link" onclick="irAPagina(${last_page})">${last_page}</a></li>`
        }

        // Botón Siguiente
        if (current_page < last_page) {
            html += `
                <li class="pagination-item">
                    <a href="#" class="pagination-link next-page" onclick="irAPagina(${
                        current_page + 1
                    })">
                        <span class="d-none d-sm-inline">Siguiente</span>
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
            `
        } else {
            html += `
                <li class="pagination-item disabled">
                    <span class="pagination-link">
                        <span class="d-none d-sm-inline">Siguiente</span>
                        <i class="fas fa-angle-right"></i>
                    </span>
                </li>
            `
        }

        // Botón Última página
        if (current_page < last_page) {
            html += `
                <li class="pagination-item">
                    <a href="#" class="pagination-link last-page" onclick="irAPagina(${last_page})" title="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            `
        }

        html += `
                </ul>
                
                <div class="page-jump">
                    <span>Ir a página:</span>
                    <input type="number" id="pageJump" min="1" max="${last_page}" value="${current_page}" onkeypress="handlePageJump(event)">
                    <button type="button" onclick="saltoRapido()" class="btn-jump">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </nav>
        `

        paginationWrapper.innerHTML = html
    }

    // ============= FUNCIONES DE PAGINACIÓN =============
    window.irAPagina = function (pagina) {
        if (
            pagina !== paginaActual &&
            pagina >= 1 &&
            (!ultimaPaginacion || pagina <= ultimaPaginacion.last_page)
        ) {
            paginaActual = pagina
            cargarProductos()
        }
    }

    window.cambiarTamanoPagina = function (nuevoTamano) {
        productosPorPagina = parseInt(nuevoTamano)
        paginaActual = 1
        cargarProductos()
    }

    window.saltoRapido = function () {
        const pageInput = document.getElementById('pageJump')
        const pageNumber = parseInt(pageInput.value)

        if (
            ultimaPaginacion &&
            pageNumber >= 1 &&
            pageNumber <= ultimaPaginacion.last_page
        ) {
            irAPagina(pageNumber)
        } else {
            alert(
                `Por favor ingresa un número entre 1 y ${
                    ultimaPaginacion?.last_page || 1
                }`
            )
            pageInput.focus()
        }
    }

    window.handlePageJump = function (event) {
        if (event.key === 'Enter') {
            event.preventDefault()
            saltoRapido()
        }
    }

    // ============= FUNCIONES DE MODAL =============
    // Función para abrir el modal
    function nuevoProducto () {
        limpiarFormulario()
        modalNuevoProducto.show()
    }

    // Función para guardar nuevo producto
    async function guardarNuevoProducto () {
        try {
            // Validar formulario
            if (!validarFormulario()) {
                return
            }

            // Mostrar loading
            mostrarLoadingModal(true)

            // Preparar datos del formulario
            const formData = new FormData(formNuevoProducto)

            // Hacer petición al servidor
            const response = await fetch(
                window.rutas.crearProducto || '/api/inventario/productos',
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        Accept: 'application/json'
                    },
                    body: formData
                }
            )

            const result = await response.json()

            if (response.ok && result.success) {
                // Éxito
                mostrarExito('Producto creado exitosamente')
                modalNuevoProducto.hide()

                // Recargar la tabla
                paginaActual = 1 // Ir a la primera página para ver el nuevo producto
                await cargarProductos()
            } else {
                // Error de validación
                if (result.errors) {
                    mostrarErroresValidacion(result.errors)
                } else {
                    mostrarError(result.message || 'Error al crear el producto')
                }
            }
        } catch (error) {
            console.error('Error creando producto:', error)
            mostrarError('Error de conexión: ' + error.message)
        } finally {
            mostrarLoadingModal(false)
        }
    }

    // Función para validar formulario
    function validarFormulario () {
        const form = formNuevoProducto
        let isValid = true

        // Limpiar errores previos
        limpiarErroresValidacion()

        // Validar campos requeridos
        const camposRequeridos = [
            { field: 'nombre', message: 'El nombre es obligatorio' },
            {
                field: 'linea_producto_id',
                message: 'Selecciona una línea de producto'
            },
            { field: 'precio', message: 'El precio es obligatorio' },
            { field: 'stock', message: 'El stock es obligatorio' }
        ]

        camposRequeridos.forEach(({ field, message }) => {
            const input = form.querySelector(`[name="${field}"]`)
            if (!input.value.trim()) {
                mostrarErrorCampo(input, message)
                isValid = false
            }
        })

        // Validar precio
        const precioInput = form.querySelector('[name="precio"]')
        const precio = parseFloat(precioInput.value)
        if (precio < 0) {
            mostrarErrorCampo(
                precioInput,
                'El precio debe ser mayor o igual a 0'
            )
            isValid = false
        }

        // Validar stock
        const stockInput = form.querySelector('[name="stock"]')
        const stock = parseInt(stockInput.value)
        if (stock < 0) {
            mostrarErrorCampo(stockInput, 'El stock debe ser mayor o igual a 0')
            isValid = false
        }

        // Validar imagen si se seleccionó
        const imagenInput = form.querySelector('[name="imagen"]')
        if (imagenInput.files.length > 0) {
            const file = imagenInput.files[0]
            const maxSize = 2 * 1024 * 1024 // 2MB
            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/jpg',
                'image/gif'
            ]

            if (!allowedTypes.includes(file.type)) {
                mostrarErrorCampo(
                    imagenInput,
                    'Formato de imagen no válido. Usa JPG, PNG o GIF'
                )
                isValid = false
            }

            if (file.size > maxSize) {
                mostrarErrorCampo(imagenInput, 'La imagen debe ser menor a 2MB')
                isValid = false
            }
        }

        return isValid
    }

    // Función para mostrar vista previa de imagen
    function mostrarVistaPrevia (event) {
        const file = event.target.files[0]

        if (file) {
            const reader = new FileReader()

            reader.onload = function (e) {
                imagenPrevia.src = e.target.result
                vistaPrevia.classList.remove('d-none')
            }

            reader.readAsDataURL(file)
        } else {
            vistaPrevia.classList.add('d-none')
        }
    }

    // Función para limpiar formulario
    function limpiarFormulario () {
        formNuevoProducto.reset()
        limpiarErroresValidacion()
        vistaPrevia.classList.add('d-none')
        imagenPrevia.src = ''
    }

    // Función para mostrar errores de validación
    function mostrarErroresValidacion (errores) {
        Object.keys(errores).forEach(campo => {
            const input = formNuevoProducto.querySelector(`[name="${campo}"]`)
            if (input) {
                mostrarErrorCampo(input, errores[campo][0])
            }
        })
    }

    // Función para mostrar error en campo específico
    function mostrarErrorCampo (input, mensaje) {
        input.classList.add('is-invalid')
        const feedback = input.parentNode.querySelector('.invalid-feedback')
        if (feedback) {
            feedback.textContent = mensaje
        }
    }

    // Función para limpiar errores de validación
    function limpiarErroresValidacion () {
        const inputs = formNuevoProducto.querySelectorAll(
            '.form-control, .form-select'
        )
        inputs.forEach(input => {
            input.classList.remove('is-invalid')
            const feedback = input.parentNode.querySelector('.invalid-feedback')
            if (feedback) {
                feedback.textContent = ''
            }
        })
    }

    // Función para mostrar loading en modal
    function mostrarLoadingModal (mostrar) {
        const modal = document.querySelector(
            '#modalNuevoProducto .modal-content'
        )

        if (mostrar) {
            btnGuardarProducto.disabled = true
            btnGuardarProducto.innerHTML =
                '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...'
            modal.classList.add('modal-loading')
        } else {
            btnGuardarProducto.disabled = false
            btnGuardarProducto.innerHTML =
                '<i class="bi bi-check-circle me-1"></i>Guardar Producto'
            modal.classList.remove('modal-loading')
        }
    }

    // Event listener para limpiar errores al escribir
    formNuevoProducto?.addEventListener('input', function (e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid')
            const feedback =
                e.target.parentNode.querySelector('.invalid-feedback')
            if (feedback) {
                feedback.textContent = ''
            }
        }
    })

    // Event listener para cerrar modal con Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalNuevoProducto._isShown) {
            modalNuevoProducto.hide()
        }
    })

    // ============= FUNCIONES DE UTILIDAD =============
    function actualizarResumen (resumen) {
        if (!resumen) return

        const elementos = {
            'stat-totalProductos': resumen.totalProductos || 0,
            'stat-stockTotal': resumen.stockTotal || 0,
            'stat-stockBajo': resumen.stockBajo || 0,
            'stat-valorInventario': resumen.valorInventario || 0
        }

        Object.keys(elementos).forEach(id => {
            const elemento = document.getElementById(id)
            if (elemento) {
                if (id === 'stat-valorInventario') {
                    elemento.textContent = `$${elementos[id].toLocaleString()}`
                } else {
                    elemento.textContent = elementos[id].toLocaleString()
                }
            }
        })
    }

    function actualizarTextoFiltros (filtros) {
        const textoFiltros = document.getElementById('texto-filtros')
        if (!textoFiltros) return

        let texto = 'Todos los productos'
        const filtrosActivos = []

        if (filtros.linea !== 'all') {
            const lineaNombre = filtroLinea?.selectedOptions[0]?.textContent
            if (lineaNombre) filtrosActivos.push(`Línea: ${lineaNombre}`)
        }

        if (filtros.stock !== 'all') {
            const stockTexto = {
                disponible: 'En Stock',
                bajo: 'Stock Bajo',
                agotado: 'Agotado'
            }
            filtrosActivos.push(
                `Estado: ${stockTexto[filtros.stock] || filtros.stock}`
            )
        }

        if (filtros.busqueda) {
            filtrosActivos.push(`Búsqueda: "${filtros.busqueda}"`)
        }

        if (filtrosActivos.length > 0) {
            texto = filtrosActivos.join(', ')
        }

        textoFiltros.textContent = texto
    }

    // Funciones de exportar y utilidades
    async function exportarDatos () {
        try {
            mostrarBotonCargando(btnExportar, true)

            const datos = {
                linea: filtroLinea?.value || 'all',
                stock: filtroStock?.value || 'all',
                orden: filtroOrden?.value || 'nombre',
                direccion: filtroDireccion?.value || 'asc',
                busqueda: searchInput?.value || ''
            }

            const response = await fetch(window.rutas.exportar, {
                method: 'POST',
                headers: window.axiosDefaults.headers,
                body: JSON.stringify(datos)
            })

            if (response.ok) {
                const blob = await response.blob()
                const url = window.URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = url
                a.download = `inventario_${new Date()
                    .toISOString()
                    .slice(0, 10)}.csv`
                document.body.appendChild(a)
                a.click()
                document.body.removeChild(a)
                window.URL.revokeObjectURL(url)

                mostrarExito('Datos exportados correctamente')
            } else {
                const errorData = await response.json()
                throw new Error(errorData.message || 'Error al exportar')
            }
        } catch (error) {
            console.error('Error exportando:', error)
            mostrarError('Error al exportar: ' + error.message)
        } finally {
            mostrarBotonCargando(btnExportar, false)
        }
    }

    function getStockClass (stock) {
        if (stock <= 0) return 'out-of-stock'
        if (stock <= 10) return 'low-stock'
        return 'in-stock'
    }

    function getStockBadge (stock) {
        if (stock <= 0) return '<span class="badge bg-danger">Agotado</span>'
        if (stock <= 10)
            return '<span class="badge bg-warning">Stock Bajo</span>'
        return '<span class="badge bg-success">Disponible</span>'
    }

    function mostrarLoading (mostrar) {
        if (loadingContainer) {
            loadingContainer.classList.toggle('d-none', !mostrar)
        }
    }

    function mostrarSinDatos (mostrar) {
        if (sinDatosMessage) {
            sinDatosMessage.classList.toggle('d-none', !mostrar)
        }
    }

    function ocultarMensajes () {
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.classList.contains('alert-permanent')) {
                alert.remove()
            }
        })
    }

    function mostrarError (mensaje) {
        mostrarAlerta(mensaje, 'danger')
    }

    function mostrarExito (mensaje) {
        mostrarAlerta(mensaje, 'success')
    }

    function mostrarAlerta (mensaje, tipo) {
        const alertHtml = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                <i class="fas fa-${
                    tipo === 'success' ? 'check-circle' : 'exclamation-circle'
                }"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `

        const container = document.querySelector('.content-area')
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml)
            setTimeout(() => {
                const alert = container.querySelector('.alert')
                if (alert) alert.remove()
            }, 5000)
        }
    }

    function mostrarBotonCargando (boton, cargando) {
        if (!boton) return

        if (cargando) {
            boton.disabled = true
            const iconoOriginal = boton.innerHTML
            boton.dataset.originalContent = iconoOriginal
            boton.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Procesando...'
        } else {
            boton.disabled = false
            if (boton.dataset.originalContent) {
                boton.innerHTML = boton.dataset.originalContent
            }
        }
    }

    // Funciones globales para botones de acción
    window.editProduct = function (id) {
        console.log('Editar producto:', id)
    }

    window.deleteProduct = function (id) {
        if (confirm('¿Está seguro de que desea eliminar este producto?')) {
            console.log('Eliminar producto:', id)
        }
    }
})
