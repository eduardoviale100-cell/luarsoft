<?php
/**
 * modules/reportes/ventas.php
 * ------------------------------------------------------------------
 * NUEVO: Reporte General de Ventas — unifica boletas y facturas
 * emitidas, con filtros por tipo de documento y rango de fechas, y
 * resumen de totales (N° de boletas/facturas y montos).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$tipo = limpiar($_GET['tipo'] ?? '');
$fecha_desde = limpiar($_GET['fecha_desde'] ?? '');
$fecha_hasta = limpiar($_GET['fecha_hasta'] ?? '');

$condiciones = [];
$params = [];
$types = '';

if ($tipo === 'BOLETA' || $tipo === 'FACTURA') {
    $condiciones[] = 'tipo_documento = ?';
    $params[] = $tipo;
    $types .= 's';
}
if ($fecha_desde !== '') {
    $condiciones[] = 'fecha >= ?';
    $params[] = $fecha_desde . ' 00:00:00';
    $types .= 's';
}
if ($fecha_hasta !== '') {
    $condiciones[] = 'fecha <= ?';
    $params[] = $fecha_hasta . ' 23:59:59';
    $types .= 's';
}
$where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

$sql = "SELECT * FROM ventas $where ORDER BY fecha DESC";
$stmt = mysqli_prepare($conexion, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$filas = [];
$totalBoletas = 0; $montoBoletas = 0;
$totalFacturas = 0; $montoFacturas = 0;
while ($f = mysqli_fetch_assoc($resultado)) {
    $filas[] = $f;
    if ($f['tipo_documento'] === 'BOLETA') { $totalBoletas++; $montoBoletas += (float)$f['total_venta']; }
    else { $totalFacturas++; $montoFacturas += (float)$f['total_venta']; }
}
$montoGeneral = $montoBoletas + $montoFacturas;

$page_title = 'Reporte General de Ventas';
$page_subtitle = 'Reportes · Boletas y Facturas emitidas';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-receipt"></i> Reporte General de Ventas</h1>
        <p>Boletas y facturas emitidas, con filtros por tipo de documento y periodo.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
    <a href="<?= url('modules/reportes/exportar.php?tipo=ventas&' . http_build_query(['tipo' => $tipo, 'fecha_desde' => $fecha_desde, 'fecha_hasta' => $fecha_hasta])) ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label><i class="bi bi-filter"></i> Tipo de Documento</label>
            <select name="tipo" class="form-select">
                <option value="">Todos (Boletas y Facturas)</option>
                <option value="BOLETA" <?= $tipo === 'BOLETA' ? 'selected' : '' ?>>Solo Boletas</option>
                <option value="FACTURA" <?= $tipo === 'FACTURA' ? 'selected' : '' ?>>Solo Facturas</option>
            </select>
        </div>
        <div class="col-md-3">
            <label><i class="bi bi-calendar-event"></i> Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="<?= h($fecha_desde) ?>">
        </div>
        <div class="col-md-3">
            <label><i class="bi bi-calendar-check"></i> Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?= h($fecha_hasta) ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
    <?php if ($tipo || $fecha_desde || $fecha_hasta): ?>
        <div class="mt-2"><a href="<?= url('modules/reportes/ventas.php') ?>" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a></div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-brand"><i class="bi bi-receipt-cutoff"></i></div>
            <div><div class="kpi-label">Boletas emitidas</div><div class="kpi-value"><?= $totalBoletas ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-warning"><i class="bi bi-file-earmark-text"></i></div>
            <div><div class="kpi-label">Facturas emitidas</div><div class="kpi-value"><?= $totalFacturas ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success"><i class="bi bi-cash-coin"></i></div>
            <div><div class="kpi-label">Monto Boletas + Facturas</div><div class="kpi-value"><?= moneda($montoGeneral) ?></div></div>
        </div>
    </div>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tipo</th><th># Documento</th><th>Fecha</th><th>Cliente</th><th>Documento Cliente</th><th class="text-end">Total</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($filas)): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><span class="badge-status <?= $f['tipo_documento'] === 'BOLETA' ? 'brand' : 'warning' ?>"><?= h($f['tipo_documento']) ?></span></td>
                        <td><?= h($f['numero_documento']) ?></td>
                        <td><?= h(date('d/m/Y H:i', strtotime($f['fecha']))) ?></td>
                        <td><?= h($f['nombre_cliente']) ?></td>
                        <td><?= h($f['documento_cliente']) ?></td>
                        <td class="text-end"><?= moneda($f['total_venta']) ?></td>
                        <td class="no-print text-center">
                            <button type="button" class="btn btn-info btn-sm" title="Imprimir comprobante individual"
                                onclick="ERP.verDocumento('<?= url('modules/ventas/imprimir.php?id=' . (int)$f['id_venta']) ?>', '<?= h($f['tipo_documento']) ?> <?= h($f['numero_documento']) ?>', '<?= url('modules/reportes/ventas.php') ?>')">
                                <i class="bi bi-printer"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron ventas con los filtros aplicados.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($filas)): ?>
            <tfoot>
                <tr style="font-weight:700; background: var(--n-50);">
                    <td colspan="5" class="text-end">TOTAL GENERAL:</td>
                    <td class="text-end"><?= moneda($montoGeneral) ?></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
