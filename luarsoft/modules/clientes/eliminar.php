<?php
/**
 * modules/clientes/eliminar.php — Reemplaza a "eliminar_cliente.php".
 * Se agrega validación de ID entero y sentencia preparada (el
 * original tomaba $_GET['id'] directo en el DELETE sin sanear).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmtImg = mysqli_prepare($conexion, "SELECT foto FROM clientes WHERE id_cliente = ?");
    mysqli_stmt_bind_param($stmtImg, "i", $id);
    mysqli_stmt_execute($stmtImg);
    $fotoActual = mysqli_stmt_get_result($stmtImg)->fetch_assoc()['foto'] ?? null;

    $stmt = mysqli_prepare($conexion, "DELETE FROM clientes WHERE id_cliente = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        eliminarImagenReferencia($fotoActual, 'clientes');
        flash('success', 'Cliente eliminado correctamente.');
    } else {
        flash('error', 'Error al eliminar el cliente.');
    }
} else {
    flash('error', 'ID de cliente no válido.');
}

header('Location: ' . url('modules/clientes/listado.php'));
exit;
