/**
 * fa_main_arcor.js
 * JavaScript para la gestión de la tabla fa_main para Arcor
 * Versión con visualización de observaciones en estados OBSERVADO y OBSERVACION_CLIENTE
 */

let datosRegistros = [];
let cargando = false;

function formatearFecha(fechaStr) {
    if (!fechaStr || fechaStr === '') return '-';
    // Si ya viene formateada como dd/mm/yyyy del SQL
    if (typeof fechaStr === 'string' && fechaStr.match(/^\d{2}\/\d{2}\/\d{4}/)) {
        return fechaStr;
    }
    // Si viene como YYYY-MM-DD
    if (typeof fechaStr === 'string' && fechaStr.match(/^\d{4}-\d{2}-\d{2}/)) {
        const [year, month, day] = fechaStr.split('-');
        return `${day}/${month}/${year}`;
    }
    return '-';
}

$(document).ready(function() {
    cargarTabla();
    cargarEstadisticas();
    inicializarEventos();
});

function inicializarEventos() {
    $('#filtro_estado').on('change', function() { 
        cargarTabla(); 
    });
}

function cargarEstadisticas() {
    $.ajax({
        url: '../../Controller/fa_main_arcor_controller.php',
        method: 'GET',
        data: { action: 'get_stats' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#totalRegistros').text(response.data.total);
                $('#totalCompletados').text(response.data.completados);
                $('#totalProceso').text(response.data.enProceso);
                $('#promedioCompletado').text(response.data.promedio + '%');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar estadísticas:', error);
        }
    });
}

function cargarTabla() {
    if (cargando) return;
    cargando = true;
    
    let estado = $('#filtro_estado').val();
    let tbody = $('#tablaBody');
    
    tbody.html('<tr><td colspan="11" class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando datos...</p></td></tr>');
    
    $.ajax({
        url: '../../Controller/fa_main_arcor_controller.php',
        method: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            cargando = false;
            
            if (response.success) {
                datosRegistros = response.data;
                
                let dataFiltrada = datosRegistros;
                if (estado) {
                    dataFiltrada = datosRegistros.filter(function(item) {
                        return item.estado === estado;
                    });
                }
                
                renderizarTabla(dataFiltrada);
            } else {
                tbody.html('<tr><td colspan="11" class="text-center"><div class="empty-message"><i class="fa fa-exclamation-triangle text-danger"></i><p>Error al cargar datos</p></div></td></tr>');
            }
        },
        error: function(xhr, status, error) {
            cargando = false;
            console.error('Error:', error);
            tbody.html('<tr><td colspan="11" class="text-center"><div class="empty-message"><i class="fa fa-exclamation-triangle text-danger"></i><p>Error de conexión: ' + error + '</p></div></td></tr>');
        }
    });
}

function renderizarTabla(data) {
    let tbody = $('#tablaBody');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="11" class="text-center"><div class="empty-message"><i class="fa fa-inbox"></i><p>No hay registros disponibles</p><small>Haga clic en "Nuevo Registro" para agregar uno</small></div></td></tr>');
        return;
    }
    
    data.forEach(function(row) {
        let completados = 0;
        if (row.recepcion == 1) completados++;
        if (row.despacho == 1) completados++;
        if (row.ocupabilidad == 1) completados++;
        if (row.servicios == 1) completados++;
        let porcentaje = (completados / 4) * 100;
        let modulosCompletados = (completados == 4);
        let estadoEsVerificado = (row.estado === 'VERIFICADO');
        
        // ============================================
        // ESTADO HTML CON CLICK PARA MOSTRAR OBSERVACIÓN
        // ============================================
        let estadoClass = `estado-${row.estado}`;
        let estadoHtml = '';
        
        // Verificar si el estado es OBSERVADO y tiene observacion_aprobador
        if (row.estado === 'OBSERVADO' && row.observacion_aprobador && row.observacion_aprobador.trim() !== '') {
            estadoHtml = `<span class="estado-badge ${estadoClass}" style="cursor: pointer;" onclick="mostrarObservacion('${escapeHtml(row.observacion_aprobador)}', ${row.id}, 'Observación del Aprobador')">
                            <i class="fa fa-comment"></i> ${row.estado}
                          </span>`;
        }
        // Verificar si el estado es OBSERVACION_CLIENTE y tiene observacion_cliente
        else if (row.estado === 'OBSERVACION_CLIENTE' && row.observacion_cliente && row.observacion_cliente.trim() !== '') {
            estadoHtml = `<span class="estado-badge ${estadoClass}" style="cursor: pointer;" onclick="mostrarObservacion('${escapeHtml(row.observacion_cliente)}', ${row.id}, 'Observación del Cliente')">
                            <i class="fa fa-comment"></i> ${row.estado}
                          </span>`;
        }
        // Si no hay observación, mostrar solo el estado sin click
        else {
            estadoHtml = `<span class="estado-badge ${estadoClass}">${row.estado}</span>`;
        }
        
        // ============================================
        // RECEPCIÓN - Botones con nuevo diseño
        // ============================================
        let recepcionHtml = '';
        if (row.recepcion == 1) {
            recepcionHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('recepcion', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-update-module" onclick="editarModulo(${row.id}, 'recepcion')" title="Actualizar">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>` : `<button class="btn-modulo btn-update-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>`}
                </div>
            `;
        } else {
            recepcionHtml = `
                <div class="modulo-buttons">
                    <span class="modulo-pendiente-text">Pendiente</span>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-edit-module" onclick="editarModulo(${row.id}, 'recepcion')" title="Editar">
                        <i class="fa fa-pencil"></i>
                    </button>` : `<button class="btn-modulo btn-edit-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-pencil"></i>
                    </button>`}
                </div>
            `;
        }
        
        // ============================================
        // DESPACHO
        // ============================================
        let despachoHtml = '';
        if (row.despacho == 1) {
            despachoHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('despacho', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-update-module" onclick="editarModulo(${row.id}, 'despacho')" title="Actualizar">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>` : `<button class="btn-modulo btn-update-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>`}
                </div>
            `;
        } else {
            despachoHtml = `
                <div class="modulo-buttons">
                    <span class="modulo-pendiente-text">Pendiente</span>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-edit-module" onclick="editarModulo(${row.id}, 'despacho')" title="Editar">
                        <i class="fa fa-pencil"></i>
                    </button>` : `<button class="btn-modulo btn-edit-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-pencil"></i>
                    </button>`}
                </div>
            `;
        }
        
        // ============================================
        // OCUPABILIDAD
        // ============================================
        let ocupabilidadHtml = '';
        if (row.ocupabilidad == 1) {
            ocupabilidadHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('ocupabilidad', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-update-module" onclick="editarModulo(${row.id}, 'ocupabilidad')" title="Actualizar">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>` : `<button class="btn-modulo btn-update-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>`}
                </div>
            `;
        } else {
            ocupabilidadHtml = `
                <div class="modulo-buttons">
                    <span class="modulo-pendiente-text">Pendiente</span>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-edit-module" onclick="editarModulo(${row.id}, 'ocupabilidad')" title="Editar">
                        <i class="fa fa-pencil"></i>
                    </button>` : `<button class="btn-modulo btn-edit-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-pencil"></i>
                    </button>`}
                </div>
            `;
        }
        
        // ============================================
        // SERVICIOS
        // ============================================
        let serviciosHtml = '';
        if (row.servicios == 1) {
            serviciosHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('servicios', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-update-module" onclick="editarModulo(${row.id}, 'servicios')" title="Actualizar">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>` : `<button class="btn-modulo btn-update-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>`}
                </div>
            `;
        } else {
            serviciosHtml = `
                <div class="modulo-buttons">
                    <span class="modulo-pendiente-text">Pendiente</span>
                    ${!estadoEsVerificado ? `<button class="btn-modulo btn-edit-module" onclick="editarModulo(${row.id}, 'servicios')" title="Editar">
                        <i class="fa fa-pencil"></i>
                    </button>` : `<button class="btn-modulo btn-edit-module disabled" disabled title="No disponible en estado VERIFICADO">
                        <i class="fa fa-pencil"></i>
                    </button>`}
                </div>
            `;
        }
        
        // ============================================
        // RESUMEN - Sin badge de disponible
        // ============================================
        let resumenHtml = '';
        if (modulosCompletados) {
            resumenHtml = `
                <div class="resumen-completo">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('resumen', ${row.id})" title="Generar PDF Completo">
                        <i class="fa fa-file-pdf-o"></i> PDF Completo
                    </button>
                </div>
            `;
        } else {
            resumenHtml = `
                <div class="resumen-incompleto">
                    <span class="badge-gris">Faltan ${4 - completados} módulo(s)</span>
                </div>
            `;
        }
        
        // ============================================
        // FECHAS
        // ============================================
        let fechaInicioHtml = formatearFecha(row.fecha1);
        let fechaFinHtml = formatearFecha(row.fecha2);
        
        // ============================================
        // SEDE
        // ============================================
        let sedeHtml = row.sede ? row.sede : '-';
        
        // ============================================
        // ACCIONES
        // ============================================
        let accionesHtml = '';
        
        // Botón de verificar - SOLO SI LOS 4 MÓDULOS ESTÁN COMPLETADOS Y ESTADO NO ES VERIFICADO
        if (modulosCompletados && !estadoEsVerificado && row.estado !== 'VERIFICADO') {
            accionesHtml += `
                <button class="btn-accion btn-verificar" onclick="verificarFactura(${row.id})" title="Verificar factura">
                    <i class="fa fa-check-circle"></i> Verificar
                </button>
            `;
        }
        
        // Botón de eliminar (solo si estado es EN_PROCESO y NO está verificado)
        if (row.estado === 'EN_PROCESO' && !estadoEsVerificado) {
            accionesHtml += `
                <button class="btn-accion btn-eliminar" onclick="eliminarRegistro(${row.id})" title="Eliminar">
                    <i class="fa fa-trash"></i> Eliminar
                </button>
            `;
        }
        
        // Si no hay acciones, mostrar un mensaje
        if (accionesHtml === '') {
            accionesHtml = `<span class="text-muted">-</span>`;
        }
        
        let rowHtml = `
            <tr>
                <td class="text-center">${row.id}</td>
                <td class="text-center">${estadoHtml}</td>
                <td class="text-center">${recepcionHtml}</td>
                <td class="text-center">${despachoHtml}</td>
                <td class="text-center">${ocupabilidadHtml}</td>
                <td class="text-center">${serviciosHtml}</td>
                <td class="text-center">${resumenHtml}</td>
                <td class="text-center">${fechaInicioHtml}</td>
                <td class="text-center">${fechaFinHtml}</td>
                <td class="text-center">${sedeHtml}</td>
                <td class="text-center">${accionesHtml}</td>
            </tr>
        `;
        
        tbody.append(rowHtml);
    });
}

// ============================================
// FUNCIÓN PARA MOSTRAR OBSERVACIÓN
// ============================================
function mostrarObservacion(observacion, facturaId, titulo) {
    console.log('Mostrando observación para factura ID:', facturaId);
    console.log('Observación:', observacion);
    
    Swal.fire({
        title: titulo,
        html: `
            <div style="text-align: left;">
                <p><strong>Factura ID:</strong> ${facturaId}</p>
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 8px;">
                    <strong><i class="fa fa-comment"></i> Motivo:</strong>
                    <div style="margin-top: 10px; white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto;">
                        ${escapeHtml(observacion)}
                    </div>
                </div>
            </div>
        `,
        icon: 'warning',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#009a3f',
        width: '600px'
    });
}

// ============================================
// FUNCIÓN PARA GENERAR PDF (UNIFICADA)
// ============================================
function generarPDF(tipo, id) {
    let url = '';
    switch(tipo) {
        case 'recepcion':
            url = '../../Controller/generar_pdf_recepcion.php?id=' + id;
            break;
        case 'despacho':
            url = '../../Controller/generar_pdf_despacho.php?id=' + id;
            break;
        case 'ocupabilidad':
            url = '../../Controller/generar_pdf_ocupabilidad.php?id=' + id;
            break;
        case 'servicios':
            url = '../../Controller/generar_pdf_servicios.php?id=' + id;
            break;
        case 'resumen':
            url = '../../Controller/generar_pdf_resumen.php?id=' + id;
            break;
        default:
            console.error('Tipo de PDF no válido:', tipo);
            return;
    }
    
    window.open(url, '_blank');
}

// ============================================
// FUNCIONES DE EDICIÓN
// ============================================
function editarModulo(id, modulo) {
    window.location.href = `index.php?opc=fa_crear_arcor&id=${id}&modulo=${modulo}`;
}

// ============================================
// FUNCIÓN DE ELIMINACIÓN
// ============================================
function eliminarRegistro(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../Controller/fa_main_arcor_controller.php',
                method: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', 'Registro eliminado correctamente', 'success');
                        cargarTabla();
                        cargarEstadisticas();
                    } else {
                        Swal.fire('Error', response.error || 'Error al eliminar', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            });
        }
    });
}

// ============================================
// FUNCIÓN DE LIMPIEZA DE FILTROS
// ============================================
function limpiarFiltros() {
    $('#filtro_estado').val('');
    cargarTabla();
}

// ============================================
// FUNCIÓN PARA VERIFICAR FACTURA
// ============================================
function verificarFactura(id) {
    console.log('🔍 Verificando factura ID:', id);
    
    if (!id || id <= 0) {
        Swal.fire('Error', 'ID de factura no válido', 'error');
        return;
    }
    
    Swal.fire({
        title: '¿Verificar factura?',
        html: `<p>Factura ID: <strong>${id}</strong></p>
               <p>Al verificar esta factura:</p>
               <ul style="text-align: left;">
                   <li>El estado cambiará a <strong>VERIFICADO</strong></li>
                   <li>Se enviará una notificación por correo a los supervisores</li>
                   <li>Los botones de edición se deshabilitarán</li>
               </ul>
               <p>¿Desea continuar?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#009a3f',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sí, verificar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Verificando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_verificar_factura.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ factura_id: id })
            })
            .then(async response => {
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parseando JSON:', text);
                    throw new Error('Respuesta inválida del servidor');
                }
                
                if (data.success) {
                    Swal.fire({
                        title: '¡Factura verificada!',
                        text: data.error,
                        icon: 'success'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al verificar');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message, 'error');
            });
        }
    });
}

// ============================================
// FUNCIÓN PARA ESCAPAR HTML
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
        .replace(/\n/g, "<br>");
}