<?php
/**
 * modules/reportes/servicios_mes.php — NUEVO.
 * ------------------------------------------------------------------
 * Listado de servicios (Órdenes de Reparación) realizados/entregados
 * dentro de un mes determinado, con totales de garantías otorgadas y
 * monto estimado acumulado.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$mes = limpiar($_GET['mes'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    $mes = date('Y-m');
}

$stmt = mysqli_prepare($conexion, "
    SELECT * FROM ordenes
    WHERE DATE_FORMAT(fecha_ingreso, '%Y-%m') = ?
       OR DATE_FORMAT(fecha_entrega, '%Y-%m') = ?
    ORDER BY fecha_ingreso ASC
");
mysqli_stmt_bind_param($stmt, "ss", $mes, $mes);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$filas = [];
$totalEntregados = 0;
$totalEstimado = 0;
if ($resultado) {
    while ($f = mysqli_fetch_assoc($resultado)) {
        $filas[] = $f;
        if ($f['estado'] === 'Entregado') { $totalEntregados++; }
        $totalEstimado += (float)($f['monto_estimado'] ?? 0);
    }
}

$page_title = 'Servicios Realizados en el Mes';
$page_subtitle = 'Reportes · Órdenes de reparación por mes';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-tools"></i> Servicios Realizados en el Mes</h1>
        <p>Órdenes de reparación con movimiento (ingreso o entrega) dentro del mes seleccionado.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label><i class="bi bi-calendar-month"></i> Mes</label>
            <input type="month" name="mes" class="form-control" value="<?= h($mes) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </form>
</div>

<div class="row g-3 my-1">
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-brand"><i class="bi bi-tools"></i></div>
            <div><div class="kpi-label">Órdenes con movimiento</div><div class="kpi-value"><?= count($filas) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success"><i class="bi bi-check-circle"></i></div>
            <div><div class="kpi-label">Entregados en el mes</div><div class="kpi-value"><?= $totalEntregados ?></div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-info"><i class="bi bi-cash-coin"></i></div>
            <div><div class="kpi-label">Monto Estimado Total</div><div class="kpi-value"><?= moneda($totalEstimado) ?></div></div>
        </div>
    </div>
</div>

<div class="table-erp-wrap mt-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th><th>Cliente</th><th>Equipo</th><th>Estado</th>
                    <th>Ingreso</th><th>Entrega</th><th class="text-end">Monto Estimado</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td>#<?= h($f['id_orden']) ?></td>
                        <td><?= h($f['cliente']) ?></td>
                        <td><?= h(trim(($f['marca'] ?? '') . ' ' . $f['modelo_impresora'])) ?></td>
                        <td><?= h($f['estado']) ?></td>
                        <td><?= h(date('d/m/Y', strtotime($f['fecha_ingreso']))) ?></td>
                        <td><?= $f['fecha_entrega'] ? h(date('d/m/Y', strtotime($f['fecha_entrega']))) : '—' ?></td>
                        <td class="text-end"><?= $f['monto_estimado'] !== null ? moneda($f['monto_estimado']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay órdenes con movimiento en el mes seleccionado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
