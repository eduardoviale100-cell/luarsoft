<?php
/**
 * modules/ventas/editar_boleta.php — Reemplaza a "editar_boleta.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    flash('error', 'ID de venta no especificado.');
    header('Location: ' . url('modules/ventas/lista_boletas.php'));
    exit;
}
$id_venta = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_documento = limpiar($_POST['numero_documento']);
    $fecha = limpiar($_POST['fecha']);
    $nombre_cliente = limpiar($_POST['nombre_cliente']);
    $total_venta = (float)$_POST['total_venta'];
    $metodo_pago = limpiar($_POST['metodo_pago'] ?? 'Efectivo');

    $upd = mysqli_prepare($conexion, "UPDATE ventas SET numero_documento=?, fecha=?, nombre_cliente=?, total_venta=?, metodo_pago=? WHERE id_venta=? AND tipo_documento='BOLETA'");
    mysqli_stmt_bind_param($upd, "sssdsi", $numero_documento, $fecha, $nombre_cliente, $total_venta, $metodo_pago, $id_venta);

    if (mysqli_stmt_execute($upd)) {
        flash('success', "Boleta $id_venta actualizada exitosamente.");
    } else {
        flash('error', 'Error al actualizar la boleta: ' . mysqli_error($conexion));
    }
    header('Location: ' . url('modules/ventas/editar_boleta.php?id=' . $id_venta));
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT id_venta, numero_documento, fecha, nombre_cliente, total_venta, metodo_pago FROM ventas WHERE id_venta = ? AND tipo_documento = 'BOLETA'");
mysqli_stmt_bind_param($stmt, "i", $id_venta);
mysqli_stmt_execute($stmt);
$resultado_select = mysqli_stmt_get_result($stmt);

if (!$resultado_select || mysqli_num_rows($resultado_select) === 0) {
    flash('error', 'No se encontró la boleta indicada.');
    header('Location: ' . url('modules/ventas/lista_boletas.php'));
    exit;
}
$datos_venta = mysqli_fetch_assoc($resultado_select);

$page_title = 'Editar Boleta';
$page_subtitle = 'Ventas · Boleta #' . $datos_venta['numero_documento'];
$active_menu = 'ventas';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-pencil-square"></i> Editar Boleta de Venta</h1>
    <p>Boleta #<?= h($datos_venta['numero_documento']) ?> (ID: <?= $id_venta ?>)</p>
</div>

<div class="card-erp" style="max-width:640px;">
    <div class="card-erp-body">
        <form method="POST" action="<?= url('modules/ventas/editar_boleta.php?id=' . $id_venta) ?>">
            <div class="field-group">
                <label>Número de Boleta</label>
                <input type="text" class="form-control" name="numero_documento" value="<?= h($datos_venta['numero_documento']) ?>" required>
            </div>
            <div class="field-group">
                <label>Fecha y Hora</label>
                <?php $fecha_formato = date('Y-m-d\TH:i', strtotime($datos_venta['fecha'])); ?>
                <input type="datetime-local" class="form-control" name="fecha" value="<?= $fecha_formato ?>" required>
            </div>
            <div class="field-group">
                <label>Nombre del Cliente</label>
                <input type="text" class="form-control" name="nombre_cliente" value="<?= h($datos_venta['nombre_cliente']) ?>" required>
            </div>
            <div class="field-group">
                <label>Total S/</label>
                <input type="number" step="0.01" class="form-control" name="total_venta" value="<?= number_format($datos_venta['total_venta'], 2, '.', '') ?>" required>
            </div>
            <div class="field-group">
                <label>Método de Pago</label>
                <?php $mp = $datos_venta['metodo_pago'] ?: 'Efectivo'; ?>
                <select name="metodo_pago" class="form-select">
                    <?php foreach (['Efectivo', 'Tarjeta', 'Yape / Plin', 'Transferencia'] as $opcion): ?>
                        <option value="<?= h($opcion) ?>" <?= $mp === $opcion ? 'selected' : '' ?>><?= h($opcion) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                <a href="<?= url('modules/ventas/lista_boletas.php') ?>" class="btn btn-secondary">Volver al Listado</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
