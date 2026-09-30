<?php
/**
 * modules/reportes/productos_mas_vendidos.php — Reemplaza a "productos_mas_vendidos.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$sql = "
    SELECT dv.producto_codigo, p.descripcion AS nombre_producto, p.codigo AS codigo_sku, SUM(dv.cantidad) AS total_unidades_vendidas
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.producto_codigo = p.codigo
    GROUP BY dv.producto_codigo, p.descripcion, p.codigo
    ORDER BY total_unidades_vendidas DESC
    LIMIT 10
";
$resultado = mysqli_query($conexion, $sql);

$page_title = 'Productos Más Vendidos';
$page_subtitle = 'Reportes · Top 10 productos';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-fire"></i> Top 10 Productos Más Vendidos por Cantidad</h1>
        <p>Listado de los 10 productos con mayor número de unidades vendidas.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
    <a href="<?= url('modules/reportes/exportar.php?tipo=mas_vendidos') ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th class="text-center"># Rank</th><th>Código (En Detalle)</th><th>Nombre del Producto</th><th>Código / SKU</th><th>Total Unidades Vendidas</th><th class="no-print text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php
            $rank = 1;
            if ($resultado && mysqli_num_rows($resultado) > 0):
                while ($producto = mysqli_fetch_assoc($resultado)):
                    $badge = 'neutral';
                    if ($rank === 1) $badge = 'danger';
                    elseif ($rank === 2) $badge = 'warning';
                    elseif ($rank === 3) $badge = 'info';
            ?>
                <tr>
                    <td class="text-center"><span class="badge-status <?= $badge ?>">#<?= $rank ?></span></td>
                    <td><?= h($producto['producto_codigo']) ?></td>
                    <td><?= h($producto['nombre_producto']) ?></td>
                    <td><?= h($producto['codigo_sku']) ?></td>
                    <td><strong><?= number_format($producto['total_unidades_vendidas'], 0) ?> unidades</strong></td>
                    <td class="no-print text-center">
                        <button type="button" class="btn btn-info btn-sm" title="Imprimir reporte individual"
                            onclick="ERP.verDocumento('<?= url('modules/reportes/imprimir_individual.php?tipo=mas_vendidos&codigo=' . urlencode($producto['producto_codigo'])) ?>', 'Ficha de <?= h($producto['nombre_producto']) ?>', '<?= url('modules/reportes/productos_mas_vendidos.php') ?>')">
                            <i class="bi bi-printer"></i>
                        </button>
                    </td>
                </tr>
            <?php
                    $rank++;
                endwhile;
            else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron datos de ventas para generar el ranking de productos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
