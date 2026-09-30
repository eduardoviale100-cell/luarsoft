<?php
/**
 * modules/ventas/lista_facturas.php — Reemplaza a "lista_facturas.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$buscar = limpiar($_GET['buscar'] ?? '');
$where = '';
$params = [];
$types = '';

if ($buscar !== '') {
    $like = "%$buscar%";
    $where = " AND (v.numero_documento LIKE ? OR v.nombre_cliente LIKE ? OR v.documento_cliente LIKE ? OR DATE(v.fecha) LIKE ?)";
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$sql = "
    SELECT v.id_venta, v.fecha, v.numero_documento, v.nombre_cliente, v.documento_cliente, v.total_venta, v.metodo_pago,
           GROUP_CONCAT(dv.cantidad, 'x ', dv.descripcion SEPARATOR '<br>') AS lista_productos
    FROM ventas v
    LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
    WHERE v.tipo_documento = 'FACTURA' $where
    GROUP BY v.id_venta
    ORDER BY v.id_venta DESC
";

$stmt = mysqli_prepare($conexion, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$page_title = 'Listado de Facturas';
$page_subtitle = 'Ventas · Facturas';
$active_menu = 'ventas';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-receipt"></i> Listado General de Facturas</h1>
    <p>Consulta, edita, elimina o imprime cualquier factura emitida.</p>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="search-input-wrap">
                <span class="search-ico"><i class="bi bi-search"></i></span>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por N° factura, RUC o fecha" value="<?= h($buscar) ?>">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="<?= url('modules/ventas/lista_facturas.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        </div>
    </form>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>ID</th><th># Factura</th><th>Fecha</th><th>RUC / Razón Social</th><th>Productos</th><th>Total S/</th><th>Pago</th><th class="no-print text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($venta = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?= h($venta['id_venta']) ?></td>
                        <td><?= h($venta['numero_documento']) ?></td>
                        <td><?= h(date('d/m/Y H:i', strtotime($venta['fecha']))) ?></td>
                        <td><?= h($venta['nombre_cliente']) ?><br><small class="text-muted"><?= h($venta['documento_cliente']) ?></small></td>
                        <td><?= $venta['lista_productos'] ?></td>
                        <td><?= moneda($venta['total_venta']) ?></td>
                        <td><?= h($venta['metodo_pago'] ?: '—') ?></td>
                        <td class="no-print text-center">
                            <a href="<?= url('modules/ventas/editar_factura.php?id=' . (int)$venta['id_venta']) ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i></a>
                            <button type="button" class="btn btn-sm btn-info" onclick="ERP.verDocumento('<?= url('modules/ventas/imprimir.php?id=' . (int)$venta['id_venta']) ?>', 'Factura <?= h($venta['numero_documento']) ?>', '<?= url('modules/ventas/lista_facturas.php') ?>')"><i class="bi bi-printer"></i></button>
                            <a href="<?= url('modules/ventas/eliminar_factura.php?id=' . (int)$venta['id_venta']) ?>" class="btn btn-sm btn-danger" data-confirm-delete="¿Eliminar la factura <?= h($venta['numero_documento']) ?>?"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay facturas registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
