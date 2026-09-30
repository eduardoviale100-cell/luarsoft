<?php
/**
 * modules/productos/listado.php — Reemplaza a "listado_productos.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$buscar = limpiar($_GET['buscar'] ?? '');

if ($buscar !== '') {
    $like = "%$buscar%";
    $stmt = mysqli_prepare($conexion, "SELECT codigo, codigo_barras, descripcion, categoria, imagen, stock, precio_compra, precio_venta, tipo_impuesto, fecha_registro FROM productos WHERE codigo LIKE ? OR codigo_barras LIKE ? OR descripcion LIKE ? OR categoria LIKE ? ORDER BY fecha_registro ASC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conexion, "SELECT codigo, codigo_barras, descripcion, categoria, imagen, stock, precio_compra, precio_venta, tipo_impuesto, fecha_registro FROM productos ORDER BY fecha_registro ASC");
}

$page_title = 'Listado de Productos';
$page_subtitle = 'Productos · Listado general';
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-list-ul"></i> Listado General de Productos</h1>
        <p>Consulta el inventario completo, edita precios o elimina productos.</p>
    </div>
    <div class="no-print">
        <a href="<?= url('modules/productos/nuevo.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo Producto</a>
        <?php if (esAdministrador()): ?>
            <a href="<?= url('database/migrar_skus.php') ?>" class="btn btn-outline-secondary" title="Recodificar los SKU antiguos al formato CATEGORIA-PRODUCTO-N°"><i class="bi bi-arrow-repeat"></i> Migrar códigos (SKU)</a>
        <?php endif; ?>
    </div>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="search-input-wrap">
                <span class="search-ico"><i class="bi bi-search"></i></span>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, código de barras, descripción o categoría..." value="<?= h($buscar) ?>">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        </div>
    </form>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="no-print">Img.</th>
                    <th>Código</th><th>Descripción</th><th>Categoría</th><th class="text-center">Stock</th>
                    <th>P. Compra</th><th>P. Venta</th><th>Impuesto</th><th>Fecha</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($resultado)):
                    $etiquetaImpuesto = match ($p['tipo_impuesto']) {
                        '10' => 'Gravado (18%)', '20' => 'Exonerado', '30' => 'Inafecto', default => 'N/A'
                    };
                    $badgeStock = $p['stock'] < 10 ? 'danger' : 'success';
                ?>
                    <tr>
                        <td class="no-print">
                            <?php if (!empty($p['imagen'])): ?>
                                <img src="<?= url('uploads/productos/' . $p['imagen']) ?>" alt="<?= h($p['descripcion']) ?>" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--n-200);">
                            <?php else: ?>
                                <span style="width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:var(--n-100); color:var(--n-400);"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($p['codigo']) ?></td>
                        <td><?= h($p['descripcion']) ?></td>
                        <td><?= h($p['categoria']) ?></td>
                        <td class="text-center"><span class="badge-status <?= $badgeStock ?>"><?= (int)$p['stock'] ?></span></td>
                        <td><?= number_format($p['precio_compra'], 2) ?></td>
                        <td><?= number_format($p['precio_venta'], 2) ?></td>
                        <td><?= h($etiquetaImpuesto) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['fecha_registro'])) ?></td>
                        <td class="no-print text-center">
                            <?php if (!empty($p['codigo_barras'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Ver/Imprimir etiqueta"
                                    onclick="ERP.verDocumento('<?= url('modules/productos/etiqueta.php?codigo=' . urlencode($p['codigo'])) ?>', 'Etiqueta de <?= h($p['descripcion']) ?>', '<?= url('modules/productos/listado.php') ?>')">
                                    <i class="bi bi-upc"></i>
                                </button>
                            <?php endif; ?>
                            <a href="<?= url('modules/productos/editar.php?id=' . urlencode($p['codigo'])) ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <a href="<?= url('modules/productos/eliminar.php?id=' . urlencode($p['codigo'])) ?>" class="btn btn-sm btn-danger" data-confirm-delete="¿Eliminar el producto «<?= h($p['descripcion']) ?>»?"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No hay productos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
