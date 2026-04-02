/**
 * fa_aprobar_arcor.js
 * JavaScript para la gestión de aprobación de facturas
 * CON DISEÑO MEJORADO (mismo estilo que fa_main_arcor)
 */

let datosRegistros = [];
let cargando = false;

function formatearFecha(fechaStr) {
    if (!fechaStr || fechaStr === '') return '-';
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
        url: '../../Controller/fa_aprobar_arcor_controller.php',
        method: 'GET',
        data: { action: 'get_stats' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#totalPendientes').text(response.data.pendientes);
                $('#totalAprobadas').text(response.data.aprobadas);
                $('#totalObservadas').text(response.data.observadas);
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
        url: '../../Controller/fa_aprobar_arcor_controller.php',
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
        tbody.html('<tr><td colspan="11" class="text-center"><div class="empty-message"><i class="fa fa-inbox"></i><p>No hay facturas pendientes de aprobación</p><small>Las facturas que estén en estado VERIFICADO aparecerán aquí</small></div></td></tr>');
        return;
    }
    
    data.forEach(function(row) {
        let completados = 0;
        if (row.recepcion == 1) completados++;
        if (row.despacho == 1) completados++;
        if (row.ocupabilidad == 1) completados++;
        if (row.servicios == 1) completados++;
        let porcentaje = (completados / 4) * 100;
        
        let estadoClass = `estado-${row.estado}`;
        let estadoHtml = `<span class="estado-badge ${estadoClass}">${row.estado}</span>`;
        
        // Si el estado es OBSERVADO, agregar evento de clic para mostrar observación
        if (row.estado === 'OBSERVADO' && row.observacion_aprobador && row.observacion_aprobador.trim() !== '') {
            estadoHtml = `<span class="estado-badge ${estadoClass}" style="cursor: pointer;" onclick="verObservacion('${escapeHtml(row.observacion_aprobador)}', ${row.id}, 'Observación del Aprobador')">
                            <i class="fa fa-comment"></i> ${row.estado}
                          </span>`;
        }
        
        // ============================================
        // RECEPCIÓN (solo PDF)
        // ============================================
        let recepcionHtml = '';
        if (row.recepcion == 1) {
            recepcionHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('recepcion', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                </div>
            `;
        } else {
            recepcionHtml = `<span class="modulo-pendiente-text">Pendiente</span>`;
        }
        
        // ============================================
        // DESPACHO (solo PDF)
        // ============================================
        let despachoHtml = '';
        if (row.despacho == 1) {
            despachoHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('despacho', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                </div>
            `;
        } else {
            despachoHtml = `<span class="modulo-pendiente-text">Pendiente</span>`;
        }
        
        // ============================================
        // OCUPABILIDAD (solo PDF)
        // ============================================
        let ocupabilidadHtml = '';
        if (row.ocupabilidad == 1) {
            ocupabilidadHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('ocupabilidad', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                </div>
            `;
        } else {
            ocupabilidadHtml = `<span class="modulo-pendiente-text">Pendiente</span>`;
        }
        
        // ============================================
        // SERVICIOS (solo PDF)
        // ============================================
        let serviciosHtml = '';
        if (row.servicios == 1) {
            serviciosHtml = `
                <div class="modulo-buttons">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('servicios', ${row.id})" title="Generar PDF">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </button>
                </div>
            `;
        } else {
            serviciosHtml = `<span class="modulo-pendiente-text">Pendiente</span>`;
        }
        
        // ============================================
        // RESUMEN
        // ============================================
        let resumenHtml = '';
        if (completados == 4) {
            resumenHtml = `
                <div class="resumen-completo">
                    <button class="btn-modulo btn-pdf-module" onclick="generarPDF('resumen', ${row.id})" title="Generar PDF Completo">
                        <i class="fa fa-file-pdf-o"></i> PDF Completo
                    </button>
                    <span class="badge-verde">Disponible</span>
                </div>
            `;
        } else {
            resumenHtml = `
                <div class="resumen-incompleto">
                    <span class="badge-gris">No disponible</span>
                    <small>Faltan ${4 - completados} módulo(s)</small>
                </div>
            `;
        }
        
        // ============================================
        // FECHAS (solo fecha, sin hora)
        // ============================================
        let fechaInicioHtml = row.fecha1 ? row.fecha1 : '-';
        let fechaFinHtml = row.fecha2 ? row.fecha2 : '-';
        
        // ============================================
        // SEDE
        // ============================================
        let sedeHtml = row.sede ? row.sede : '-';
        
        // ============================================
        // ACCIONES
        // ============================================
        let accionesHtml = '';
        
        if (row.estado === 'VERIFICADO') {
            accionesHtml = `
                <div class="acciones-btns">
                    <button class="btn-accion btn-aprobar" onclick="aprobarFactura(${row.id})" title="Aprobar factura">
                        <i class="fa fa-check-circle"></i> Aprobar
                    </button>
                    <button class="btn-accion btn-observar" onclick="observarFactura(${row.id})" title="Observar factura">
                        <i class="fa fa-exclamation-triangle"></i> Observar
                    </button>
                </div>
            `;
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
// FUNCIÓN PARA VER OBSERVACIÓN
// ============================================
function verObservacion(observacion, facturaId, titulo) {
    Swal.fire({
        title: titulo || 'Observación de Factura',
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
// FUNCIÓN PARA APROBAR FACTURA
// ============================================
function aprobarFactura(id) {
    console.log('🔍 Aprobando factura ID:', id);
    
    Swal.fire({
        title: '¿Aprobar factura?',
        html: `<p>Factura ID: <strong>${id}</strong></p>
               <p>Al aprobar esta factura:</p>
               <ul style="text-align: left;">
                   <li>El estado cambiará a <strong>APROBADO</strong></li>
                   <li>Se enviará una notificación por correo a los destinatarios del cliente</li>
               </ul>
               <p>¿Desea continuar?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Aprobando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_aprobar_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ factura_id: id, accion: 'aprobar' })
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
                        title: '¡Factura aprobada!',
                        text: data.error || data.message || 'Factura aprobada correctamente',
                        icon: 'success'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al aprobar');
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
// FUNCIÓN PARA OBSERVAR FACTURA
// ============================================
function observarFactura(id) {
    console.log('🔍 Observando factura ID:', id);
    
    Swal.fire({
        title: 'Observar factura',
        html: `
            <p>Factura ID: <strong>${id}</strong></p>
            <div style="text-align: left;">
                <label for="motivo">Motivo de la observación:</label>
                <textarea id="motivoObservacion" class="swal2-textarea" rows="4" placeholder="Describa el motivo de la observación..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Enviar observación',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const motivo = document.getElementById('motivoObservacion').value;
            if (!motivo || motivo.trim() === '') {
                Swal.showValidationMessage('Debe ingresar un motivo para la observación');
                return false;
            }
            if (motivo.trim().length < 10) {
                Swal.showValidationMessage('Por favor, proporcione un motivo más detallado (mínimo 10 caracteres)');
                return false;
            }
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Enviando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_aprobar_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    factura_id: id, 
                    accion: 'observar',
                    motivo: result.value
                })
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
                        title: 'Factura observada',
                        text: data.error || data.message || 'Observación registrada correctamente',
                        icon: 'warning'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al procesar observación');
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
// FUNCIÓN PARA GENERAR PDF
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

function limpiarFiltros() {
    $('#filtro_estado').val('');
    cargarTabla();
}

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