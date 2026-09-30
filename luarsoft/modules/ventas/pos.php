<?php
/**
 * modules/ventas/pos.php — Reemplaza a "pos.php".
 * ------------------------------------------------------------------
 * Conserva EXACTAMENTE la misma lógica de negocio del sistema
 * original: numeración correlativa por serie (B001/F001), captura
 * de cliente por documento, tabla de productos con autocompletado,
 * cálculo de IGV universal (18%) y registro AJAX vía guardar_venta.
 * Solo cambian las rutas de los endpoints y la envoltura visual
 * (ahora integrada al layout general del sistema).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$tipo_documento_defecto = "BOLETA";
$serie_defecto = "B001";
$titulo_documento = "BOLETA DE VENTA";
$ruc_empresa = "10419418043";
$siguiente_correlativo = 0;

$sql_ultimo_correlativo = "
    SELECT MAX(CAST(SUBSTRING_INDEX(numero_documento, ' - ', -1) AS UNSIGNED)) as max_num
    FROM ventas
    WHERE serie_documento = '{$serie_defecto}'
";
if ($resultado_num = mysqli_query($conexion, $sql_ultimo_correlativo)) {
    $row = mysqli_fetch_assoc($resultado_num);
    if ($row && $row['max_num'] !== null) {
        $siguiente_correlativo = intval($row['max_num']);
    }
}
$siguiente_numero_documento = $serie_defecto . " - " . str_pad($siguiente_correlativo + 1, 5, "0", STR_PAD_LEFT);

// Mismo cálculo mostrado arriba, pero para Factura (F001). Antes, al
// cambiar de pestaña Boleta <-> Factura el número en pantalla solo
// intercambiaba el prefijo y se quedaba con el número de la otra serie
// (ej. mostraba "F001 - 00017" aunque solo hubiera 5 facturas reales).
// Esto era únicamente visual: el número que de verdad se guarda siempre
// se calcula en el servidor al momento de guardar, así que ninguna
// factura ni boleta quedó mal numerada por este detalle de pantalla.
$siguiente_correlativo_factura = 0;
$sql_ultimo_correlativo_factura = "
    SELECT MAX(CAST(SUBSTRING_INDEX(numero_documento, ' - ', -1) AS UNSIGNED)) as max_num
    FROM ventas
    WHERE serie_documento = 'F001'
";
if ($resultado_num_f = mysqli_query($conexion, $sql_ultimo_correlativo_factura)) {
    $row_f = mysqli_fetch_assoc($resultado_num_f);
    if ($row_f && $row_f['max_num'] !== null) {
        $siguiente_correlativo_factura = intval($row_f['max_num']);
    }
}
$siguiente_numero_documento_factura = "F001 - " . str_pad($siguiente_correlativo_factura + 1, 5, "0", STR_PAD_LEFT);

$nombre_defecto = "Público General";
$documento_defecto = "99999999";

$page_title = 'Punto de Venta';
$page_subtitle = 'POS · Boletas y Facturas';
$active_menu = 'pos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<style>
    :root {
        --pos-accent: var(--accent);
        --pos-accent-dark: var(--brand-700);
    }
    .boleta-box {
        border: 1px solid var(--n-200);
        padding: 0;
        background: var(--n-0);
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    .boleta-header { display: flex; justify-content: space-between; align-items: flex-start; background: linear-gradient(120deg, var(--pos-accent-dark), var(--pos-accent)); color: white; padding: 20px 24px; }
    .boleta-body { padding: 22px 24px; }
    .empresa-info h4 img { height: 46px; margin-right: 10px; vertical-align: middle; border-radius: 8px; }
    .documento-info { text-align: right; min-width: 250px; padding-left: 15px; border-left: 2px solid rgba(255,255,255,.4); }
    .documento-info .ruc { font-size: 1rem; font-weight: bold; background: rgba(0,0,0,.2); padding: 3px 10px; border-radius: 30px; margin-bottom: 6px; display:inline-block; }
    .documento-info .titulo { font-size: 1.15rem; font-weight: bold; padding: 6px 0; }
    .documento-info .numero { font-size: 1.4rem; font-weight: 800; background: var(--n-0); color: var(--pos-accent); padding: 6px 10px; margin-top: 5px; border-radius: 6px; }
    .info-cliente-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; margin-bottom: 15px; border-bottom: 1px dashed var(--n-300); padding-bottom: 14px; }
    .info-cliente-item { display: flex; align-items: center; gap: 10px; }
    .info-cliente-item label { font-weight: bold; white-space: nowrap; min-width: 80px; margin-bottom:0; }
    .info-cliente-item input[readonly] { border: none; border-bottom: 1px dashed var(--n-400); background: transparent; color: var(--n-800); padding: 2px 0; }
    #tablaProductos thead tr th { background: var(--brand-800); color: #fff; text-transform: uppercase; font-size: 0.78rem; }
    .autocomplete-list-container { position: relative; }
    .autocomplete-list { position: absolute; z-index: 1000; width: 100%; max-height: 220px; overflow-y: auto; border: 1px solid var(--n-200); background: var(--n-0); box-shadow: var(--shadow-md); list-style: none; padding: 0; margin-top: 2px; border-radius: var(--radius-sm); }
    .autocomplete-item { padding: 9px 12px; cursor: pointer; border-bottom: 1px solid var(--n-100); font-size: 0.85rem; color: var(--n-800); }
    .autocomplete-item:hover, .autocomplete-item.active { background: var(--n-50); font-weight: 600; }
    .totales-container { display: flex; justify-content: flex-end; align-items: flex-end; margin-top: 20px; }
    .totales-right { width: 300px; }
    .table-totales th { background: var(--n-50); text-align: right; }
    .total-final-row { background: var(--pos-accent) !important; color: white; font-weight: bold; font-size: 1.05rem; }
    .total-final-row th, .total-final-row td { color: white !important; }
    .hidden-by-js { display: none !important; }
    .btn-add { border: 1.5px dashed var(--n-300); background: var(--n-50); color: var(--n-600); font-weight: 600; }
    .btn-add:hover { background: var(--n-100); }

    /* ================================================================
       RESPONSIVE — el POS es la pantalla que más se usa desde un
       celular en el mostrador, así que el encabezado del documento y
       los totales pasan de dos columnas a una sola columna apilada.
       ================================================================ */
    @media (max-width: 768px) {
        .boleta-header { flex-direction: column; gap: 16px; padding: 16px 18px; }
        .documento-info { min-width: 0; width: 100%; text-align: left; border-left: none; padding-left: 0; }
        .documento-info .mb-2.text-center { text-align: left !important; }
        .boleta-body { padding: 16px 18px; }
        .info-cliente-grid { grid-template-columns: 1fr; }
        .totales-container { flex-direction: column; align-items: stretch; }
        .totales-container .row.g-2 { justify-content: flex-start !important; }
        .totales-container select#metodoPago,
        .totales-container input[type="text"].form-control.d-inline-block { width: 100% !important; }
        .totales-right { width: 100%; margin-top: 14px; }
        .text-center.mt-3 .btn { display: block; width: 100%; margin: 6px 0 !important; }
    }
    @media (max-width: 480px) {
        .boleta-box { border-radius: var(--radius-md); }
        .empresa-info h4 { font-size: 1rem; }
        .empresa-info p { font-size: 0.78rem !important; }
        .documento-info .numero { font-size: 1.2rem; }
    }
</style>

<div class="page-heading">
    <h1><i class="bi bi-cash-stack"></i> Punto de Venta (POS)</h1>
    <p>Registra boletas y facturas electrónicas con control de stock en tiempo real.</p>
</div>

<form method="POST" action="#" id="formBoleta">
    <input type="hidden" name="tipo_documento" id="tipoDocumentoInput" value="<?= $tipo_documento_defecto ?>">
    <input type="hidden" name="serie_documento" id="serieDocumentoInput" value="<?= $serie_defecto ?>">

    <div class="boleta-box">
        <div class="boleta-header">
            <div class="empresa-info">
                <h4><img src="<?= url('assets/img/eros.jpg') ?>" alt="Logo Eros"> EROS TECNOLOGÍA</h4>
                <p style="max-width:420px; font-size:.85rem; opacity:.9; margin:0;">
                    De: Jose Luis Fernández Montes<br>
                    VENTA Y REPARACIÓN DE PRODUCTOS TECNOLÓGICOS, COMPUTADORAS Y ACCESORIOS<br>
                    Jirón progreso con Av. La Mar, Imperial | CEL: 949092352
                </p>
            </div>
            <div class="documento-info">
                <div class="mb-2 text-center">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="tipoDocumentoRadio" id="tipoBoletaRadio" value="BOLETA" checked>
                        <label class="btn btn-sm btn-outline-light" for="tipoBoletaRadio">Boleta</label>
                        <input type="radio" class="btn-check" name="tipoDocumentoRadio" id="tipoFacturaRadio" value="FACTURA">
                        <label class="btn btn-sm btn-outline-light" for="tipoFacturaRadio">Factura</label>
                    </div>
                </div>
                <div class="ruc"><?= "R.U.C. " . $ruc_empresa ?></div>
                <div class="titulo" id="tituloDocumento"><?= $titulo_documento ?></div>
                <div class="numero" id="numeroDocumento"><?= $siguiente_numero_documento ?></div>
            </div>
        </div>

        <div class="boleta-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="font-weight-bold">Buscar Cliente por Documento (DNI/RUC):</label>
                    <div class="input-group">
                        <input type="text" id="documentoBusqueda" class="form-control" placeholder="Escriba DNI o RUC">
                        <button class="btn btn-primary" type="button" id="btnBuscarCliente"><i class="bi bi-search"></i> Buscar</button>
                    </div>
                    <small id="estadoBusqueda" class="form-text text-muted">Cliente: <?= $nombre_defecto ?></small>
                </div>
                <div class="col-md-6"></div>
            </div>

            <div class="info-cliente-grid">
                <div class="info-cliente-item hidden-by-js" id="rucClienteContainer">
                    <label for="rucCliente">RUC:</label>
                    <input id="rucCliente" name="rucCliente" class="form-control w-100" value="">
                </div>
                <div class="info-cliente-item">
                    <label for="nombre">Señor(es):</label>
                    <input id="nombre" name="nombre" class="form-control w-100" value="<?= $nombre_defecto ?>" required>
                </div>
                <div class="info-cliente-item" id="documentoClienteContainer">
                    <label for="documento" id="labelDocumento">Doc. Ident:</label>
                    <input id="documento" name="documento" class="form-control w-100" value="<?= $documento_defecto ?>" required>
                </div>
                <div class="info-cliente-item">
                    <label for="direccion">Dirección:</label>
                    <input id="direccion" name="direccion" class="form-control w-100" value="">
                </div>
                <div class="info-cliente-item">
                    <label for="fecha">Fecha:</label>
                    <input type="text" id="fecha" class="form-control w-100" value="<?= date('d / m / Y') ?>" readonly>
                </div>
            </div>

            <div class="scan-bar mb-2">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="inputEscanerPos" class="form-control" placeholder="Escanea un código de barras (cámara, lector USB) o teclealo y presiona Enter" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" onclick="ERPEscaner.abrir(procesarCodigoEscaneado)" title="Escanear con cámara">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table table-bordered mt-3" id="tablaProductos">
                <thead>
                    <tr>
                        <th style="width:10%">CANT.</th>
                        <th style="width:50%">DESCRIPCIÓN</th>
                        <th style="width:15%">P. UNIT.</th>
                        <th style="width:15%">IMPORTE</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="number" min="1" value="1" class="form-control cantidad">
                            <input type="hidden" name="producto_codigo[]" class="codigo_producto" value="">
                        </td>
                        <td>
                            <div class="autocomplete-list-container">
                                <input class="form-control producto" placeholder="Escriba el producto (↑↓ para navegar, Enter/Tab para elegir)" autocomplete="off">
                            </div>
                            <small class="text-muted stock-hint"></small>
                        </td>
                        <td><input type="number" min="0" step="0.01" value="0.00" class="form-control precio"></td>
                        <td><input class="form-control total" readonly value="0.00"></td>
                        <td class="text-center"><button class="btn btn-danger btn-sm eliminar" type="button">X</button></td>
                    </tr>
                </tbody>
            </table>
            </div>

            <button class="btn btn-add" id="agregarProducto" type="button">+ Añadir Producto</button>

            <div class="totales-container">
                <div style="flex-grow:1; padding-top:20px;">
                    <div class="row g-2 justify-content-center align-items-end">
                        <div class="col-auto text-center">
                            <label class="font-weight-bold mb-1 d-block"><i class="bi bi-wallet2"></i> Método de Pago</label>
                            <select id="metodoPago" class="form-select d-inline-block" style="width:190px;">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Yape / Plin">Yape / Plin</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-auto text-center">
                            <label class="font-weight-bold mb-1 d-block">CANCELADO</label>
                            <input type="text" class="form-control d-inline-block" style="width:200px;">
                        </div>
                    </div>

                    <div id="panelYape" class="hidden-by-js" style="margin-top:18px; padding:16px; border:1.5px dashed var(--pos-accent); border-radius:12px; background:var(--n-50); max-width:300px; margin-left:auto; margin-right:auto; text-align:center;">
                        <img src="<?= url('assets/img/yape_qr.png') ?>" alt="QR de Yape" style="width:150px; height:auto; border-radius:8px; box-shadow: var(--shadow-sm);">
                        <div style="font-size:1.25rem; font-weight:800; color:var(--pos-accent); margin-top:10px;">Cobrar S/ <span id="montoYape">0.00</span></div>
                        <div class="mt-2">
                            <input type="text" id="inputOperacionYape" class="form-control form-control-sm text-center" placeholder="N° de operación Yape (opcional)">
                        </div>
                    </div>
                </div>
                <div class="totales-right">
                    <table class="table table-bordered table-totales">
                        <tr id="subtotalRow" class="hidden-by-js"><th id="labelSubTotal">SUBTOTAL S/</th><td id="subTotal">0.00</td></tr>
                        <tr id="igvRow"><th id="labelIgv">IMPUESTOS S/</th><td id="igv">0.00</td></tr>
                        <tr class="total-final-row"><th>TOTAL S/</th><td id="totalFinal">0.00</td></tr>
                    </table>
                </div>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-primary btn-lg mx-2" type="button" onclick="registrarVenta()"><i class="bi bi-check-circle"></i> Registrar Venta</button>
                <button class="btn btn-secondary btn-lg mx-2" type="button" onclick="prepararEImprimir(last_id_venta)"><i class="bi bi-printer"></i> Imprimir Documento</button>
                <button class="btn btn-outline-secondary btn-lg mx-2" type="button" onclick="cancelarVenta()"><i class="bi bi-x-circle"></i> Cancelar / Limpiar Venta</button>
            </div>
        </div>
    </div>
</form>

<script>
// ====================================================================
// VARIABLE GLOBAL CLAVE
// ====================================================================
let last_id_venta = null;
const IGV_RATE = 0.18;
let currentDocumentType = 'BOLETA';

// Próximo número de cada serie, calculado por separado en el servidor
// (uno para Boleta, otro para Factura) para que al cambiar de pestaña
// cada tipo de documento muestre SU propio correlativo, no el de la otra.
let proximoNumeroBoleta = "<?= $siguiente_numero_documento ?>";
let proximoNumeroFactura = "<?= $siguiente_numero_documento_factura ?>";

const ENDPOINT_BUSCAR_CLIENTE = "<?= url('modules/clientes/buscar_doc.php') ?>";
const ENDPOINT_BUSCAR_PRODUCTOS = "<?= url('modules/productos/buscar_autocomplete.php') ?>";
const ENDPOINT_BUSCAR_POR_CODIGO = "<?= url('modules/productos/buscar_por_codigo_barras.php') ?>";
const ENDPOINT_GUARDAR_VENTA = "<?= url('modules/ventas/guardar_venta.php') ?>";
const ENDPOINT_TICKET = "<?= url('modules/ventas/ticket.php') ?>";

// ====================================================================
// PERSISTENCIA TEMPORAL DEL CARRITO (localStorage)
// ====================================================================
const CARRITO_STORAGE_KEY = 'luarsoft_pos_carrito_temporal';

function guardarCarritoLocal() {
    try {
        const filas = Array.from(document.querySelectorAll("#tablaProductos tbody tr")).map(fila => ({
            cantidad: fila.querySelector(".cantidad")?.value || '1',
            producto: fila.querySelector(".producto")?.value || '',
            precio: fila.querySelector(".precio")?.value || '0.00',
            codigo: fila.querySelector(".codigo_producto")?.value || '',
            stock: fila.querySelector(".stock-hint")?.textContent?.replace(/[^0-9]/g, '') || ''
        })).filter(f => f.producto !== '' || f.codigo !== '');

        const estado = {
            tipoDocumento: document.getElementById("tipoDocumentoInput")?.value || 'BOLETA',
            nombre: document.getElementById("nombre")?.value || '',
            documento: document.getElementById("documento")?.value || '',
            direccion: document.getElementById("direccion")?.value || '',
            rucCliente: document.getElementById("rucCliente")?.value || '',
            productos: filas,
            guardadoEn: Date.now()
        };
        localStorage.setItem(CARRITO_STORAGE_KEY, JSON.stringify(estado));
    } catch (e) {
        console.warn('No se pudo guardar el carrito temporal:', e);
    }
}

function restaurarCarritoLocal() {
    let estado;
    try {
        const raw = localStorage.getItem(CARRITO_STORAGE_KEY);
        if (!raw) return false;
        estado = JSON.parse(raw);
    } catch (e) {
        return false;
    }
    if (!estado || (!estado.productos?.length && (!estado.nombre || estado.nombre === 'Público General'))) {
        return false;
    }

    const tipo = estado.tipoDocumento === 'FACTURA' ? 'FACTURA' : 'BOLETA';
    const radio = document.getElementById(tipo === 'FACTURA' ? 'tipoFacturaRadio' : 'tipoBoletaRadio');
    radio.checked = true;

    if (estado.nombre) document.getElementById("nombre").value = estado.nombre;
    if (estado.documento) document.getElementById("documento").value = estado.documento;
    document.getElementById("direccion").value = estado.direccion || '';
    document.getElementById("rucCliente").value = estado.rucCliente || '';

    const tbody = document.querySelector("#tablaProductos tbody");
    tbody.innerHTML = '';
    if (estado.productos && estado.productos.length > 0) {
        estado.productos.forEach(p => agregarFilaProducto(p));
    } else {
        agregarFilaProducto();
    }

    handleDocumentTypeChange({ target: radio });
    ERP.toast('info', 'Se restauró la venta que tenías en curso en el POS.');
    return true;
}

function cancelarVenta() {
    ERP.confirmarEliminar('Se perderán el cliente y los productos agregados en esta venta no registrada.', {
        titulo: '¿Cancelar la venta actual?',
        textoConfirmar: 'Sí, cancelar venta'
    }).then(ok => {
        if (!ok) return;

        localStorage.removeItem(CARRITO_STORAGE_KEY);
        last_id_venta = null;

        document.getElementById("nombre").value = "Público General";
        document.getElementById("documento").value = "99999999";
        document.getElementById("direccion").value = "";
        document.getElementById("rucCliente").value = "";
        document.getElementById("documentoBusqueda").value = "";

        const tbody = document.querySelector("#tablaProductos tbody");
        tbody.innerHTML = '';
        agregarFilaProducto();

        document.getElementById('tipoBoletaRadio').checked = true;
        handleDocumentTypeChange({ target: document.getElementById('tipoBoletaRadio') });

        document.getElementById("metodoPago").value = "Efectivo";
        document.getElementById("inputOperacionYape").value = "";
        document.getElementById("panelYape").classList.add("hidden-by-js");

        ERP.toast('success', 'Venta cancelada. El POS quedó limpio para una nueva venta.');
    });
}

// ====================================================================
// INICIALIZACIÓN
// ====================================================================
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="tipoDocumentoRadio"]').forEach(radio => {
        radio.addEventListener('change', handleDocumentTypeChange);
    });

    const restaurado = restaurarCarritoLocal();
    if (!restaurado) {
        handleDocumentTypeChange({target: document.getElementById('tipoBoletaRadio')});
    }

    // Guarda el carrito también ante cambios directos en los datos del cliente
    ['nombre', 'documento', 'direccion', 'rucCliente'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', guardarCarritoLocal);
    });
});

// ====================================================================
// LÓGICA DE DOCUMENTO (BOLETA/FACTURA)
// ====================================================================
function handleDocumentTypeChange(event) {
    const tipo = event.target.value;
    currentDocumentType = tipo;

    const tituloDoc = document.getElementById('tituloDocumento');
    const tipoDocInput = document.getElementById('tipoDocumentoInput');
    const serieDocInput = document.getElementById('serieDocumentoInput');
    const numDoc = document.getElementById('numeroDocumento');
    const rucContainer = document.getElementById('rucClienteContainer');
    const docInput = document.getElementById('documento');
    const labelDoc = document.getElementById('labelDocumento');
    const subtotalRow = document.getElementById('subtotalRow');
    const labelIgv = document.getElementById('labelIgv');

    let newSerie = '';

    if (tipo === 'FACTURA') {
        tituloDoc.innerText = "FACTURA ELECTRÓNICA";
        newSerie = 'F001';
        rucContainer.classList.remove('hidden-by-js');
        labelDoc.innerText = 'DNI (opcional):';
        subtotalRow.classList.remove('hidden-by-js');
        labelIgv.innerText = `IGV (${(IGV_RATE * 100).toFixed(0)}%) S/`;
        document.getElementById('labelSubTotal').innerText = 'VALOR VENTA S/';
        if (document.getElementById("nombre").value === "Público General") {
            docInput.value = '';
        }
    } else {
        tituloDoc.innerText = "BOLETA DE VENTA";
        newSerie = 'B001';
        rucContainer.classList.add('hidden-by-js');
        document.getElementById('rucCliente').value = '';
        if (document.getElementById("nombre").value === "Cliente Anónimo" || document.getElementById("nombre").value === "Público General") {
            document.getElementById("nombre").value = "Público General";
            docInput.value = '99999999';
        }
        labelDoc.innerText = 'Doc. Ident:';
        subtotalRow.classList.add('hidden-by-js');
        labelIgv.innerText = 'IMPUESTOS S/';
    }

    tipoDocInput.value = tipo;
    serieDocInput.value = newSerie;

    numDoc.innerText = (tipo === 'FACTURA') ? proximoNumeroFactura : proximoNumeroBoleta;

    recalcular();

    document.getElementById("documentoBusqueda").value = "";
    document.getElementById("estadoBusqueda").textContent = `Cliente: ${document.getElementById("nombre").value}. Ingrese ${tipo === 'FACTURA' ? 'RUC' : 'DNI'} para buscar.`;
}

// ====================================================================
// FUNCIÓN IMPRIMIR
// ====================================================================
function prepararEImprimir(idVenta) {
    if (!idVenta) {
        ERP.toast('warning', 'Primero debe registrar una venta exitosa.');
        return;
    }
    ERP.verDocumento(`${ENDPOINT_TICKET}?id=${idVenta}`, 'Ticket de Venta', window.location.pathname);
}

// ====================================================================
// BÚSQUEDA DE CLIENTE POR DOCUMENTO (AJAX)
// ====================================================================
document.getElementById("btnBuscarCliente").addEventListener("click", buscarCliente);
document.getElementById("documentoBusqueda").addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();
        buscarCliente();
    }
});

async function buscarCliente() {
    const documento = document.getElementById("documentoBusqueda").value.trim();
    const estadoBusqueda = document.getElementById("estadoBusqueda");

    if (documento.length < 1) {
        estadoBusqueda.textContent = "Ingrese el DNI/RUC a buscar.";
        estadoBusqueda.className = "form-text text-danger";
        return;
    }

    estadoBusqueda.textContent = "Buscando...";
    estadoBusqueda.className = "form-text text-warning";

    try {
        const response = await fetch(`${ENDPOINT_BUSCAR_CLIENTE}?documento=${documento}`);
        const cliente = await response.json();

        if (cliente.error) {
            document.getElementById("nombre").value = "Cliente Anónimo";
            document.getElementById("direccion").value = "";
            document.getElementById("rucCliente").value = "";

            if (currentDocumentType === 'FACTURA' && documento.length === 11) {
                document.getElementById("rucCliente").value = documento;
                document.getElementById("documento").value = '';
            } else {
                document.getElementById("documento").value = documento;
                document.getElementById("rucCliente").value = '';
            }

            estadoBusqueda.textContent = `Cliente no encontrado. Usando 'Cliente Anónimo' con Doc/RUC ${documento}.`;
            estadoBusqueda.className = "form-text text-danger";
        } else {
            document.getElementById("nombre").value = cliente.nombre;
            document.getElementById("direccion").value = cliente.direccion;

            const docEncontrado = cliente.numero_documento;

            if (docEncontrado.length === 11) {
                document.getElementById("rucCliente").value = docEncontrado;
                document.getElementById("documento").value = '';
                document.getElementById("tipoFacturaRadio").checked = true;
                handleDocumentTypeChange({target: document.getElementById('tipoFacturaRadio')});
            } else if (docEncontrado.length === 8) {
                if (currentDocumentType === 'FACTURA') {
                    // Bug corregido: si ya estamos en la pestaña Factura y el
                    // documento encontrado es un DNI (8 dígitos), NO se debe
                    // saltar a la pestaña Boleta. Se conserva Factura y el DNI
                    // se carga como dato opcional del cliente (campo "DNI
                    // (opcional)"), sin tocar el RUC ya ingresado.
                    document.getElementById("documento").value = docEncontrado;
                } else {
                    document.getElementById("documento").value = docEncontrado;
                    document.getElementById("rucCliente").value = '';
                    document.getElementById("tipoBoletaRadio").checked = true;
                    handleDocumentTypeChange({target: document.getElementById('tipoBoletaRadio')});
                }
            } else {
                document.getElementById("documento").value = docEncontrado;
                document.getElementById("rucCliente").value = '';
            }

            estadoBusqueda.textContent = `Cliente: ${cliente.nombre} encontrado.`;
            estadoBusqueda.className = "form-text text-success";
        }
        guardarCarritoLocal();
    } catch (error) {
        console.error("Error al buscar cliente:", error);
        estadoBusqueda.textContent = "Error de conexión al buscar cliente.";
        estadoBusqueda.className = "form-text text-danger";
    }
}

// ====================================================================
// AUTOCOMPLETADO DE PRODUCTOS (AJAX)
// ====================================================================
const inputTimeout = {};

document.addEventListener("input", function (e) {
    if (e.target.classList.contains("producto")) {
        const currentRow = e.target.closest('tr');
        clearTimeout(inputTimeout[currentRow]);

        inputTimeout[currentRow] = setTimeout(() => {
            const query = e.target.value.trim();
            if (query.length > 2) {
                searchProducts(query, e.target, currentRow);
            } else {
                hideAutocomplete(currentRow);
            }
        }, 300);
    }

    if (e.target.classList.contains("cantidad") || e.target.classList.contains("precio")) {
        recalcular();
    }
});

async function searchProducts(query, inputElement, rowElement) {
    const response = await fetch(`${ENDPOINT_BUSCAR_PRODUCTOS}?query=${encodeURIComponent(query)}`);
    const results = await response.json();
    displayAutocompleteResults(results, inputElement, rowElement);
}

function displayAutocompleteResults(results, inputElement, rowElement) {
    let listContainer = inputElement.closest('.autocomplete-list-container');
    listContainer.querySelector('.autocomplete-list')?.remove();

    if (results.length > 0) {
        const ul = document.createElement('ul');
        ul.className = 'autocomplete-list';

        results.forEach((product, idx) => {
            const li = document.createElement('li');
            li.className = 'autocomplete-item';
            li.innerHTML = `${product.descripcion} <span>(Cód: ${product.codigo} | Stock: ${product.stock})</span>`;

            li.dataset.codigo = product.codigo;
            li.dataset.precio = product.precio;
            li.dataset.descripcion = product.descripcion;
            li.dataset.stock = product.stock;
            if (idx === 0) li.classList.add('active');

            li.addEventListener('click', (e) => {
                e.stopPropagation();
                selectProduct(li, rowElement, inputElement);
            });

            ul.appendChild(li);
        });
        listContainer.appendChild(ul);
    } else {
        const ul = document.createElement('ul');
        ul.className = 'autocomplete-list';
        ul.innerHTML = '<li class="autocomplete-item" style="cursor:default;background:none;">No hay coincidencias.</li>';
        listContainer.appendChild(ul);
    }
}

function hideAutocomplete() {
    document.querySelectorAll('.autocomplete-list').forEach(list => list.remove());
}

function selectProduct(liElement, rowElement, inputElement) {
    const codigo = liElement.dataset.codigo;
    const precio = liElement.dataset.precio;
    const descripcion = liElement.dataset.descripcion || liElement.textContent.split('(')[0].trim();
    const stock = liElement.dataset.stock;

    rowElement.querySelector(".producto").value = descripcion;
    rowElement.querySelector(".precio").value = parseFloat(precio).toFixed(2);
    rowElement.querySelector(".codigo_producto").value = codigo;
    const hint = rowElement.querySelector(".stock-hint");
    if (hint) hint.textContent = stock !== undefined ? `Stock disponible: ${stock}` : '';

    hideAutocomplete();
    recalcular();
    guardarCarritoLocal();
    rowElement.querySelector(".cantidad").focus();
    rowElement.querySelector(".cantidad").select();
}

/* ---------------- Navegación por teclado del autocompletado (↑ ↓ Tab Enter) ---------------- */
document.addEventListener("keydown", function (e) {
    if (!e.target.classList || !e.target.classList.contains("producto")) return;

    const container = e.target.closest('.autocomplete-list-container');
    const list = container?.querySelector('.autocomplete-list');
    if (!list) return;

    const items = Array.from(list.querySelectorAll('.autocomplete-item[data-codigo]'));
    if (items.length === 0) return;

    let activeIndex = items.findIndex(li => li.classList.contains('active'));

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = (activeIndex + 1) % items.length;
        marcarActivoAutocomplete(items, activeIndex);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
        marcarActivoAutocomplete(items, activeIndex);
    } else if (e.key === 'Tab' || e.key === 'Enter') {
        const objetivo = activeIndex >= 0 ? items[activeIndex] : items[0];
        if (objetivo) {
            e.preventDefault();
            const rowElement = e.target.closest('tr');
            selectProduct(objetivo, rowElement, e.target);
        }
    } else if (e.key === 'Escape') {
        hideAutocomplete();
    }
});

function marcarActivoAutocomplete(items, index) {
    items.forEach((li, i) => li.classList.toggle('active', i === index));
    items[index].scrollIntoView({ block: 'nearest' });
}

document.addEventListener("click", function (e) {
    if (e.target.classList.contains("eliminar")) {
        let filas = document.querySelectorAll("#tablaProductos tbody tr");
        if (filas.length > 1) {
            e.target.closest('tr').remove();
            recalcular();
            guardarCarritoLocal();
        } else {
            ERP.toast('warning', 'Debe haber al menos un producto.');
        }
    }
    if (!e.target.classList.contains('producto')) {
        hideAutocomplete();
    }
});

// ====================================================================
// FILAS Y CÁLCULOS (IGV UNIVERSAL)
// ====================================================================
function recalcular() {
    let filas = document.querySelectorAll("#tablaProductos tbody tr");
    let totalBruto = 0;

    filas.forEach(fila => {
        let cant = parseFloat(fila.querySelector(".cantidad").value) || 0;
        let precio = parseFloat(fila.querySelector(".precio").value) || 0;
        let total = cant * precio;
        fila.querySelector(".total").value = total.toFixed(2);
        totalBruto += total;
    });

    let subtotal = totalBruto / (1 + IGV_RATE);
    let igv = totalBruto - subtotal;
    let totalFinal = totalBruto;

    document.getElementById("subTotal").innerText = subtotal.toFixed(2);
    document.getElementById("igv").innerText = igv.toFixed(2);
    document.getElementById("totalFinal").innerText = totalFinal.toFixed(2);
    document.getElementById("montoYape").innerText = totalFinal.toFixed(2);

    guardarCarritoLocal();
}

// ====================================================================
// PANEL DE PAGO CON YAPE: se muestra el QR + monto a cobrar cuando el
// cajero elige "Yape / Plin" como método de pago.
// ====================================================================
document.getElementById("metodoPago").addEventListener("change", function () {
    const panelYape = document.getElementById("panelYape");
    if (this.value.includes("Yape")) {
        panelYape.classList.remove("hidden-by-js");
        document.getElementById("montoYape").innerText = document.getElementById("totalFinal").innerText;
    } else {
        panelYape.classList.add("hidden-by-js");
    }
});

document.getElementById("agregarProducto").addEventListener("click", function () {
    agregarFilaProducto();
    recalcular();
    guardarCarritoLocal();
});

function agregarFilaProducto(datos) {
    let tabla = document.querySelector("#tablaProductos tbody");
    let fila = document.createElement("tr");

    fila.innerHTML = `
        <td>
            <input type="number" min="1" value="1" class="form-control cantidad">
            <input type="hidden" name="producto_codigo[]" class="codigo_producto" value="">
        </td>
        <td>
            <div class="autocomplete-list-container">
                <input class="form-control producto" placeholder="Escriba el producto (↑↓ para navegar, Enter/Tab para elegir)" autocomplete="off">
            </div>
            <small class="text-muted stock-hint"></small>
        </td>
        <td><input type="number" min="0" step="0.01" value="0.00" class="form-control precio"></td>
        <td><input class="form-control total" readonly value="0.00"></td>
        <td class="text-center"><button class="btn btn-danger btn-sm eliminar" type="button">X</button></td>
    `;

    tabla.appendChild(fila);

    if (datos) {
        fila.querySelector('.cantidad').value = datos.cantidad ?? 1;
        fila.querySelector('.producto').value = datos.producto ?? '';
        fila.querySelector('.precio').value = datos.precio ?? '0.00';
        fila.querySelector('.codigo_producto').value = datos.codigo ?? '';
        if (datos.stock !== undefined && datos.stock !== null && datos.stock !== '') {
            fila.querySelector('.stock-hint').textContent = `Stock disponible: ${datos.stock}`;
        }
    } else {
        fila.querySelector('.producto').focus();
    }
    return fila;
}

// ====================================================================
// ESCANEO DE CÓDIGO DE BARRAS (cámara o lector físico USB)
// ====================================================================
async function procesarCodigoEscaneado(codigo) {
    codigo = (codigo || '').trim();
    if (codigo === '') return;

    const inputEscaner = document.getElementById('inputEscanerPos');
    try {
        const res = await fetch(`${ENDPOINT_BUSCAR_POR_CODIGO}?codigo=${encodeURIComponent(codigo)}`);
        const data = await res.json();

        if (!data.success) {
            ERP.toast('error', data.message || 'Producto no encontrado.');
            if (inputEscaner) { inputEscaner.value = ''; inputEscaner.focus(); }
            return;
        }

        // Si el producto ya está en el carrito, se suma 1 a su cantidad en
        // vez de duplicar la fila (igual que un POS físico real).
        const filas = document.querySelectorAll('#tablaProductos tbody tr');
        let filaExistente = null;
        let filaVacia = null;
        filas.forEach(function (fila) {
            const codigoFila = fila.querySelector('.codigo_producto')?.value;
            if (codigoFila === data.codigo) { filaExistente = fila; }
            else if (!codigoFila && !filaVacia) { filaVacia = fila; }
        });

        if (filaExistente) {
            const cantidadInput = filaExistente.querySelector('.cantidad');
            cantidadInput.value = (parseInt(cantidadInput.value, 10) || 0) + 1;
        } else if (filaVacia) {
            filaVacia.querySelector('.producto').value = data.descripcion;
            filaVacia.querySelector('.precio').value = data.precio.toFixed(2);
            filaVacia.querySelector('.codigo_producto').value = data.codigo;
            filaVacia.querySelector('.cantidad').value = 1;
            const hint = filaVacia.querySelector('.stock-hint');
            if (hint) hint.textContent = `Stock disponible: ${data.stock}`;
        } else {
            agregarFilaProducto({ producto: data.descripcion, precio: data.precio.toFixed(2), codigo: data.codigo, cantidad: 1, stock: data.stock });
        }

        recalcular();
        guardarCarritoLocal();
        ERP.toast('success', `${data.descripcion} agregado (S/ ${data.precio.toFixed(2)}).`);
    } catch (e) {
        ERP.toast('error', 'Error de conexión al buscar el producto escaneado.');
    }

    if (inputEscaner) { inputEscaner.value = ''; inputEscaner.focus(); }
}

document.getElementById('inputEscanerPos')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        procesarCodigoEscaneado(this.value);
    }
});

// ====================================================================
// REGISTRAR VENTA
// ====================================================================
function registrarVenta() {
    let tipoDocumento = document.getElementById("tipoDocumentoInput").value;
    let serieDocumento = document.getElementById("serieDocumentoInput").value;
    let nombreCliente = document.getElementById("nombre").value;
    let documentoCliente = document.getElementById("documento").value;
    let direccionCliente = document.getElementById("direccion").value;
    let rucCliente = document.getElementById("rucCliente").value;
    let numeroDocumento = document.getElementById("numeroDocumento").innerText;

    recalcular();

    let subtotalDisplay = document.getElementById("subTotal").innerText;
    let igvDisplay = document.getElementById("igv").innerText;
    let totalDisplay = document.getElementById("totalFinal").innerText;

    if (tipoDocumento === 'FACTURA') {
        const docPrincipal = rucCliente.trim();
        if (docPrincipal.length !== 11) {
            ERP.toast('warning', "Para una FACTURA, el RUC debe tener 11 dígitos.");
            return;
        }
        documentoCliente = rucCliente;
    } else {
        const docPrincipal = documentoCliente.trim();
        if (nombreCliente !== "Público General" && docPrincipal.length !== 8) {
            if (docPrincipal !== '99999999' && docPrincipal.length > 0 && docPrincipal.length < 8) {
                ERP.toast('warning', "Para una BOLETA, el DNI generalmente tiene 8 dígitos. Verifique o use 'Público General'.");
            }
        }
    }

    let productos = [];
    let filas = document.querySelectorAll("#tablaProductos tbody tr");
    let totalItems = 0;

    for (let i = 0; i < filas.length; i++) {
        let fila = filas[i];
        let cant = parseFloat(fila.querySelector(".cantidad").value);
        let precio = parseFloat(fila.querySelector(".precio").value);
        let descripcion = fila.querySelector(".producto").value.trim();
        let codigo = fila.querySelector(".codigo_producto").value.trim();

        if (cant > 0 && precio > 0 && descripcion !== "") {
            let valor_unitario = precio / (1 + IGV_RATE);
            productos.push({
                codigo: codigo,
                descripcion: descripcion,
                cantidad: cant,
                precio_unitario: precio.toFixed(2),
                valor_unitario: valor_unitario.toFixed(2),
                importe: (cant * precio).toFixed(2)
            });
            totalItems++;
        }
    }

    if (totalItems === 0) {
        ERP.toast('warning', "Debe agregar al menos un producto válido.");
        return;
    }

    let datosVenta = {
        tipo_documento: tipoDocumento,
        serie_documento: serieDocumento,
        numero_documento: numeroDocumento,
        nombre_cliente: nombreCliente,
        documento_cliente: documentoCliente,
        direccion_cliente: direccionCliente,
        total_venta: totalDisplay,
        subtotal_venta: subtotalDisplay,
        igv_venta: igvDisplay,
        metodo_pago: document.getElementById("metodoPago").value,
        referencia_pago: document.getElementById("inputOperacionYape").value.trim(),
        productos: productos
    };

    fetch(ENDPOINT_GUARDAR_VENTA, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(datosVenta)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ERP.toast('success', `Venta registrada con éxito. ${data.message}`);
            last_id_venta = data.id_venta;
            localStorage.removeItem(CARRITO_STORAGE_KEY);

            // Refresca el próximo número EN PANTALLA para el tipo de
            // documento recién usado, con el valor real que calculó el
            // servidor (así no se queda con un número atrasado si se
            // hacen varias ventas del mismo tipo seguidas).
            if (data.nuevo_numero_documento) {
                if (tipoDocumento === 'FACTURA') {
                    proximoNumeroFactura = data.nuevo_numero_documento;
                } else {
                    proximoNumeroBoleta = data.nuevo_numero_documento;
                }
                if (currentDocumentType === tipoDocumento) {
                    document.getElementById('numeroDocumento').innerText = data.nuevo_numero_documento;
                }
            }
        } else {
            ERP.toast('error', `Error al registrar venta: ${data.message || 'Error desconocido'}`);
        }
    })
    .catch(error => {
        console.error('Error en la comunicación con el servidor:', error);
        ERP.toast('error', 'Ocurrió un error de red o servidor al registrar la venta.');
    });
}
</script>

<?php include __DIR__ . "/../../includes/layout_bottom.php"; ?>
