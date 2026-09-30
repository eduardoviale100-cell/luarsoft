<?php
/**
 * modules/productos/eliminar.php — Reemplaza a "eliminar_producto.php".
 * Conserva el paso de confirmación explícita antes de borrar.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_GET['id']) || $_GET['id'] === '') {
    flash('error', 'Código de producto no especificado para eliminar.');
    header('Location: ' . url('modules/productos/listado.php'));
    exit;
}
$codigo_producto = trim($_GET['id']);

if (isset($_GET['confirmar']) && $_GET['confirmar'] === 'si') {
    $stmtImg = mysqli_prepare($conexion, "SELECT imagen FROM productos WHERE codigo = ?");
    mysqli_stmt_bind_param($stmtImg, "s", $codigo_producto);
    mysqli_stmt_execute($stmtImg);
    $imagenActual = mysqli_stmt_get_result($stmtImg)->fetch_assoc()['imagen'] ?? null;

    $stmt = mysqli_prepare($conexion, "DELETE FROM productos WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "s", $codigo_producto);
    if (mysqli_stmt_execute($stmt)) {
        eliminarImagenReferencia($imagenActual, 'productos');
        flash('success', "Producto con código $codigo_producto eliminado correctamente.");
    } elseif (mysqli_errno($conexion) === 1451) {
        // Error 1451 = violación de FOREIGN KEY (el producto tiene ventas,
        // compras o repuestos de órdenes asociados en su historial).
        flash('error', "No se puede eliminar «$codigo_producto»: tiene ventas, compras o repuestos de órdenes registrados en su historial. Si ya no lo vendes, puedes dejarlo con stock en 0 en vez de eliminarlo.");
    } else {
        flash('error', 'Error al eliminar: ' . mysqli_error($conexion));
    }
    header('Location: ' . url('modules/productos/listado.php'));
    exit;
}

$page_title = 'Confirmar Eliminación';
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>
<div class="card-erp" style="max-width:560px; margin:40px auto;">
    <div class="card-erp-body text-center">
        <h2><i class="bi bi-exclamation-triangle"></i> ¿Eliminar este producto?</h2>
        <p class="text-muted">Esta acción no se puede deshacer.</p>
        <p>Producto a eliminar: <strong><?= h($codigo_producto) ?></strong></p>
        <a href="<?= url('modules/productos/eliminar.php?id=' . urlencode($codigo_producto) . '&confirmar=si') ?>" class="btn btn-danger me-2">Sí, Eliminar Permanentemente</a>
        <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-secondary">Cancelar y Volver</a>
    </div>
</div>
<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
