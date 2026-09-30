<?php
/**
 * modules/compras/listado.php — NUEVO.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$resultado = mysqli_query($conexion, "SELECT * FROM compras ORDER BY fecha DESC, id_compra DESC");

$totalGeneral = 0;
$filas = [];
if ($resultado) {
    while ($f = mysqli_fetch_assoc($resultado)) {
        $filas[] = $f;
        $totalGeneral += (float)$f['total'];
    }
}

$page_title = 'Registro de Compras';
$page_subtitle = 'Compras · Listado general';
$active_menu = 'compras';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-bag-check"></i> Registro de Compras</h1>
        <p>Historial de mercadería comprada a proveedores. Cada compra aumenta el stock automáticamente.</p>
    </div>
    <div class="no-print">
        <a href="<?= url('modules/reportes/kardex.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-journal-text"></i> Ver Kardex</a>
        <a href="<?= url('modules/compras/nuevo.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva Compra</a>
    </div>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th><th>Producto</th><th>Proveedor</th><th class="text-center">Cantidad</th>
                    <th class="text-end">Costo Unit.</th><th class="text-end">Total</th><th>Fecha</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td>#<?= h($f['id_compra']) ?></td>
                        <td><?= h($f['descripcion_producto']) ?> <small class="text-muted">(<?= h($f['producto_codigo']) ?>)</small></td>
                        <td><?= h($f['proveedor'] ?: '—') ?></td>
                        <td class="text-center">+<?= h($f['cantidad']) ?></td>
                        <td class="text-end"><?= moneda($f['costo_unitario']) ?></td>
                        <td class="text-end"><strong><?= moneda($f['total']) ?></strong></td>
                        <td><?= h(date('d/m/Y', strtotime($f['fecha']))) ?></td>
                        <td class="no-print text-center">
                            <a href="<?= url('modules/compras/eliminar.php?id=' . (int)$f['id_compra']) ?>" class="btn btn-danger btn-sm" title="Eliminar (revierte el stock)"
                                data-confirm-delete="¿Eliminar esta compra? Se descontarán <?= (int)$f['cantidad'] ?> unidades del stock de «<?= h($f['descripcion_producto']) ?>».">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay compras registradas.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if ($filas): ?>
            <tfoot>
                <tr style="font-weight:700; background: var(--n-50);">
                    <td colspan="5" class="text-end">TOTAL GENERAL:</td>
                    <td class="text-end"><?= moneda($totalGeneral) ?></td>
                    <td colspan="2" class="no-print"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
