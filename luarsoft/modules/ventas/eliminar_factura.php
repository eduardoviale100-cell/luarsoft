<?php
/**
 * modules/ventas/eliminar_factura.php — Reemplaza a "eliminar_factura.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    flash('error', 'ID de venta no especificado.');
    header('Location: ' . url('modules/ventas/lista_facturas.php'));
    exit;
}
$id_venta = (int)$_GET['id'];

mysqli_begin_transaction($conexion);
$delete_success = false;

try {
    $del1 = mysqli_prepare($conexion, "DELETE FROM detalle_venta WHERE id_venta = ?");
    mysqli_stmt_bind_param($del1, "i", $id_venta);
    mysqli_stmt_execute($del1);

    $del2 = mysqli_prepare($conexion, "DELETE FROM ventas WHERE id_venta = ? AND tipo_documento = 'FACTURA'");
    mysqli_stmt_bind_param($del2, "i", $id_venta);

    if (mysqli_stmt_execute($del2)) {
        if (mysqli_stmt_affected_rows($del2) > 0) {
            mysqli_commit($conexion);
            $delete_success = true;
        } else {
            mysqli_rollback($conexion);
        }
    } else {
        throw new Exception(mysqli_error($conexion));
    }
} catch (Exception $e) {
    mysqli_rollback($conexion);
}

flash($delete_success ? 'success' : 'error', $delete_success ? 'Factura eliminada correctamente.' : 'No se pudo eliminar la factura.');
header('Location: ' . url('modules/ventas/lista_facturas.php'));
exit;
