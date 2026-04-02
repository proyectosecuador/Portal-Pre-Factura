/**
 * fa_aprobar_cliente_arcor.js
 * JavaScript para la gestión de aprobación de facturas por parte del cliente
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
        url: '../../Controller/fa_aprobar_cliente_controller.php',
        method: 'GET',
        data: { action: 'get_stats' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#totalPendientesAprobacion').text(response.data.pendientes_aprobacion);
                $('#totalAprobadasCliente').text(response.data.aprobadas_cliente);
                $('#totalObservadasCliente').text(response.data.observadas_cliente);
                $('#totalFacturadas').text(response.data.facturadas);
                $('#totalPagadas').text(response.data.pagadas);
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
        url: '../../Controller/fa_aprobar_cliente_controller.php',
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
        tbody.html('<tr><td colspan="11" class="text-center"><div class="empty-message"><i class="fa fa-inbox"></i><p>No hay facturas para mostrar</p></div></td></tr>');
        return;
    }
    
    data.forEach(function(row) {
        let completados = 0;
        if (row.recepcion == 1) completados++;
        if (row.despacho == 1) completados++;
        if (row.ocupabilidad == 1) completados++;
        if (row.servicios == 1) completados++;
        
        let estadoClass = `estado-${row.estado}`;
        let estadoHtml = `<span class="estado-badge ${estadoClass}">${row.estado}</span>`;
        
        // Si el estado es OBSERVACION_CLIENTE, mostrar observación
        if (row.estado === 'OBSERVACION_CLIENTE' && row.observacion_cliente && row.observacion_cliente.trim() !== '') {
            estadoHtml = `<span class="estado-badge ${estadoClass}" style="cursor: pointer;" onclick="verObservacion('${escapeHtml(row.observacion_cliente)}', ${row.id}, 'Observación del Cliente')">
                            <i class="fa fa-comment"></i> ${row.estado}
                          </span>`;
        }
        
        // Para APROBADO_CLIENTE mostrar quién aprobó
        if (row.estado === 'APROBADO_CLIENTE' && row.cliente_aprobador) {
            estadoHtml = `<span class="estado-badge ${estadoClass}" title="Aprobado por: ${escapeHtml(row.cliente_aprobador)}">${row.estado}</span>`;
        }
        
        // Para FACTURADO mostrar info de facturación
        if (row.estado === 'FACTURADO' && row.numero_factura) {
            estadoHtml = `<span class="estado-badge ${estadoClass}" title="Factura: ${escapeHtml(row.numero_factura)}">${row.estado}</span>`;
        }
        
        // ============================================
        // RECEPCIÓN (solo PDF con nuevo estilo)
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
        // DESPACHO (solo PDF con nuevo estilo)
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
        // OCUPABILIDAD (solo PDF con nuevo estilo)
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
        // SERVICIOS (solo PDF con nuevo estilo)
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
        // FECHAS
        // ============================================
        let fechaInicioHtml = row.fecha1 ? row.fecha1 : '-';
        let fechaFinHtml = row.fecha2 ? row.fecha2 : '-';
        
        // ============================================
        // SEDE
        // ============================================
        let sedeHtml = row.sede ? row.sede : '-';
        
        // ============================================
        // ACCIONES (con botones con texto)
        // ============================================
        let accionesHtml = '';
        
        // Estado APROBADO: botones de Aprobar y Rechazar
        if (row.estado === 'APROBADO') {
            accionesHtml = `
                <div class="acciones-btns">
                    <button class="btn-accion btn-aprobar-cliente" onclick="aprobarFacturaCliente(${row.id})" title="Aprobar factura">
                        <i class="fa fa-check-circle"></i> Aprobar
                    </button>
                    <button class="btn-accion btn-rechazar-cliente" onclick="rechazarFacturaCliente(${row.id})" title="Rechazar factura">
                        <i class="fa fa-times-circle"></i> Rechazar
                    </button>
                </div>
            `;
        }
        
        // Estado FACTURADO: botón para confirmar pago
        if (row.estado === 'FACTURADO') {
            accionesHtml = `
                <div class="acciones-btns">
                    <button class="btn-accion btn-confirmar-pago" onclick="confirmarPagoFactura(${row.id})" title="Confirmar pago">
                        <i class="fa fa-money"></i> Confirmar Pago
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
function verObservacion(observacion, facturaId, titulo = 'Detalle de Observación') {
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
// FUNCIÓN PARA APROBAR FACTURA (CLIENTE)
// ============================================
function aprobarFacturaCliente(id) {
    console.log('✅ Aprobando factura ID:', id);
    
    Swal.fire({
        title: '¿Aprobar factura?',
        html: `<p>Factura ID: <strong>${id}</strong></p>
               <p>Al aprobar esta factura:</p>
               <ul style="text-align: left;">
                   <li>El estado cambiará a <strong>APROBADO_CLIENTE</strong></li>
                   <li>Se enviará una notificación por correo a todos los destinatarios</li>
                   <li>El siguiente paso será que Contabilidad realice la facturación</li>
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
            
            fetch('../../Controller/fa_aprobar_cliente_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ factura_id: id, accion: 'aprobar_cliente' })
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
                        text: data.message,
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
// FUNCIÓN PARA RECHAZAR FACTURA (CLIENTE)
// ============================================
function rechazarFacturaCliente(id) {
    console.log('❌ Rechazando factura ID:', id);
    
    Swal.fire({
        title: 'Rechazar factura',
        html: `
            <p>Factura ID: <strong>${id}</strong></p>
            <div style="text-align: left;">
                <label for="motivo">Motivo del rechazo:</label>
                <textarea id="motivoRechazo" class="swal2-textarea" rows="4" placeholder="Describa detalladamente el motivo del rechazo..."></textarea>
                <small class="text-muted">Este motivo será enviado por correo a los aprobadores y verificadores.</small>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Rechazar factura',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const motivo = document.getElementById('motivoRechazo').value;
            if (!motivo || motivo.trim() === '') {
                Swal.showValidationMessage('Debe ingresar un motivo para el rechazo');
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
                title: 'Procesando rechazo...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_aprobar_cliente_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    factura_id: id, 
                    accion: 'rechazar_cliente',
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
                        title: 'Factura rechazada',
                        text: data.message,
                        icon: 'info'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al procesar rechazo');
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
// FUNCIÓN PARA CONFIRMAR PAGO (FACTURADO -> PAGADO)
// ============================================
function confirmarPagoFactura(id) {
    console.log('💰 Confirmando pago factura ID:', id);
    
    Swal.fire({
        title: 'Confirmar Pago',
        html: `
            <p>Factura ID: <strong>${id}</strong></p>
            <div style="text-align: left;">
                <p>Al confirmar el pago:</p>
                <ul style="text-align: left;">
                    <li>El estado cambiará a <strong>PAGADO</strong></li>
                    <li>Se enviará una notificación por correo a <strong>TODOS</strong> los destinatarios del cliente</li>
                    <li>La factura quedará registrada como pagada en el sistema</li>
                </ul>
                <div class="info-pago">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Importante:</strong> Solo confirme el pago si realmente se ha realizado la transferencia o pago de la factura.
                </div>
                <label for="comprobante">Número de comprobante/transferencia (opcional):</label>
                <input type="text" id="comprobantePago" class="swal2-input" placeholder="Ej: TRANS-123456, CHEQUE-789, etc.">
                <label for="fechaPago" style="margin-top: 10px;">Fecha de pago:</label>
                <input type="date" id="fechaPago" class="swal2-input" value="${new Date().toISOString().split('T')[0]}">
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#20c997',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Confirmar Pago',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const comprobante = document.getElementById('comprobantePago').value;
            const fechaPago = document.getElementById('fechaPago').value;
            return {
                comprobante: comprobante || null,
                fecha_pago: fechaPago
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_aprobar_cliente_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    factura_id: id, 
                    accion: 'confirmar_pago',
                    comprobante: result.value.comprobante,
                    fecha_pago: result.value.fecha_pago
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
                        title: '¡Pago confirmado!',
                        text: data.message,
                        icon: 'success'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al confirmar pago');
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

// ============================================
// FUNCIÓN DE LIMPIEZA DE FILTROS
// ============================================
function limpiarFiltros() {
    $('#filtro_estado').val('');
    cargarTabla();
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