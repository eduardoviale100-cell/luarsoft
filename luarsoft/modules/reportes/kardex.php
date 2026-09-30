<?php
/**
 * modules/reportes/kardex.php — NUEVO.
 * ------------------------------------------------------------------
 * Kardex de inventario: ficha de movimientos de un producto (entradas
 * por compras a proveedores + salidas por ventas), en orden
 * cronológico, con saldo acumulado. Complementa el Registro de
 * Compras (módulo nuevo) y el Registro de Ventas ya existente.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$codigo = limpiar($_GET['producto'] ?? '');
$movimientos = [];
$productoInfo = null;

if ($codigo !== '') {
    $stmtProd = mysqli_prepare($conexion, "SELECT codigo, descripcion, stock FROM productos WHERE codigo = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtProd, "s", $codigo);
    mysqli_stmt_execute($stmtProd);
    $productoInfo = mysqli_stmt_get_result($stmtProd)->fetch_assoc();

    if ($productoInfo) {
        $stmtC = mysqli_prepare($conexion, "SELECT fecha, cantidad, costo_unitario AS precio, proveedor AS detalle FROM compras WHERE producto_codigo = ? ORDER BY fecha ASC");
        mysqli_stmt_bind_param($stmtC, "s", $codigo);
        mysqli_stmt_execute($stmtC);
        $resC = mysqli_stmt_get_result($stmtC);
        while ($f = mysqli_fetch_assoc($resC)) {
            $movimientos[] = [
                'fecha' => $f['fecha'], 'tipo' => 'Entrada', 'cantidad' => (int)$f['cantidad'],
                'precio' => (float)$f['precio'], 'detalle' => $f['detalle'] ?: 'Compra a proveedor',
            ];
        }

        $stmtV = mysqli_prepare($conexion, "
            SELECT v.fecha, dv.cantidad, dv.precio_unitario AS precio, v.numero_documento AS detalle
            FROM detalle_venta dv
            INNER JOIN ventas v ON dv.id_venta = v.id_venta
            WHERE dv.producto_codigo = ?
            ORDER BY v.fecha ASC
        ");
        mysqli_stmt_bind_param($stmtV, "s", $codigo);
        mysqli_stmt_execute($stmtV);
        $resV = mysqli_stmt_get_result($stmtV);
        while ($f = mysqli_fetch_assoc($resV)) {
            $movimientos[] = [
                'fecha' => $f['fecha'], 'tipo' => 'Salida', 'cantidad' => (int)$f['cantidad'],
                'precio' => (float)$f['precio'], 'detalle' => 'Venta ' . $f['detalle'],
            ];
        }

        usort($movimientos, function ($a, $b) { return strtotime($a['fecha']) <=> strtotime($b['fecha']); });

        $saldo = 0;
        foreach ($movimientos as &$m) {
            $saldo += ($m['tipo'] === 'Entrada') ? $m['cantidad'] : -$m['cantidad'];
            $m['saldo'] = $saldo;
        }
        unset($m);
    }
}

$productosDisponibles = mysqli_query($conexion, "SELECT codigo, descripcion FROM productos ORDER BY descripcion ASC");

$page_title = 'Kardex de Inventario';
$page_subtitle = 'Reportes · Movimientos de stock por producto';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-journal-text"></i> Kardex de Inventario</h1>
        <p>Historial de entradas (compras) y salidas (ventas) de un producto, con saldo acumulado.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir</button>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label><i class="bi bi-box-seam"></i> Producto</label>
            <select name="producto" class="form-select" required>
                <option value="">Selecciona un producto...</option>
                <?php mysqli_data_seek($productosDisponibles, 0); while ($p = mysqli_fetch_assoc($productosDisponibles)): ?>
                    <option value="<?= h($p['codigo']) ?>" <?= $codigo === $p['codigo'] ? 'selected' : '' ?>><?= h($p['descripcion']) ?> (<?= h($p['codigo']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Ver Kardex</button>
        </div>
    </form>
</div>

<?php if ($codigo === ''): ?>
    <div class="alert alert-info mt-3"><i class="bi bi-info-circle"></i> Selecciona un producto para ver su historial de movimientos de stock.</div>
<?php elseif (!$productoInfo): ?>
    <div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle"></i> Producto no encontrado.</div>
<?php else: ?>

    <div class="card-erp mt-3 mb-3">
        <div class="card-erp-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-1"><?= h($productoInfo['descripcion']) ?></h3>
                <small class="text-muted">Código: <?= h($productoInfo['codigo']) ?></small>
            </div>
            <div class="text-end">
                <div class="text-muted small">Stock actual en sistema</div>
                <div style="font-size:1.4rem; font-weight:700; color:var(--accent-dark);"><?= (int)$productoInfo['stock'] ?> uds.</div>
            </div>
        </div>
    </div>

    <div class="table-erp-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th><th>Tipo</th><th>Detalle</th>
                        <th class="text-center">Entrada</th><th class="text-center">Salida</th>
                        <th class="text-end">Precio Unit.</th><th class="text-center">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($movimientos): ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?= h(date('d/m/Y', strtotime($m['fecha']))) ?></td>
                            <td>
                                <?php if ($m['tipo'] === 'Entrada'): ?>
                                    <span class="badge-status success"><i class="bi bi-arrow-down-circle"></i> Entrada</span>
                                <?php else: ?>
                                    <span class="badge-status danger"><i class="bi bi-arrow-up-circle"></i> Salida</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($m['detalle']) ?></td>
                            <td class="text-center"><?= $m['tipo'] === 'Entrada' ? '+' . $m['cantidad'] : '—' ?></td>
                            <td class="text-center"><?= $m['tipo'] === 'Salida' ? '-' . $m['cantidad'] : '—' ?></td>
                            <td class="text-end"><?= moneda($m['precio']) ?></td>
                            <td class="text-center"><strong><?= $m['saldo'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Este producto aún no tiene movimientos registrados (compras o ventas).</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
