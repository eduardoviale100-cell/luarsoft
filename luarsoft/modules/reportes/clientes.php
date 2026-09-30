<?php
/**
 * modules/reportes/clientes.php
 * ------------------------------------------------------------------
 * NUEVO: Reporte Completo de Clientes. Muestra a todos los clientes
 * registrados junto con su historial de compras (N° de compras,
 * monto total gastado y fecha de la última compra), con filtro
 * opcional por rango de fechas para acotar el historial calculado.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$fecha_desde = limpiar($_GET['fecha_desde'] ?? '');
$fecha_hasta = limpiar($_GET['fecha_hasta'] ?? '');
$buscar = limpiar($_GET['buscar'] ?? '');

$condicionesJoin = [];
$params = [];
$types = '';

if ($fecha_desde !== '') {
    $condicionesJoin[] = 'v.fecha >= ?';
    $params[] = $fecha_desde . ' 00:00:00';
    $types .= 's';
}
if ($fecha_hasta !== '') {
    $condicionesJoin[] = 'v.fecha <= ?';
    $params[] = $fecha_hasta . ' 23:59:59';
    $types .= 's';
}
$onJoin = 'v.documento_cliente = c.numero_documento';
if ($condicionesJoin) {
    $onJoin .= ' AND ' . implode(' AND ', $condicionesJoin);
}

$whereBuscar = '';
if ($buscar !== '') {
    $whereBuscar = 'WHERE c.nombre LIKE ? OR c.numero_documento LIKE ? OR c.ciudad LIKE ?';
    $like = "%$buscar%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$sql = "
    SELECT c.id_cliente, c.nombre, c.tipo_documento, c.numero_documento, c.telefono, c.ciudad, c.region,
           COUNT(v.id_venta) AS total_compras,
           COALESCE(SUM(v.total_venta), 0) AS total_gastado,
           MAX(v.fecha) AS ultima_compra
    FROM clientes c
    LEFT JOIN ventas v ON $onJoin
    $whereBuscar
    GROUP BY c.id_cliente
    ORDER BY total_gastado DESC, c.nombre ASC
";

$stmt = mysqli_prepare($conexion, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$filas = [];
$granTotalGastado = 0;
$granTotalCompras = 0;
while ($f = mysqli_fetch_assoc($resultado)) {
    $filas[] = $f;
    $granTotalGastado += (float)$f['total_gastado'];
    $granTotalCompras += (int)$f['total_compras'];
}

$page_title = 'Reporte de Clientes';
$page_subtitle = 'Reportes · Clientes e historial de compras';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-people"></i> Reporte Completo de Clientes</h1>
        <p>Listado de clientes con su historial de compras. Filtra por fecha para acotar el periodo del historial.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
    <a href="<?= url('modules/reportes/exportar.php?tipo=clientes&' . http_build_query(['buscar' => $buscar, 'fecha_desde' => $fecha_desde, 'fecha_hasta' => $fecha_hasta])) ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label><i class="bi bi-search"></i> Buscar cliente</label>
            <input type="text" name="buscar" class="form-control" placeholder="Nombre, documento o ciudad..." value="<?= h($buscar) ?>">
        </div>
        <div class="col-md-3">
            <label><i class="bi bi-calendar-event"></i> Compras desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="<?= h($fecha_desde) ?>">
        </div>
        <div class="col-md-3">
            <label><i class="bi bi-calendar-check"></i> Compras hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?= h($fecha_hasta) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
    <?php if ($fecha_desde || $fecha_hasta || $buscar): ?>
        <div class="mt-2"><a href="<?= url('modules/reportes/clientes.php') ?>" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a></div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-brand"><i class="bi bi-people"></i></div>
            <div><div class="kpi-label">Clientes listados</div><div class="kpi-value"><?= count($filas) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success"><i class="bi bi-bag-check"></i></div>
            <div><div class="kpi-label">Compras en el periodo</div><div class="kpi-value"><?= $granTotalCompras ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-warning"><i class="bi bi-cash-coin"></i></div>
            <div><div class="kpi-label">Monto total del periodo</div><div class="kpi-value"><?= moneda($granTotalGastado) ?></div></div>
        </div>
    </div>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Cliente</th><th>Documento</th><th>Teléfono</th><th>Ciudad / Región</th>
                    <th class="text-center">N° Compras</th><th class="text-end">Total Gastado</th><th>Última Compra</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($filas)): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><?= h($f['nombre']) ?></td>
                        <td><?= h($f['tipo_documento']) ?> <?= h($f['numero_documento']) ?></td>
                        <td><?= h($f['telefono']) ?></td>
                        <td><?= h($f['ciudad']) ?> / <?= h($f['region']) ?></td>
                        <td class="text-center">
                            <span class="badge-status <?= $f['total_compras'] > 0 ? 'success' : 'neutral' ?>"><?= (int)$f['total_compras'] ?></span>
                        </td>
                        <td class="text-end"><?= moneda($f['total_gastado']) ?></td>
                        <td><?= $f['ultima_compra'] ? h(date('d/m/Y', strtotime($f['ultima_compra']))) : '—' ?></td>
                        <td class="no-print text-center">
                            <button type="button" class="btn btn-info btn-sm" title="Imprimir reporte individual"
                                onclick="ERP.verDocumento('<?= url('modules/reportes/imprimir_individual.php?tipo=clientes&id=' . (int)$f['id_cliente']) ?>', 'Ficha de <?= h($f['nombre']) ?>', '<?= url('modules/reportes/clientes.php') ?>')">
                                <i class="bi bi-printer"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron clientes con los filtros aplicados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
