/**
 * fa_control_facturas.js
 * JavaScript para el control de facturación (Contabilidad)
 * CON DISEÑO MEJORADO (mismo estilo que main)
 */

let datosRegistros = [];
let cargando = false;
let clientes = [];
let sedes = [];

function formatearFecha(fechaStr) {
    if (!fechaStr || fechaStr === '') return '-';
    if (typeof fechaStr === 'string' && fechaStr.match(/^\d{4}-\d{2}-\d{2}/)) {
        const [year, month, day] = fechaStr.split('-');
        return `${day}/${month}/${year}`;
    }
    return '-';
}

$(document).ready(function() {
    cargarClientes();
    cargarSedes();
    cargarTabla();
    cargarEstadisticas();
    inicializarEventos();
});

function inicializarEventos() {
    $('#filtro_estado').on('change', function() { 
        cargarTabla(); 
    });
    $('#filtro_cliente').on('change', function() { 
        cargarTabla(); 
    });
    $('#filtro_sede').on('change', function() { 
        cargarTabla(); 
    });
}

function cargarClientes() {
    $.ajax({
        url: '../../Controller/fa_control_facturas_controller.php',
        method: 'GET',
        data: { action: 'get_clientes' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                clientes = response.data;
                let select = $('#filtro_cliente');
                select.empty();
                select.append('<option value="">Todos los clientes</option>');
                clientes.forEach(function(cliente) {
                    select.append(`<option value="${cliente.id}">${escapeHtml(cliente.nombre_comercial)}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar clientes:', error);
        }
    });
}

function cargarSedes() {
    $.ajax({
        url: '../../Controller/fa_control_facturas_controller.php',
        method: 'GET',
        data: { action: 'get_sedes' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                sedes = response.data;
                let select = $('#filtro_sede');
                select.empty();
                select.append('<option value="">Todas las sedes</option>');
                sedes.forEach(function(sede) {
                    select.append(`<option value="${sede}">${escapeHtml(sede)}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar sedes:', error);
        }
    });
}

function cargarEstadisticas() {
    $.ajax({
        url: '../../Controller/fa_control_facturas_controller.php',
        method: 'GET',
        data: { action: 'get_stats' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#totalPendientesFacturar').text(response.data.pendientes_facturar);
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
    let id_cliente = $('#filtro_cliente').val();
    let sede = $('#filtro_sede').val();
    let tbody = $('#tablaBody');
    
    tbody.html('<tr><td colspan="13" class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando datos...</p></td></tr>');
    
    $.ajax({
        url: '../../Controller/fa_control_facturas_controller.php',
        method: 'GET',
        data: { 
            action: 'list',
            estado: estado,
            id_cliente: id_cliente,
            sede: sede
        },
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            cargando = false;
            
            if (response.success) {
                datosRegistros = response.data;
                renderizarTabla(datosRegistros);
            } else {
                tbody.html('<tr><td colspan="13" class="text-center"><div class="empty-message"><i class="fa fa-exclamation-triangle text-danger"></i><p>Error al cargar datos</p></div></td></tr>');
            }
        },
        error: function(xhr, status, error) {
            cargando = false;
            console.error('Error:', error);
            tbody.html('<tr><td colspan="13" class="text-center"><div class="empty-message"><i class="fa fa-exclamation-triangle text-danger"></i><p>Error de conexión: ' + error + '</p></div></td></tr>');
        }
    });
}

function renderizarTabla(data) {
    let tbody = $('#tablaBody');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="13" class="text-center"><div class="empty-message"><i class="fa fa-inbox"></i><p>No hay facturas para mostrar</p></div></td></tr>');
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
        // NÚMERO DE FACTURA
        // ============================================
        let numeroFacturaHtml = row.numero_factura ? row.numero_factura : '-';
        
        // ============================================
        // ACCIONES
        // ============================================
        let accionesHtml = '';
        
        // Estado APROBADO_CLIENTE: botón para facturar
        if (row.estado === 'APROBADO_CLIENTE') {
            accionesHtml = `
                <div class="acciones-btns">
                    <button class="btn-accion btn-facturar" onclick="facturarFactura(${row.id}, '${escapeHtml(row.nombre_comercial)}')" title="Facturar">
                        <i class="fa fa-file-text"></i> Facturar
                    </button>
                </div>
            `;
        }
        
        // Estado FACTURADO: mostrar información de factura
        if (row.estado === 'FACTURADO') {
            accionesHtml = `
                <div class="acciones-btns">
                    <span class="cliente-badge"><i class="fa fa-check"></i> Facturado</span>
                </div>
            `;
        }
        
        // Estado PAGADO: mostrar confirmación
        if (row.estado === 'PAGADO') {
            accionesHtml = `
                <div class="acciones-btns">
                    <span class="cliente-badge" style="background:#20c997; color:white;"><i class="fa fa-money"></i> Pagado</span>
                </div>
            `;
        }
        
        let rowHtml = `
            <tr>
                <td class="text-center">${row.id}</td>
                <td><strong>${escapeHtml(row.nombre_comercial)}</strong><br><small>${escapeHtml(row.codigo_cliente || '')}</small></td>
                <td class="text-center">${estadoHtml}</td>
                <td class="text-center">${recepcionHtml}</td>
                <td class="text-center">${despachoHtml}</td>
                <td class="text-center">${ocupabilidadHtml}</td>
                <td class="text-center">${serviciosHtml}</td>
                <td class="text-center">${resumenHtml}</td>
                <td class="text-center">${fechaInicioHtml}</td>
                <td class="text-center">${fechaFinHtml}</td>
                <td class="text-center">${sedeHtml}</td>
                <td class="text-center">${numeroFacturaHtml}</td>
                <td class="text-center">${accionesHtml}</td>
            </tr>
        `;
        
        tbody.append(rowHtml);
    });
}

// ============================================
// FUNCIÓN PARA FACTURAR (APROBADO_CLIENTE -> FACTURADO)
// ============================================
function facturarFactura(id, nombreCliente) {
    console.log('📄 Facturando factura ID:', id);
    
    Swal.fire({
        title: 'Facturar Pre-Factura',
        html: `
            <p><strong>Cliente:</strong> ${escapeHtml(nombreCliente)}</p>
            <p><strong>ID Pre-Factura:</strong> ${id}</p>
            <div style="text-align: left; margin-top: 15px;">
                <label for="numeroFactura">Número de Factura:</label>
                <input type="text" id="numeroFactura" class="swal2-input" placeholder="Ej: FAC-001-2024" required>
                <label for="fechaFactura" style="margin-top: 10px;">Fecha de Factura:</label>
                <input type="date" id="fechaFactura" class="swal2-input" value="${new Date().toISOString().split('T')[0]}" required>
                <label for="montoFactura" style="margin-top: 10px;">Monto Facturado (Bs):</label>
                <input type="number" id="montoFactura" class="swal2-input" placeholder="0.00" step="0.01">
                <div class="info-factura" style="margin-top: 15px;">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Nota:</strong> Al facturar, se enviará un correo al cliente y a los supervisores/contabilidad notificando que la factura está lista para pago.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6f42c1',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Generar Factura',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const numeroFactura = document.getElementById('numeroFactura').value;
            const fechaFactura = document.getElementById('fechaFactura').value;
            const montoFactura = document.getElementById('montoFactura').value;
            
            if (!numeroFactura || numeroFactura.trim() === '') {
                Swal.showValidationMessage('Debe ingresar el número de factura');
                return false;
            }
            if (!fechaFactura) {
                Swal.showValidationMessage('Debe ingresar la fecha de factura');
                return false;
            }
            return {
                numero_factura: numeroFactura,
                fecha_factura: fechaFactura,
                monto_factura: montoFactura || null
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando facturación...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            fetch('../../Controller/fa_control_facturas_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    factura_id: id, 
                    accion: 'facturar',
                    numero_factura: result.value.numero_factura,
                    fecha_factura: result.value.fecha_factura,
                    monto_factura: result.value.monto_factura
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
                        title: '¡Factura generada!',
                        text: data.message,
                        icon: 'success'
                    });
                    cargarTabla();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.error || 'Error al facturar');
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
    $('#filtro_cliente').val('');
    $('#filtro_sede').val('');
    cargarTabla();
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}