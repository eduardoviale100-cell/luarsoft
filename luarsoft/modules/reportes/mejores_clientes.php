<?php
/**
 * modules/reportes/mejores_clientes.php — Reemplaza a "mejores_clientes.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$sql = "
    SELECT nombre_cliente, documento_cliente, COUNT(id_venta) AS total_ventas, SUM(total_venta) AS total_gastado
    FROM ventas
    WHERE nombre_cliente IS NOT NULL AND nombre_cliente != ''
    GROUP BY nombre_cliente, documento_cliente
    ORDER BY total_gastado DESC
    LIMIT 10
";
$resultado = mysqli_query($conexion, $sql);

$page_title = 'Mejores Clientes';
$page_subtitle = 'Reportes · Top 10 clientes';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-trophy"></i> Top 10 Mejores Clientes por Monto de Compra</h1>
        <p>Listado de los clientes que más han gastado en ventas registradas.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
    <a href="<?= url('modules/reportes/exportar.php?tipo=mejores_clientes') ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th class="text-center"># Rank</th><th>Nombre / Razón Social</th><th>Documento (RUC/DNI)</th><th>Total Gastado S/</th><th>Detalle</th><th class="no-print text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php
            $rank = 1;
            if ($resultado && mysqli_num_rows($resultado) > 0):
                while ($cliente = mysqli_fetch_assoc($resultado)):
                    $badge = 'neutral';
                    if ($rank === 1) $badge = 'warning';
                    elseif ($rank === 2) $badge = 'info';
                    elseif ($rank === 3) $badge = 'success';
            ?>
                <tr>
                    <td class="text-center"><span class="badge-status <?= $badge ?>">#<?= $rank ?></span></td>
                    <td><?= h($cliente['nombre_cliente']) ?></td>
                    <td><?= h($cliente['documento_cliente']) ?></td>
                    <td><strong><?= moneda($cliente['total_gastado']) ?></strong></td>
                    <td>Total de <?= (int)$cliente['total_ventas'] ?> ventas realizadas.</td>
                    <td class="no-print text-center">
                        <button type="button" class="btn btn-info btn-sm" title="Imprimir reporte individual"
                            onclick="ERP.verDocumento('<?= url('modules/reportes/imprimir_individual.php?tipo=mejores_clientes&documento=' . urlencode($cliente['documento_cliente'])) ?>', 'Ficha de <?= h($cliente['nombre_cliente']) ?>', '<?= url('modules/reportes/mejores_clientes.php') ?>')">
                            <i class="bi bi-printer"></i>
                        </button>
                    </td>
                </tr>
            <?php
                    $rank++;
                endwhile;
            else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron datos de ventas para generar el ranking.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
