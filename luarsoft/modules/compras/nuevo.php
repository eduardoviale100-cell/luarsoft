<?php
/**
 * modules/compras/nuevo.php — NUEVO.
 * ------------------------------------------------------------------
 * Registro de Compras a proveedores. Cada compra registrada:
 *   1. Se guarda en la tabla `compras` (alimenta el reporte de Kardex
 *      junto con las salidas de `detalle_venta`).
 *   2. Aumenta automáticamente el stock del producto seleccionado,
 *      igual que una venta lo disminuye.
 *   3. Opcionalmente actualiza el precio de compra del producto con
 *      el costo unitario de esta compra.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_codigo = limpiar($_POST['producto_codigo'] ?? '');
    $proveedor = limpiar($_POST['proveedor'] ?? '');
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $costo_unitario = (float)($_POST['costo_unitario'] ?? 0);
    $fecha = limpiar($_POST['fecha'] ?? date('Y-m-d'));
    $observaciones = limpiar($_POST['observaciones'] ?? '');
    $actualizar_precio = !empty($_POST['actualizar_precio']);

    if ($producto_codigo === '' || $cantidad <= 0 || $costo_unitario <= 0) {
        $error = 'Selecciona un producto e ingresa una cantidad y costo válidos.';
    } else {
        $stmtProd = mysqli_prepare($conexion, "SELECT descripcion FROM productos WHERE codigo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtProd, "s", $producto_codigo);
        mysqli_stmt_execute($stmtProd);
        $producto = mysqli_stmt_get_result($stmtProd)->fetch_assoc();

        if (!$producto) {
            $error = 'El producto seleccionado no existe.';
        } else {
            $total = $cantidad * $costo_unitario;

            mysqli_begin_transaction($conexion);
            try {
                $ins = mysqli_prepare($conexion, "INSERT INTO compras (producto_codigo, descripcion_producto, proveedor, cantidad, costo_unitario, total, fecha, observaciones) VALUES (?,?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($ins, "sssidsss", $producto_codigo, $producto['descripcion'], $proveedor, $cantidad, $costo_unitario, $total, $fecha, $observaciones);
                if (!mysqli_stmt_execute($ins)) {
                    throw new RuntimeException('No se pudo registrar la compra.');
                }

                if ($actualizar_precio) {
                    $updStock = mysqli_prepare($conexion, "UPDATE productos SET stock = stock + ?, precio_compra = ? WHERE codigo = ?");
                    mysqli_stmt_bind_param($updStock, "ids", $cantidad, $costo_unitario, $producto_codigo);
                } else {
                    $updStock = mysqli_prepare($conexion, "UPDATE productos SET stock = stock + ? WHERE codigo = ?");
                    mysqli_stmt_bind_param($updStock, "is", $cantidad, $producto_codigo);
                }
                if (!mysqli_stmt_execute($updStock)) {
                    throw new RuntimeException('No se pudo actualizar el stock del producto.');
                }

                mysqli_commit($conexion);
                flash('success', 'Compra registrada correctamente. Stock actualizado (+' . $cantidad . ' uds.).');
                header('Location: ' . url('modules/compras/listado.php'));
                exit;
            } catch (RuntimeException $e) {
                mysqli_rollback($conexion);
                $error = $e->getMessage();
            }
        }
    }
}

$productos = mysqli_query($conexion, "SELECT codigo, descripcion, stock FROM productos ORDER BY descripcion ASC");

$page_title = 'Nueva Compra';
$page_subtitle = 'Compras · Registrar ingreso de mercadería';
$active_menu = 'compras';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-bag-plus"></i> Registrar Nueva Compra</h1>
    <p>Registra el ingreso de mercadería de un proveedor. El stock del producto se actualiza automáticamente.</p>
</div>

<div class="card-erp">
    <div class="card-erp-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-box-seam"></i> Producto</label>
                    <select name="producto_codigo" class="form-select" required>
                        <option value="">Selecciona un producto...</option>
                        <?php mysqli_data_seek($productos, 0); while ($p = mysqli_fetch_assoc($productos)): ?>
                            <option value="<?= h($p['codigo']) ?>"><?= h($p['descripcion']) ?> (<?= h($p['codigo']) ?>) · Stock actual: <?= h($p['stock']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-truck"></i> Proveedor</label>
                    <input type="text" name="proveedor" class="form-control" placeholder="Ej: Distribuidora Cañete Digital">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-123"></i> Cantidad Comprada</label>
                    <input type="number" name="cantidad" class="form-control" min="1" placeholder="Ej: 10" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-cash"></i> Costo Unitario (S/)</label>
                    <input type="number" name="costo_unitario" step="0.01" class="form-control" min="0.01" placeholder="0.00" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-calendar-event"></i> Fecha de Compra</label>
                    <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-card-text"></i> Observaciones (opcional)</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Ej: N° de guía o factura del proveedor">
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="checkActualizarPrecio" name="actualizar_precio" value="1" checked>
                <label class="form-check-label" for="checkActualizarPrecio">Actualizar el precio de compra del producto con este costo unitario</label>
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Compra</button>
                <a href="<?= url('modules/compras/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
