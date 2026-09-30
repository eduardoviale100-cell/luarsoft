<?php
/**
 * modules/reportes/tributario.php
 * ------------------------------------------------------------------
 * NUEVO: Reporte Tributario y Contable — para la sustentación del
 * concurso de innovación. Calcula, con datos reales de tu sistema:
 *
 *   1) IGV por pagar del periodo = IGV de Ventas − IGV de Compras
 *   2) Asiento contable de ventas (partida doble, según el PCGE)
 *   3) Costo de Ventas = precio de compra × cantidad vendida
 *
 * No modifica ninguna tabla ni módulo existente: solo LEE de
 * `ventas`, `compras`, `detalle_venta` y `productos`.
 *
 * NOTA IMPORTANTE (pendiente a propósito, no inventado):
 * La interconexión real con la SUNAT para emitir comprobantes
 * electrónicos requiere un Proveedor de Servicios Electrónicos (PSE)
 * u OSE homologado, certificado digital y credenciales SOL — eso
 * está fuera del alcance de este reporte y depende de la explicación
 * del Ing. Lazarte mencionada en tu guía. Este módulo calcula los
 * montos correctamente; no transmite nada a SUNAT.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

// Periodo por defecto: mes actual (así se paga el IGV: mensual).
$fecha_desde = limpiar($_GET['fecha_desde'] ?? date('Y-m-01'));
$fecha_hasta = limpiar($_GET['fecha_hasta'] ?? date('Y-m-t'));
$fecha_hasta_full = $fecha_hasta . ' 23:59:59';

// ------------------------------------------------------------------
// 1) IGV DE VENTAS (ya calculado por el sistema al emitir cada
//    boleta/factura, sumamos lo del periodo).
// ------------------------------------------------------------------
$stmt = mysqli_prepare($conexion, "SELECT
        COALESCE(SUM(subtotal), 0)    AS subtotal_ventas,
        COALESCE(SUM(igv), 0)         AS igv_ventas,
        COALESCE(SUM(total_venta), 0) AS total_ventas
    FROM ventas
    WHERE fecha BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $fecha_desde, $fecha_hasta_full);
mysqli_stmt_execute($stmt);
$resVentas = mysqli_stmt_get_result($stmt)->fetch_assoc();

// ------------------------------------------------------------------
// 2) IGV DE COMPRAS. La tabla `compras` guarda el total ya con IGV
//    incluido (como llega en la factura del proveedor), así que se
//    extrae el 18% con la fórmula estándar: Total ÷ 1.18 × 0.18.
// ------------------------------------------------------------------
$stmt2 = mysqli_prepare($conexion, "SELECT COALESCE(SUM(total), 0) AS total_compras
    FROM compras WHERE fecha BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt2, "ss", $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt2);
$resCompras = mysqli_stmt_get_result($stmt2)->fetch_assoc();
$totalCompras = (float)$resCompras['total_compras'];
$igvCompras   = round($totalCompras - ($totalCompras / 1.18), 2);
$baseCompras  = round($totalCompras - $igvCompras, 2);

$igvVentas  = (float)$resVentas['igv_ventas'];
$igvPagar   = round($igvVentas - $igvCompras, 2);

// ------------------------------------------------------------------
// 3) COSTO DE VENTAS: por cada línea vendida en el periodo, se busca
//    el precio de compra REAL del producto (no el de venta) y se
//    multiplica por la cantidad vendida.
// ------------------------------------------------------------------
$stmt3 = mysqli_prepare($conexion, "SELECT
        COALESCE(SUM(dv.cantidad * p.precio_compra), 0) AS costo_ventas,
        COALESCE(SUM(dv.importe), 0) AS ingresos_por_producto
    FROM detalle_venta dv
    INNER JOIN ventas v ON v.id_venta = dv.id_venta
    LEFT JOIN productos p ON p.codigo = dv.producto_codigo
    WHERE v.fecha BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt3, "ss", $fecha_desde, $fecha_hasta_full);
mysqli_stmt_execute($stmt3);
$resCosto = mysqli_stmt_get_result($stmt3)->fetch_assoc();

$costoVentas   = (float)$resCosto['costo_ventas'];
$subtotalVenta = (float)$resVentas['subtotal_ventas'];
$utilidadBruta = round($subtotalVenta - $costoVentas, 2);

$page_title = 'Reporte Tributario y Contable';
$page_subtitle = 'Reportes · IGV, asiento de ventas y costo de ventas';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-calculator"></i> Reporte Tributario y Contable</h1>
        <p>IGV por pagar, asiento contable de ventas y costo de ventas del periodo.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir</button>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label><i class="bi bi-calendar-event"></i> Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="<?= h($fecha_desde) ?>">
        </div>
        <div class="col-md-3">
            <label><i class="bi bi-calendar-event"></i> Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?= h($fecha_hasta) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Consultar periodo</button>
        </div>
    </form>
</div>

<!-- 1) LIQUIDACIÓN DE IGV -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-receipt-cutoff"></i> 1. Impuesto General a las Ventas (IGV) — periodo <?= h($fecha_desde) ?> a <?= h($fecha_hasta) ?></div>
    <div class="card-body">
        <table class="table">
            <tbody>
                <tr><td>IGV de Ventas (débito fiscal)</td><td class="text-end"><?= moneda($igvVentas) ?></td></tr>
                <tr><td>(−) IGV de Compras (crédito fiscal)</td><td class="text-end">− <?= moneda($igvCompras) ?></td></tr>
                <tr style="font-weight:700; background: var(--n-50);">
                    <td><?= $igvPagar >= 0 ? 'IGV por pagar este mes' : 'Saldo a favor (crédito fiscal para el próximo mes)' ?></td>
                    <td class="text-end"><?= moneda(abs($igvPagar)) ?></td>
                </tr>
            </tbody>
        </table>
        <p class="text-muted small mb-0">
            Referencia: base imponible de compras <?= moneda($baseCompras) ?> + IGV <?= moneda($igvCompras) ?> = total compras <?= moneda($totalCompras) ?>.
            Este monto se declara mensualmente ante SUNAT (obligatorio si el RUC empieza con "20").
        </p>
    </div>
</div>

<!-- 2) ASIENTO CONTABLE DE VENTAS -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-journal-text"></i> 2. Asiento Contable de Ventas del periodo (Plan Contable General Empresarial)</div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>Cuenta</th><th>Denominación</th><th class="text-end">Debe</th><th class="text-end">Haber</th></tr>
            </thead>
            <tbody>
                <tr><td>12</td><td>Cuentas por Cobrar Comerciales — Terceros</td><td class="text-end"><?= moneda($resVentas['total_ventas']) ?></td><td class="text-end">—</td></tr>
                <tr><td>40</td><td>Tributos, Contraprest. y Aportes por Pagar (IGV)</td><td class="text-end">—</td><td class="text-end"><?= moneda($igvVentas) ?></td></tr>
                <tr><td>70</td><td>Ventas</td><td class="text-end">—</td><td class="text-end"><?= moneda($subtotalVenta) ?></td></tr>
                <tr style="font-weight:700; background: var(--n-50);"><td colspan="2" class="text-end">TOTALES</td><td class="text-end"><?= moneda($resVentas['total_ventas']) ?></td><td class="text-end"><?= moneda((float)$igvVentas + $subtotalVenta) ?></td></tr>
            </tbody>
        </table>

        <p class="fw-semibold mt-3 mb-2">Asiento destino (reconocimiento del costo de ventas):</p>
        <table class="table">
            <thead>
                <tr><th>Cuenta</th><th>Denominación</th><th class="text-end">Debe</th><th class="text-end">Haber</th></tr>
            </thead>
            <tbody>
                <tr><td>69</td><td>Costo de Ventas</td><td class="text-end"><?= moneda($costoVentas) ?></td><td class="text-end">—</td></tr>
                <tr><td>20</td><td>Mercaderías</td><td class="text-end">—</td><td class="text-end"><?= moneda($costoVentas) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 3) COSTO DE VENTAS Y UTILIDAD BRUTA -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-graph-up-arrow"></i> 3. Costo de Ventas y Utilidad Bruta</div>
    <div class="card-body">
        <table class="table">
            <tbody>
                <tr><td>Ventas netas (sin IGV)</td><td class="text-end"><?= moneda($subtotalVenta) ?></td></tr>
                <tr><td>(−) Costo de Ventas <span class="text-muted small">(precio de compra × cantidad vendida)</span></td><td class="text-end">− <?= moneda($costoVentas) ?></td></tr>
                <tr style="font-weight:700; background: var(--n-50);"><td>Utilidad Bruta</td><td class="text-end"><?= moneda($utilidadBruta) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body text-muted small">
        <i class="bi bi-info-circle"></i>
        <strong>Pendiente (a propósito):</strong> la emisión electrónica directa a SUNAT (facturación electrónica vía API/PSE)
        no está implementada aquí — este reporte calcula los montos correctos, pero la transmisión real a SUNAT requiere
        credenciales SOL y un proveedor homologado. Coordina ese punto con el Ing. Lazarte antes de tu sustentación.
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
