<?php
/**
 * modules/reportes/flujo_ingresos.php — NUEVO.
 * ------------------------------------------------------------------
 * Flujo de Ingresos: resume por día el dinero que entra (ventas) y
 * el que sale (compras a proveedores) dentro de un rango de fechas,
 * con el neto acumulado. Complementa el Reporte General de Ventas ya
 * existente con una vista de caja/flujo, no solo de comprobantes.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$fecha_desde = limpiar($_GET['fecha_desde'] ?? date('Y-m-01'));
$fecha_hasta = limpiar($_GET['fecha_hasta'] ?? date('Y-m-d'));

$stmtIng = mysqli_prepare($conexion, "
    SELECT DATE(fecha) AS dia, SUM(total_venta) AS monto
    FROM ventas
    WHERE DATE(fecha) BETWEEN ? AND ?
    GROUP BY DATE(fecha)
");
mysqli_stmt_bind_param($stmtIng, "ss", $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmtIng);
$resIng = mysqli_stmt_get_result($stmtIng);
$ingresosPorDia = [];
$totalIngresos = 0;
while ($f = mysqli_fetch_assoc($resIng)) {
    $ingresosPorDia[$f['dia']] = (float)$f['monto'];
    $totalIngresos += (float)$f['monto'];
}

$stmtEgr = mysqli_prepare($conexion, "
    SELECT fecha AS dia, SUM(total) AS monto
    FROM compras
    WHERE fecha BETWEEN ? AND ?
    GROUP BY fecha
");
mysqli_stmt_bind_param($stmtEgr, "ss", $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmtEgr);
$resEgr = mysqli_stmt_get_result($stmtEgr);
$egresosPorDia = [];
$totalEgresos = 0;
while ($f = mysqli_fetch_assoc($resEgr)) {
    $egresosPorDia[$f['dia']] = (float)$f['monto'];
    $totalEgresos += (float)$f['monto'];
}

$dias = array_unique(array_merge(array_keys($ingresosPorDia), array_keys($egresosPorDia)));
sort($dias);

$filas = [];
$saldoAcumulado = 0;
foreach ($dias as $dia) {
    $ing = $ingresosPorDia[$dia] ?? 0;
    $egr = $egresosPorDia[$dia] ?? 0;
    $neto = $ing - $egr;
    $saldoAcumulado += $neto;
    $filas[] = ['dia' => $dia, 'ingresos' => $ing, 'egresos' => $egr, 'neto' => $neto, 'acumulado' => $saldoAcumulado];
}

$netoTotal = $totalIngresos - $totalEgresos;

$page_title = 'Flujo de Ingresos';
$page_subtitle = 'Reportes · Ingresos (ventas) vs egresos (compras)';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-graph-up"></i> Flujo de Ingresos</h1>
        <p>Dinero que entra por ventas y sale por compras a proveedores, día a día.</p>
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
            <label><i class="bi bi-calendar-check"></i> Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?= h($fecha_hasta) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </form>
</div>

<div class="row g-3 my-1">
    <div class="col-6 col-lg-4">
        <div class="kpi-card">
            <div class="kpi-icon bg-success"><i class="bi bi-arrow-down-circle"></i></div>
            <div><div class="kpi-label">Total Ingresos (Ventas)</div><div class="kpi-value"><?= moneda($totalIngresos) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="kpi-card">
            <div class="kpi-icon bg-red"><i class="bi bi-arrow-up-circle"></i></div>
            <div><div class="kpi-label">Total Egresos (Compras)</div><div class="kpi-value"><?= moneda($totalEgresos) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="kpi-card">
            <div class="kpi-icon bg-info"><i class="bi bi-cash-stack"></i></div>
            <div><div class="kpi-label">Flujo Neto del Período</div><div class="kpi-value"><?= moneda($netoTotal) ?></div></div>
        </div>
    </div>
</div>

<div class="table-erp-wrap mt-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th><th class="text-end">Ingresos</th><th class="text-end">Egresos</th>
                    <th class="text-end">Neto del Día</th><th class="text-end">Saldo Acumulado</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><?= h(date('d/m/Y', strtotime($f['dia']))) ?></td>
                        <td class="text-end" style="color:var(--color-success);"><?= moneda($f['ingresos']) ?></td>
                        <td class="text-end" style="color:var(--color-danger);"><?= moneda($f['egresos']) ?></td>
                        <td class="text-end"><?= moneda($f['neto']) ?></td>
                        <td class="text-end"><strong><?= moneda($f['acumulado']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No hay movimientos de ingresos ni egresos en el rango seleccionado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
