/**
 * fa_crear_arcor.js
 * JavaScript para la creación/edición de facturas de Arcor
 * MODIFICADO: Soporte para auto-detectar sede desde archivo de despacho
 */

let moduloActual = '';
let datosProcesados = {
    recepcion: null,
    despacho: null,
    ocupabilidad: null,
    servicios: null
};
let registroId = 0;
let facturaTempId = 0;

// ============================================
// INICIALIZACIÓN
// ============================================

$(document).ready(function() {
    console.log('✅ Página cargada');
    
    if (window.registroId && window.registroId > 0) {
        registroId = window.registroId;
        facturaTempId = window.registroId;
        console.log('🎯 EDITANDO FACTURA EXISTENTE con ID:', facturaTempId);
    } else {
        facturaTempId = Date.now();
        console.log('🆕 CREANDO NUEVA FACTURA con ID temporal:', facturaTempId);
    }
    
    // Cargar datos existentes si es edición
    if (registroId > 0) {
        cargarDatosExistentes();
    }
    
    const moduloActivo = window.moduloActivo || '';
    console.log('📌 Módulo activo desde PHP:', moduloActivo);
    
    if (moduloActivo === 'recepcion') {
        setTimeout(() => document.getElementById('file-recepcion')?.click(), 500);
    } else if (moduloActivo === 'despacho') {
        setTimeout(() => document.getElementById('file-despacho')?.click(), 500);
    } else if (moduloActivo === 'ocupabilidad') {
        setTimeout(() => window.abrirModalOcupabilidad(), 500);
    } else if (moduloActivo === 'servicios') {
        setTimeout(() => window.abrirModalServicios(), 500);
    }
});

function cargarDatosExistentes() {
    // Cargar datos de la factura existente
    $.ajax({
        url: '../../Controller/fa_get_factura.php',
        method: 'GET',
        data: { id: registroId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const factura = response.data;
                
                // Cargar sede
                if (factura.sede) {
                    const selectSede = document.getElementById('sede_registro');
                    if (selectSede) {
                        // Verificar si existe la opción
                        let existe = false;
                        for (let i = 0; i < selectSede.options.length; i++) {
                            if (selectSede.options[i].value === factura.sede) {
                                existe = true;
                                break;
                            }
                        }
                        if (!existe) {
                            const newOption = document.createElement('option');
                            newOption.value = factura.sede;
                            newOption.textContent = factura.sede;
                            selectSede.appendChild(newOption);
                        }
                        selectSede.value = factura.sede;
                    }
                }
                
                // Cargar fechas
                if (factura.fecha1) {
                    document.getElementById('fecha_inicio').value = factura.fecha1.split(' ')[0];
                }
                if (factura.fecha2) {
                    document.getElementById('fecha_fin').value = factura.fecha2.split(' ')[0];
                }
                
                // Marcar módulos completados
                if (factura.recepcion == 1) marcarModuloCompletadoVisual('recepcion');
                if (factura.despacho == 1) marcarModuloCompletadoVisual('despacho');
                if (factura.ocupabilidad == 1) marcarModuloCompletadoVisual('ocupabilidad');
                if (factura.servicios == 1) marcarModuloCompletadoVisual('servicios');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando datos existentes:', error);
        }
    });
}

function marcarModuloCompletadoVisual(modulo) {
    const card = document.getElementById(`modulo-${modulo}`);
    if (card) card.classList.add('completado');
    
    // Deshabilitar botones de procesar
    const btn = document.getElementById(`btn-procesar-${modulo}`);
    if (btn) btn.disabled = true;
    
    // Mostrar datos extraídos si existen
    if (modulo === 'ocupabilidad' && datosProcesados.ocupabilidad) {
        mostrarDatosOcupabilidad(datosProcesados.ocupabilidad.datos);
    }
    if (modulo === 'servicios' && datosProcesados.servicios) {
        mostrarDatosServicios(datosProcesados.servicios);
    }
    
    actualizarProgreso();
}

// ============================================
// FUNCIONES PARA FILTROS DE FECHA
// ============================================

window.setRango = function(rango) {
    console.log('📅 SetRango llamado:', rango);
    const hoy = new Date();
    let fechaDesde, fechaHasta;
    
    switch(rango) {
        case 'hoy':
            fechaDesde = hoy;
            fechaHasta = hoy;
            break;
        case 'ayer':
            fechaDesde = new Date();
            fechaDesde.setDate(hoy.getDate() - 1);
            fechaHasta = fechaDesde;
            break;
        case 'ultimos7':
            fechaHasta = hoy;
            fechaDesde = new Date();
            fechaDesde.setDate(hoy.getDate() - 7);
            break;
        case 'ultimos30':
            fechaHasta = hoy;
            fechaDesde = new Date();
            fechaDesde.setDate(hoy.getDate() - 30);
            break;
        default:
            return;
    }
    
    const fechaDesdeInput = document.getElementById('filtro-fecha-desde');
    const fechaHastaInput = document.getElementById('filtro-fecha-hasta');
    
    if (fechaDesdeInput) fechaDesdeInput.value = formatFechaInput(fechaDesde);
    if (fechaHastaInput) fechaHastaInput.value = formatFechaInput(fechaHasta);
    
    aplicarFiltroFecha();
};

function formatFechaInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

window.aplicarFiltroFecha = function() {
    console.log('📅 Aplicando filtro de fecha - módulo actual:', moduloActual);
    
    const fechaDesde = document.getElementById('filtro-fecha-desde')?.value;
    const fechaHasta = document.getElementById('filtro-fecha-hasta')?.value;
    
    console.log('   Desde:', fechaDesde);
    console.log('   Hasta:', fechaHasta);
    
    if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
        Swal.fire('Advertencia', 'La fecha "desde" no puede ser mayor que la fecha "hasta"', 'warning');
        return;
    }
    
    if (moduloActual && datosProcesados[moduloActual]) {
        console.log('🔄 Recargando datos con filtro para:', moduloActual);
        mostrarVistaPrevia(moduloActual);
    } else {
        console.log('No hay módulo activo para filtrar');
    }
};

window.limpiarFiltroFecha = function() {
    console.log('📅 Limpiando filtro de fecha');
    
    const fechaDesdeInput = document.getElementById('filtro-fecha-desde');
    const fechaHastaInput = document.getElementById('filtro-fecha-hasta');
    
    if (fechaDesdeInput) fechaDesdeInput.value = '';
    if (fechaHastaInput) fechaHastaInput.value = '';
    
    const filterStats = document.getElementById('filterStats');
    if (filterStats) filterStats.innerHTML = '';
    
    if (moduloActual && datosProcesados[moduloActual]) {
        mostrarVistaPrevia(moduloActual);
    }
};

// ============================================
// FUNCIONES PARA RECEPCIÓN Y DESPACHO
// ============================================

window.habilitarBotonProcesar = function(tipo) {
    const fileInput = document.getElementById(`file-${tipo}`);
    const btnProcesar = document.getElementById(`btn-procesar-${tipo}`);
    
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        const file = fileInput.files[0];
        document.getElementById(`nombre-${tipo}`).textContent = file.name.length > 30 ? file.name.substring(0, 30) + '...' : file.name;
        document.getElementById(`size-${tipo}`).textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById(`info-${tipo}`).style.display = 'flex';
        btnProcesar.disabled = false;
        btnProcesar.classList.add('active');
    } else {
        btnProcesar.disabled = true;
        btnProcesar.classList.remove('active');
        document.getElementById(`info-${tipo}`).style.display = 'none';
        document.getElementById(`data-${tipo}`).style.display = 'none';
    }
};

window.eliminarArchivo = function(tipo) {
    document.getElementById(`file-${tipo}`).value = '';
    document.getElementById(`info-${tipo}`).style.display = 'none';
    document.getElementById(`data-${tipo}`).style.display = 'none';
    document.getElementById(`btn-procesar-${tipo}`).disabled = true;
    datosProcesados[tipo] = null;
};

window.mostrarVistaPrevia = function(tipo) {
    moduloActual = tipo;
    const fileInput = document.getElementById(`file-${tipo}`);
    if (!fileInput.files.length) {
        Swal.fire('Advertencia', 'Debe seleccionar un archivo primero', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('archivo', file);
    
    const fechaDesde = document.getElementById('filtro-fecha-desde')?.value;
    const fechaHasta = document.getElementById('filtro-fecha-hasta')?.value;
    if (fechaDesde) formData.append('fecha_desde', fechaDesde);
    if (fechaHasta) formData.append('fecha_hasta', fechaHasta);
    formData.append('factura_temp_id', facturaTempId);
    
    let url = tipo === 'recepcion' ? '../../Controller/fa_recepcion_controller.php' : '../../Controller/fa_despacho_controller.php';
    
    $('#modalVistaPrevia').modal('show');
    document.getElementById('preview-body').innerHTML = '<tr><td colspan="20" class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Procesando...</td></tr>';
    
    fetch(url, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error(data.error);
            datosProcesados[tipo] = data;
            generarTablaPreview(tipo, data);
            actualizarEstadisticasModulo(tipo, data.stats);
            
            // ============================================
            // NUEVO: Si es despacho y se detectó sede, actualizar campo
            // ============================================
            if (tipo === 'despacho' && data.sede) {
                const selectSede = document.getElementById('sede_registro');
                if (selectSede) {
                    // Verificar si la sede existe en el select
                    let sedeExiste = false;
                    for (let i = 0; i < selectSede.options.length; i++) {
                        if (selectSede.options[i].value === data.sede) {
                            sedeExiste = true;
                            break;
                        }
                    }
                    
                    if (sedeExiste) {
                        selectSede.value = data.sede;
                        console.log(`🏢 Sede automática asignada: ${data.sede}`);
                    } else {
                        // Si no existe, agregar una opción
                        const newOption = document.createElement('option');
                        newOption.value = data.sede;
                        newOption.textContent = data.sede;
                        selectSede.appendChild(newOption);
                        selectSede.value = data.sede;
                        console.log(`🏢 Nueva sede agregada: ${data.sede}`);
                    }
                    
                    // Mostrar notificación
                    Swal.fire({
                        title: 'Sede Detectada',
                        html: `Se ha detectado la sede <strong>${data.sede}</strong> desde el archivo de despacho.<br><small>WHSEID: ${data.whseid || 'N/A'}</small>`,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }
            
            document.getElementById('btn-confirmar').disabled = false;
            Swal.fire('Éxito', `${data.total_registros} registros encontrados`, 'success');
        })
        .catch(error => Swal.fire('Error', error.message, 'error'));
};

function generarTablaPreview(tipo, data) {
    const header = document.getElementById('preview-header');
    const body = document.getElementById('preview-body');
    header.innerHTML = '';
    body.innerHTML = '';
    
    (data.headers || []).forEach(col => {
        const th = document.createElement('th');
        th.textContent = col;
        header.appendChild(th);
    });
    
    (data.data || []).forEach(row => {
        const tr = document.createElement('tr');
        (Array.isArray(row) ? row : Object.values(row)).forEach(cell => {
            const td = document.createElement('td');
            td.textContent = cell || '-';
            tr.appendChild(td);
        });
        body.appendChild(tr);
    });
    
    document.getElementById('total-registros').textContent = data.total_registros || 0;
    
    // Actualizar información de filtros
    const filterStats = document.getElementById('filterStats');
    if (filterStats && data.stats) {
        let statsHtml = '';
        if (data.stats.fecha_min && data.stats.fecha_max) {
            statsHtml += `<span class="filter-stat-badge"><i class="fa fa-calendar"></i> Rango: ${data.stats.fecha_min} - ${data.stats.fecha_max}</span>`;
        }
        if (data.stats.filas_filtradas_fecha > 0) {
            statsHtml += `<span class="filter-stat-badge"><i class="fa fa-filter"></i> Filtradas: ${data.stats.filas_filtradas_fecha}</span>`;
        }
        filterStats.innerHTML = statsHtml;
    }
}

function actualizarEstadisticasModulo(tipo, stats) {
    if (!stats) return;
    document.getElementById(`data-${tipo}`).style.display = 'block';
    
    if (tipo === 'recepcion') {
        document.getElementById('recepcion-total').textContent = stats.total_filas || 0;
        let totales = [];
        if (stats.total_unidades) totales.push(`${stats.total_unidades} Unid`);
        if (stats.total_cajas) totales.push(`${stats.total_cajas} Cjas`);
        if (stats.total_pallets) totales.push(`${stats.total_pallets} Pals`);
        document.getElementById('recepcion-unidades').textContent = totales.join(' + ') || '0';
        if (stats.fecha_min && stats.fecha_max) document.getElementById('recepcion-periodo').textContent = `${stats.fecha_min} - ${stats.fecha_max}`;
    } else if (tipo === 'despacho') {
        document.getElementById('despacho-total').textContent = stats.total_filas || 0;
        document.getElementById('despacho-unidades').textContent = stats.total_unidades || 0;
        if (stats.fecha_min && stats.fecha_max) document.getElementById('despacho-periodo').textContent = `${stats.fecha_min} - ${stats.fecha_max}`;
        
        // Mostrar sede si está disponible
        if (stats.sede) {
            const sedeInfo = document.createElement('div');
            sedeInfo.className = 'alert alert-info mt-2';
            sedeInfo.innerHTML = `<i class="fa fa-building"></i> Sede detectada: <strong>${stats.sede}</strong>`;
            const dataDiv = document.getElementById(`data-${tipo}`);
            if (dataDiv && !dataDiv.querySelector('.sede-info')) {
                sedeInfo.className = 'sede-info alert alert-info mt-2';
                dataDiv.appendChild(sedeInfo);
            }
        }
    }
}

// ============================================
// CONFIRMAR PROCESAMIENTO
// ============================================

window.confirmarProcesamiento = function() {
    if (!moduloActual) return;
    
    const datos = datosProcesados[moduloActual];
    if (!datos || !datos.data_completa) {
        Swal.fire('Error', 'No hay datos para guardar', 'error');
        return;
    }
    
    const datosParaGuardar = datos.data_completa.map(row => {
        if (Array.isArray(row)) {
            const obj = {};
            datos.headers.forEach((h, i) => obj[h] = row[i]);
            return obj;
        }
        return row;
    });
    
    const payload = {
        id_factura: facturaTempId,
        [`datos_${moduloActual}`]: datosParaGuardar,
        fecha_desde: document.getElementById('filtro-fecha-desde')?.value,
        fecha_hasta: document.getElementById('filtro-fecha-hasta')?.value
    };
    
    // ============================================
    // NUEVO: Si es despacho, incluir la sede detectada
    // ============================================
    if (moduloActual === 'despacho' && datos.sede) {
        payload.sede = datos.sede;
        console.log('🏢 Enviando sede al servidor:', datos.sede);
    }
    
    const url = moduloActual === 'recepcion' ? '../../Controller/fa_guardar_recepcion.php' : '../../Controller/fa_guardar_despacho.php';
    
    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) throw new Error(data.error);
        
        if (data.factura_id || data.id_factura) {
            const nuevoId = data.factura_id || data.id_factura;
            if (nuevoId && nuevoId !== facturaTempId) {
                facturaTempId = nuevoId;
                registroId = nuevoId;
                window.registroId = nuevoId;
                console.log('🔄 ID de factura ACTUALIZADO a:', facturaTempId);
            }
        }
        
        // Mostrar mensaje con sede si se guardó
        let mensaje = `Se guardaron ${data.registros_guardados || data.insertados} registros`;
        if (data.sede_guardada) {
            mensaje += ` con sede: ${data.sede_guardada}`;
        }
        
        Swal.fire('Éxito', mensaje, 'success');
        marcarModuloCompletado(moduloActual);
        $('#modalVistaPrevia').modal('hide');
    })
    .catch(error => Swal.fire('Error', error.message, 'error'));
};

function marcarModuloCompletado(modulo) {
    const card = document.getElementById(`modulo-${modulo}`);
    if (card) card.classList.add('completado');
    
    // Para ocupabilidad, actualizar la vista
    if (modulo === 'ocupabilidad' && datosProcesados.ocupabilidad && datosProcesados.ocupabilidad.datos) {
        mostrarDatosOcupabilidad(datosProcesados.ocupabilidad.datos);
    }
    
    // Para servicios, actualizar la vista
    if (modulo === 'servicios' && datosProcesados.servicios && datosProcesados.servicios.datos) {
        mostrarDatosServicios(datosProcesados.servicios);
    }
    
    const btn = document.getElementById(`btn-procesar-${modulo}`);
    if (btn) btn.disabled = true;
    actualizarProgreso();
}

function mostrarDatosOcupabilidad(datos) {
    const dataExtracted = document.getElementById('data-ocupabilidad');
    if (dataExtracted) {
        dataExtracted.style.display = 'block';
        const total = datos.reduce((sum, d) => sum + d.cantidad, 0);
        document.getElementById('ocupabilidad-total').textContent = total;
        document.getElementById('ocupabilidad-racks').textContent = datos.length;
        
        let resumen = '';
        datos.forEach(d => {
            resumen += `${d.tipo}: ${d.cantidad}\n`;
        });
        document.getElementById('ocupabilidad-nivel').textContent = resumen.substring(0, 50) + (resumen.length > 50 ? '...' : '');
    }
}

function mostrarDatosServicios(datos) {
    const dataExtracted = document.getElementById('data-servicios');
    if (dataExtracted) {
        dataExtracted.style.display = 'block';
        document.getElementById('servicios-total').textContent = datos.datos ? datos.datos.length : 0;
        document.getElementById('servicios-monto').textContent = 'Bs ' + (datos.total || 0).toFixed(2);
    }
}

function actualizarProgreso() {
    let completados = 0;
    if (datosProcesados.recepcion) completados++;
    if (datosProcesados.despacho) completados++;
    if (datosProcesados.ocupabilidad) completados++;
    if (datosProcesados.servicios) completados++;
    
    const porcentaje = (completados / 4) * 100;
    const modulosCompletadosSpan = document.getElementById('modulosCompletados');
    const progresoPorcentajeSpan = document.getElementById('progresoPorcentaje');
    const progressFill = document.getElementById('progressFill');
    
    if (modulosCompletadosSpan) modulosCompletadosSpan.textContent = completados;
    if (progresoPorcentajeSpan) progresoPorcentajeSpan.textContent = Math.round(porcentaje);
    if (progressFill) progressFill.style.width = porcentaje + '%';
}

// ============================================
// FUNCIONES PARA OCUPABILIDAD
// ============================================

window.abrirModalOcupabilidad = function() {
    console.log('abrirModalOcupabilidad llamado');
    
    limpiarModalOcupabilidad();
    
    if (datosProcesados.ocupabilidad && datosProcesados.ocupabilidad.datos) {
        cargarDatosOcupabilidad(datosProcesados.ocupabilidad.datos);
    }
    
    actualizarTotalOcupabilidad();
    
    $('#modalOcupabilidad').modal('show');
};

function limpiarModalOcupabilidad() {
    const tbody = document.getElementById('ocupabilidad-body');
    if (!tbody) return;
    
    // Mantener solo las dos filas por defecto
    const filasDefecto = ['Posiciones rack', 'Posiciones rack (pallet adicional)'];
    const filas = tbody.querySelectorAll('.fila-ocupabilidad');
    
    filas.forEach(fila => {
        const tipoInput = fila.querySelector('.tipo-posicion');
        const tipo = tipoInput ? tipoInput.value : '';
        if (!filasDefecto.includes(tipo)) {
            fila.remove();
        } else {
            const cantidadInput = fila.querySelector('.cantidad-posicion');
            if (cantidadInput) cantidadInput.value = '';
        }
    });
}

function cargarDatosOcupabilidad(datos) {
    if (!datos || !datos.length) return;
    
    const tbody = document.getElementById('ocupabilidad-body');
    if (!tbody) return;
    
    // Limpiar filas existentes excepto las que coinciden con datos
    const filasDefecto = ['Posiciones rack', 'Posiciones rack (pallet adicional)'];
    const filasExistentes = tbody.querySelectorAll('.fila-ocupabilidad');
    
    // Actualizar filas existentes que coincidan
    datos.forEach(item => {
        let filaExistente = null;
        for (let f of filasExistentes) {
            const tipoInput = f.querySelector('.tipo-posicion');
            if (tipoInput && tipoInput.value === item.tipo) {
                filaExistente = f;
                break;
            }
        }
        
        if (filaExistente) {
            const cantidadInput = filaExistente.querySelector('.cantidad-posicion');
            if (cantidadInput) cantidadInput.value = item.cantidad;
        } else if (!filasDefecto.includes(item.tipo)) {
            // Agregar nueva fila para tipos no estándar
            agregarFilaOcupabilidadConDatos(item.tipo, item.cantidad);
        }
    });
}

function agregarFilaOcupabilidadConDatos(tipo, cantidad) {
    const tbody = document.getElementById('ocupabilidad-body');
    if (!tbody) return;
    
    const nuevaFila = document.createElement('tr');
    nuevaFila.className = 'fila-ocupabilidad';
    nuevaFila.setAttribute('data-tipo', tipo);
    
    nuevaFila.innerHTML = `
        <td>
            <input type="text" class="form-control tipo-posicion" value="${escapeHtml(tipo)}">
        </td>
        <td>
            <input type="number" class="form-control cantidad-posicion" value="${cantidad}" placeholder="Ingrese cantidad" min="0" onchange="actualizarTotalOcupabilidad()">
        </td>
        <td class="text-center">
            <button class="btn btn-danger btn-sm eliminar-fila" onclick="eliminarFilaOcupabilidad(this)">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(nuevaFila);
}

window.agregarFilaOcupabilidad = function() {
    const tbody = document.getElementById('ocupabilidad-body');
    if (!tbody) return;
    
    const nuevaFila = document.createElement('tr');
    nuevaFila.className = 'fila-ocupabilidad';
    nuevaFila.setAttribute('data-tipo', '');
    
    nuevaFila.innerHTML = `
        <td>
            <input type="text" class="form-control tipo-posicion" placeholder="Ej: Posiciones especiales" value="">
        </td>
        <td>
            <input type="number" class="form-control cantidad-posicion" value="" placeholder="Ingrese cantidad" min="0" onchange="actualizarTotalOcupabilidad()">
        </td>
        <td class="text-center">
            <button class="btn btn-danger btn-sm eliminar-fila" onclick="eliminarFilaOcupabilidad(this)">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(nuevaFila);
    
    const tipoInput = nuevaFila.querySelector('.tipo-posicion');
    if (tipoInput) tipoInput.focus();
};

window.eliminarFilaOcupabilidad = function(btn) {
    const fila = btn.closest('tr');
    const tipoInput = fila.querySelector('.tipo-posicion');
    const tipo = tipoInput ? tipoInput.value : '';
    
    if (tipo === 'Posiciones rack' || tipo === 'Posiciones rack (pallet adicional)') {
        Swal.fire('Advertencia', 'No se puede eliminar este tipo de posición predefinido', 'warning');
        return;
    }
    
    fila.remove();
    actualizarTotalOcupabilidad();
};

function actualizarTotalOcupabilidad() {
    let total = 0;
    const filas = document.querySelectorAll('#ocupabilidad-body .fila-ocupabilidad');
    
    filas.forEach(fila => {
        const cantidadInput = fila.querySelector('.cantidad-posicion');
        if (cantidadInput && cantidadInput.value) {
            total += parseInt(cantidadInput.value) || 0;
        }
    });
    
    const totalElement = document.getElementById('total-ocupabilidad');
    if (totalElement) totalElement.textContent = total;
}

window.guardarOcupabilidad = function() {
    const filas = document.querySelectorAll('#ocupabilidad-body .fila-ocupabilidad');
    const datos = [];
    let error = false;
    
    filas.forEach(fila => {
        const tipoInput = fila.querySelector('.tipo-posicion');
        const cantidadInput = fila.querySelector('.cantidad-posicion');
        
        const tipo = tipoInput ? tipoInput.value.trim() : '';
        const cantidad = cantidadInput ? parseInt(cantidadInput.value) || 0 : 0;
        
        if (!tipo) {
            Swal.fire('Error', 'Todos los tipos de posición deben tener un nombre', 'error');
            error = true;
            return;
        }
        
        datos.push({
            tipo: tipo,
            cantidad: cantidad
        });
    });
    
    if (error) return;
    
    if (datos.length === 0) {
        Swal.fire('Advertencia', 'Debe agregar al menos un tipo de posición', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    const payload = {
        id_factura: facturaTempId,
        datos_ocupabilidad: datos
    };
    
    fetch('../../Controller/fa_guardar_ocupabilidad.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) throw new Error(data.error);
        
        datosProcesados.ocupabilidad = {
            datos: datos,
            total: data.total_cantidades || datos.reduce((sum, d) => sum + d.cantidad, 0)
        };
        
        Swal.fire('Éxito', `Se guardaron ${datos.length} tipos de posición`, 'success');
        marcarModuloCompletado('ocupabilidad');
        mostrarDatosOcupabilidad(datos);
        $('#modalOcupabilidad').modal('hide');
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
};

// ============================================
// FUNCIONES PARA SERVICIOS
// ============================================

let serviciosData = [];

function cargarTarifasServicios() {
    return fetch('../../Controller/fa_get_tarifas_servicios.php?id_cliente=1')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                serviciosData = data.data;
                return serviciosData;
            } else {
                console.error('Error cargando tarifas:', data.error);
                return [];
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            return [];
        });
}

window.abrirModalServicios = async function() {
    console.log('abrirModalServicios llamado');
    
    if (serviciosData.length === 0) {
        await cargarTarifasServicios();
    }
    
    construirTablaServicios();
    
    if (datosProcesados.servicios && datosProcesados.servicios.datos) {
        cargarDatosServicios(datosProcesados.servicios.datos);
    }
    
    $('#modalServicios').modal('show');
};

function construirTablaServicios() {
    const tbody = document.getElementById('servicios-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const serviciosPredefinidos = [
        'Maquila',
        'Digitalizacion SAP',
        'Enmallados',
        'Temperatura controlada',
        'Uso de montacarga',
        'Etiquetado',
        'Revision/inspeccion a nivel unitario',
        'Hora hombre extra',
        'Hora hombre extra + recarga nocturna',
        'Hora hombre extra + feriado y domingo',
        'Impresión de facturas',
        'Timbrado',
        'Strech film',
        'Cajas RANSA',
        'Asistente de operaciones',
        'Caja chica'
    ];
    
    serviciosPredefinidos.forEach(servicio => {
        const tarifaInfo = serviciosData.find(s => s.servicio === servicio);
        const tarifa = tarifaInfo ? tarifaInfo.tarifa : 0;
        
        const fila = document.createElement('tr');
        fila.className = 'fila-servicio';
        fila.setAttribute('data-servicio', servicio);
        fila.setAttribute('data-tarifa', tarifa);
        
        fila.innerHTML = `
            <td class="servicio-nombre-cell">
                <strong>${escapeHtml(servicio)}</strong>
                <input type="hidden" class="servicio-nombre" value="${escapeHtml(servicio)}">
             </div>
            <td class="text-center tarifa-valor">${tarifa.toFixed(3)}</div>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm cantidad-servicio" value="0" min="0" step="0.01" placeholder="0" style="width: 100%; text-align: center;" onchange="calcularTotalServicio(this)">
             </div>
            <td class="text-center total-servicio">0.00</div>
        `;
        tbody.appendChild(fila);
    });
}

function cargarDatosServicios(datos) {
    if (!datos || !datos.length) return;
    
    const filas = document.querySelectorAll('#servicios-body .fila-servicio');
    
    filas.forEach(fila => {
        const cantidadInput = fila.querySelector('.cantidad-servicio');
        if (cantidadInput) {
            cantidadInput.value = 0;
            calcularTotalServicio(cantidadInput);
        }
    });
    
    datos.forEach(item => {
        let fila = Array.from(filas).find(f => {
            const hiddenInput = f.querySelector('.servicio-nombre');
            return hiddenInput && hiddenInput.value === item.servicio;
        });
        
        if (fila) {
            const cantidadInput = fila.querySelector('.cantidad-servicio');
            if (cantidadInput) {
                cantidadInput.value = item.cantidad;
                calcularTotalServicio(cantidadInput);
            }
        } else {
            agregarServicioPersonalizado(item.servicio, item.tarifa, item.cantidad);
        }
    });
    
    actualizarTotalGeneralServicios();
}

window.calcularTotalServicio = function(input) {
    const fila = input.closest('tr');
    const tarifaElement = fila.querySelector('.tarifa-valor');
    const tarifaInput = fila.querySelector('.tarifa-servicio');
    
    let tarifa = 0;
    if (tarifaElement) {
        tarifa = parseFloat(tarifaElement.textContent) || 0;
    } else if (tarifaInput) {
        tarifa = parseFloat(tarifaInput.value) || 0;
    }
    
    const cantidad = parseFloat(input.value) || 0;
    const total = tarifa * cantidad;
    
    const totalCell = fila.querySelector('.total-servicio');
    if (totalCell) {
        totalCell.textContent = total.toFixed(2);
    }
    
    actualizarTotalGeneralServicios();
};

function actualizarTotalGeneralServicios() {
    let totalGeneral = 0;
    const filas = document.querySelectorAll('#servicios-body .fila-servicio');
    
    filas.forEach(fila => {
        const totalCell = fila.querySelector('.total-servicio');
        if (totalCell) {
            totalGeneral += parseFloat(totalCell.textContent) || 0;
        }
    });
    
    const totalElement = document.getElementById('total-general-servicios');
    if (totalElement) {
        totalElement.textContent = totalGeneral.toFixed(2);
    }
}

window.agregarServicioPersonalizado = function(servicioNombre = '', tarifaValor = 0, cantidadValor = 0) {
    const tbody = document.getElementById('servicios-body');
    if (!tbody) return;
    
    const filasExistentes = tbody.querySelectorAll('.fila-servicio');
    const existe = Array.from(filasExistentes).some(fila => {
        const hiddenInput = fila.querySelector('.servicio-nombre');
        return hiddenInput && hiddenInput.value === servicioNombre;
    });
    
    if (existe && servicioNombre) {
        Swal.fire('Advertencia', 'Este servicio ya existe', 'warning');
        return;
    }
    
    const nuevaFila = document.createElement('tr');
    nuevaFila.className = 'fila-servicio servicio-personalizado';
    nuevaFila.setAttribute('data-servicio', servicioNombre || '');
    nuevaFila.setAttribute('data-tarifa', tarifaValor);
    
    nuevaFila.innerHTML = `
        71
            <input type="text" class="form-control form-control-sm servicio-nombre" value="${escapeHtml(servicioNombre)}" placeholder="Nombre del servicio" style="width: 100%;" onchange="actualizarNombreServicio(this)">
         </div>
        <td class="text-center">
            <input type="number" class="form-control form-control-sm tarifa-servicio" value="${tarifaValor}" step="0.001" min="0" style="width: 100%; text-align: center;" onchange="actualizarTarifaServicio(this)">
         </div>
        <td class="text-center">
            <input type="number" class="form-control form-control-sm cantidad-servicio" value="${cantidadValor}" min="0" step="0.01" placeholder="0" style="width: 100%; text-align: center;" onchange="calcularTotalServicio(this)">
         </div>
        <td class="text-center total-servicio">${(tarifaValor * cantidadValor).toFixed(2)}</div>
        <td class="text-center" style="width: 35px;">
            <button class="btn btn-danger btn-sm" onclick="eliminarServicioPersonalizado(this)" style="padding: 2px 6px;">
                <i class="fa fa-trash"></i>
            </button>
         </div>
    `;
    
    tbody.appendChild(nuevaFila);
    
    const cantidadInput = nuevaFila.querySelector('.cantidad-servicio');
    const tarifaInputNueva = nuevaFila.querySelector('.tarifa-servicio');
    
    if (cantidadInput) {
        cantidadInput.onchange = function() { calcularTotalServicio(this); };
    }
    
    if (tarifaInputNueva) {
        tarifaInputNueva.onchange = function() { 
            actualizarTarifaServicio(this);
        };
    }
    
    actualizarTotalGeneralServicios();
};

window.actualizarNombreServicio = function(input) {
    const fila = input.closest('tr');
    fila.setAttribute('data-servicio', input.value);
};

window.actualizarTarifaServicio = function(input) {
    const fila = input.closest('tr');
    const tarifa = parseFloat(input.value) || 0;
    fila.setAttribute('data-tarifa', tarifa);
    
    const cantidadInput = fila.querySelector('.cantidad-servicio');
    if (cantidadInput && cantidadInput.value > 0) {
        calcularTotalServicio(cantidadInput);
    } else {
        const totalCell = fila.querySelector('.total-servicio');
        if (totalCell) totalCell.textContent = '0.00';
        actualizarTotalGeneralServicios();
    }
};

window.eliminarServicioPersonalizado = function(btn) {
    const fila = btn.closest('tr');
    const nombreInput = fila.querySelector('.servicio-nombre');
    const servicioNombre = nombreInput ? nombreInput.value : '';
    
    if (servicioNombre && serviciosData.some(s => s.servicio === servicioNombre)) {
        const cantidadInput = fila.querySelector('.cantidad-servicio');
        if (cantidadInput) {
            cantidadInput.value = 0;
            calcularTotalServicio(cantidadInput);
        }
        Swal.fire('Información', 'Los servicios predefinidos no se pueden eliminar, solo se puede resetear la cantidad', 'info');
    } else {
        fila.remove();
        actualizarTotalGeneralServicios();
    }
};

window.guardarServicios = function() {
    const filas = document.querySelectorAll('#servicios-body .fila-servicio');
    const datos = [];
    
    filas.forEach(fila => {
        const nombreInput = fila.querySelector('.servicio-nombre');
        const nombre = nombreInput ? nombreInput.value.trim() : '';
        
        if (!nombre) return;
        
        let tarifa = 0;
        const tarifaSpan = fila.querySelector('.tarifa-valor');
        const tarifaInput = fila.querySelector('.tarifa-servicio');
        if (tarifaSpan) {
            tarifa = parseFloat(tarifaSpan.textContent) || 0;
        } else if (tarifaInput) {
            tarifa = parseFloat(tarifaInput.value) || 0;
        }
        
        const cantidadInput = fila.querySelector('.cantidad-servicio');
        const cantidad = cantidadInput ? parseFloat(cantidadInput.value) || 0 : 0;
        
        if (cantidad > 0) {
            datos.push({
                servicio: nombre,
                tarifa: tarifa,
                cantidad: cantidad
            });
        }
    });
    
    if (datos.length === 0) {
        Swal.fire('Advertencia', 'Debe ingresar al menos un servicio con cantidad mayor a 0', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    const payload = {
        id_factura: facturaTempId,
        datos_servicios: datos
    };
    
    fetch('../../Controller/fa_guardar_servicios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) throw new Error(data.error);
        
        datosProcesados.servicios = {
            datos: datos,
            total: data.total_general
        };
        
        Swal.fire('Éxito', `Se guardaron ${data.registros_guardados} servicios por un total de Bs ${data.total_general.toFixed(2)}`, 'success');
        marcarModuloCompletado('servicios');
        mostrarDatosServicios(datosProcesados.servicios);
        $('#modalServicios').modal('hide');
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
};

// ============================================
// FUNCIÓN AUXILIAR
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ============================================
// GUARDAR REGISTRO PRINCIPAL
// ============================================

window.guardarRegistro = function() {
    // Validar campos requeridos
    const sede = document.getElementById('sede_registro')?.value;
    const fechaInicio = document.getElementById('fecha_inicio')?.value;
    const fechaFin = document.getElementById('fecha_fin')?.value;
    
    if (!sede) {
        Swal.fire('Advertencia', 'Debe seleccionar una sede', 'warning');
        return;
    }
    
    if (!fechaInicio) {
        Swal.fire('Advertencia', 'Debe seleccionar la fecha de inicio del período', 'warning');
        return;
    }
    
    if (!fechaFin) {
        Swal.fire('Advertencia', 'Debe seleccionar la fecha de fin del período', 'warning');
        return;
    }
    
    if (fechaInicio > fechaFin) {
        Swal.fire('Advertencia', 'La fecha de inicio no puede ser mayor que la fecha de fin', 'warning');
        return;
    }
    
    if (!datosProcesados.recepcion && !datosProcesados.despacho && !datosProcesados.ocupabilidad && !datosProcesados.servicios) {
        Swal.fire('Advertencia', 'Debe completar al menos un módulo', 'warning');
        return;
    }
    
    const dataToSave = {
        id: registroId,
        recepcion: datosProcesados.recepcion ? 1 : 0,
        despacho: datosProcesados.despacho ? 1 : 0,
        ocupabilidad: datosProcesados.ocupabilidad ? 1 : 0,
        servicios: datosProcesados.servicios ? 1 : 0,
        estado: 'EN_PROCESO',
        sede: sede,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    };
    
    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch('../../Controller/fa_guardar_controller.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSave)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'Registro guardado correctamente', 'success').then(() => window.location.href = 'fa_main_arcor.php');
        } else {
            throw new Error(data.error);
        }
    })
    .catch(error => Swal.fire('Error', error.message, 'error'));
};