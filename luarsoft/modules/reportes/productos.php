<?php
/**
 * modules/reportes/productos.php
 * ------------------------------------------------------------------
 * NUEVO: Reporte Completo de Productos, con filtros por categoría y
 * nivel de stock, y valorización de inventario (costo total y valor
 * de venta total) por producto y en conjunto.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$categoria = limpiar($_GET['categoria'] ?? '');
$nivelStock = limpiar($_GET['nivel_stock'] ?? '');

$condiciones = [];
$params = [];
$types = '';

if ($categoria !== '') {
    $condiciones[] = 'categoria = ?';
    $params[] = $categoria;
    $types .= 's';
}
if ($nivelStock === 'bajo') {
    $condiciones[] = 'stock < 10';
} elseif ($nivelStock === 'agotado') {
    $condiciones[] = 'stock = 0';
} elseif ($nivelStock === 'normal') {
    $condiciones[] = 'stock >= 10';
}

$where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';
$sql = "SELECT * FROM productos $where ORDER BY categoria ASC, descripcion ASC";
$stmt = mysqli_prepare($conexion, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$filas = [];
$valorCostoTotal = 0;
$valorVentaTotal = 0;
while ($f = mysqli_fetch_assoc($resultado)) {
    $f['valor_costo'] = (float)$f['precio_compra'] * (int)$f['stock'];
    $f['valor_venta'] = (float)$f['precio_venta'] * (int)$f['stock'];
    $valorCostoTotal += $f['valor_costo'];
    $valorVentaTotal += $f['valor_venta'];
    $filas[] = $f;
}

$categorias = [];
$resCat = mysqli_query($conexion, "SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
while ($c = mysqli_fetch_assoc($resCat)) { $categorias[] = $c['categoria']; }

$page_title = 'Reporte de Productos';
$page_subtitle = 'Reportes · Inventario y valorización';
$active_menu = 'reportes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-box-seam"></i> Reporte Completo de Productos</h1>
        <p>Inventario filtrable por categoría y nivel de stock, con valorización total.</p>
    </div>
    <button onclick="window.print();" class="btn btn-primary no-print"><i class="bi bi-printer"></i> Imprimir listado</button>
    <a href="<?= url('modules/reportes/exportar.php?tipo=productos&' . http_build_query(['categoria' => $categoria, 'nivel_stock' => $nivelStock])) ?>" class="btn btn-success no-print"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label><i class="bi bi-tags"></i> Categoría</label>
            <select name="categoria" class="form-select">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= h($c) ?>" <?= $categoria === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label><i class="bi bi-bar-chart-line"></i> Nivel de Stock</label>
            <select name="nivel_stock" class="form-select">
                <option value="">Todos</option>
                <option value="normal" <?= $nivelStock === 'normal' ? 'selected' : '' ?>>Normal (10 o más)</option>
                <option value="bajo" <?= $nivelStock === 'bajo' ? 'selected' : '' ?>>Stock bajo (menos de 10)</option>
                <option value="agotado" <?= $nivelStock === 'agotado' ? 'selected' : '' ?>>Agotado (0)</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
    <?php if ($categoria || $nivelStock): ?>
        <div class="mt-2"><a href="<?= url('modules/reportes/productos.php') ?>" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a></div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-brand"><i class="bi bi-box-seam"></i></div>
            <div><div class="kpi-label">Productos listados</div><div class="kpi-value"><?= count($filas) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-warning"><i class="bi bi-cash-stack"></i></div>
            <div><div class="kpi-label">Valor al costo</div><div class="kpi-value"><?= moneda($valorCostoTotal) ?></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success"><i class="bi bi-cash-coin"></i></div>
            <div><div class="kpi-label">Valor de venta</div><div class="kpi-value"><?= moneda($valorVentaTotal) ?></div></div>
        </div>
    </div>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th><th>Descripción</th><th>Categoría</th><th class="text-center">Stock</th>
                    <th class="text-end">P. Compra</th><th class="text-end">P. Venta</th>
                    <th class="text-end">Valor Costo</th><th class="text-end">Valor Venta</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($filas)): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><?= h($f['codigo']) ?></td>
                        <td><?= h($f['descripcion']) ?></td>
                        <td><?= h($f['categoria']) ?></td>
                        <td class="text-center"><span class="badge-status <?= $f['stock'] == 0 ? 'danger' : ($f['stock'] < 10 ? 'warning' : 'success') ?>"><?= (int)$f['stock'] ?></span></td>
                        <td class="text-end"><?= number_format($f['precio_compra'], 2) ?></td>
                        <td class="text-end"><?= number_format($f['precio_venta'], 2) ?></td>
                        <td class="text-end"><?= number_format($f['valor_costo'], 2) ?></td>
                        <td class="text-end"><?= number_format($f['valor_venta'], 2) ?></td>
                        <td class="no-print text-center">
                            <button type="button" class="btn btn-info btn-sm" title="Imprimir reporte individual"
                                onclick="ERP.verDocumento('<?= url('modules/reportes/imprimir_individual.php?tipo=productos&codigo=' . urlencode($f['codigo'])) ?>', 'Ficha de <?= h($f['descripcion']) ?>', '<?= url('modules/reportes/productos.php') ?>')">
                                <i class="bi bi-printer"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron productos con los filtros aplicados.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($filas)): ?>
            <tfoot>
                <tr style="font-weight:700; background: var(--n-50);">
                    <td colspan="6" class="text-end">TOTALES:</td>
                    <td class="text-end"><?= number_format($valorCostoTotal, 2) ?></td>
                    <td class="text-end"><?= number_format($valorVentaTotal, 2) ?></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
