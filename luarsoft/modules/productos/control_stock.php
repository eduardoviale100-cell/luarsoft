<?php
/**
 * modules/productos/control_stock.php — Reemplaza a "control_stock.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isset($_POST['actualizar'])) {
    $codigo = limpiar($_POST['codigo']);
    $nuevo_stock = (int)$_POST['nuevo_stock'];

    $stmt = mysqli_prepare($conexion, "UPDATE productos SET stock = ? WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "is", $nuevo_stock, $codigo);
    mysqli_stmt_execute($stmt);

    $redirect_url = url('modules/productos/control_stock.php') . '?ok=1';
    if (!empty($_POST['busqueda_actual'])) {
        $redirect_url .= '&busqueda=' . urlencode($_POST['busqueda_actual']);
    }
    header("Location: $redirect_url");
    exit;
}

$busqueda = limpiar($_GET['busqueda'] ?? '');

if ($busqueda !== '') {
    $like = "%$busqueda%";
    $stmt = mysqli_prepare($conexion, "SELECT codigo, descripcion, categoria, stock FROM productos WHERE codigo LIKE ? OR descripcion LIKE ? OR categoria LIKE ? ORDER BY descripcion ASC");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conexion, "SELECT codigo, descripcion, categoria, stock FROM productos ORDER BY descripcion ASC");
}

$page_title = 'Control de Stock';
$page_subtitle = 'Productos · Control de stock';
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-bar-chart-line"></i> Control de Stock</h1>
    <p>Actualiza rápidamente las existencias de cada producto.</p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success text-center alert-dismissible fade show" role="alert">
        <i class="bi bi-check-lg"></i> Stock actualizado correctamente
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="search-toolbar">
    <form method="GET" class="row g-2 align-items-center justify-content-center">
        <div class="col-auto"><label class="col-form-label fw-bold"><i class="bi bi-search"></i> Buscar Producto:</label></div>
        <div class="col-md-6">
            <input type="text" name="busqueda" class="form-control" placeholder="Escribe código, nombre o categoría..." value="<?= h($busqueda) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-success">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="<?= url('modules/productos/control_stock.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>SKU</th><th>Descripción</th><th>Categoría</th><th class="text-center">Stock Actual</th><th style="width:260px;">Actualizar Stock</th></tr>
            </thead>
            <tbody>
            <?php while ($p = mysqli_fetch_assoc($resultado)): ?>
                <tr>
                    <td><?= h($p['codigo']) ?></td>
                    <td><?= h($p['descripcion']) ?></td>
                    <td><?= h($p['categoria']) ?></td>
                    <td class="text-center">
                        <span class="badge-status <?= $p['stock'] < 10 ? 'danger' : 'success' ?>"><?= (int)$p['stock'] ?></span>
                    </td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="codigo" value="<?= h($p['codigo']) ?>">
                            <input type="hidden" name="busqueda_actual" value="<?= h($busqueda) ?>">
                            <input type="number" name="nuevo_stock" class="form-control" placeholder="Cant." min="0" required>
                            <button type="submit" name="actualizar" class="btn btn-primary btn-sm"><i class="bi bi-save"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <?= $busqueda !== '' ? 'No se encontraron productos para: <strong>' . h($busqueda) . '</strong>' : 'No hay productos registrados en el sistema.' ?>
    </div>
<?php endif; ?>

<div class="text-center mt-4">
    <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-secondary"><i class="bi bi-list-ul"></i> Volver al Listado</a>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
