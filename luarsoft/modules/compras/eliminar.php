<?php
/**
 * modules/compras/eliminar.php — NUEVO.
 * Elimina un registro de compra y revierte el stock que había sumado
 * al producto (resta la misma cantidad), dentro de una transacción.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flash('error', 'ID de compra no válido.');
    header('Location: ' . url('modules/compras/listado.php'));
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT producto_codigo, cantidad FROM compras WHERE id_compra = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$compra = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$compra) {
    flash('error', 'Compra no encontrada.');
    header('Location: ' . url('modules/compras/listado.php'));
    exit;
}

mysqli_begin_transaction($conexion);
try {
    $del = mysqli_prepare($conexion, "DELETE FROM compras WHERE id_compra = ?");
    mysqli_stmt_bind_param($del, "i", $id);
    if (!mysqli_stmt_execute($del)) {
        throw new RuntimeException('No se pudo eliminar la compra.');
    }

    $upd = mysqli_prepare($conexion, "UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE codigo = ?");
    mysqli_stmt_bind_param($upd, "is", $compra['cantidad'], $compra['producto_codigo']);
    if (!mysqli_stmt_execute($upd)) {
        throw new RuntimeException('No se pudo revertir el stock del producto.');
    }

    mysqli_commit($conexion);
    flash('success', 'Compra eliminada y stock revertido correctamente.');
} catch (RuntimeException $e) {
    mysqli_rollback($conexion);
    flash('error', $e->getMessage());
}

header('Location: ' . url('modules/compras/listado.php'));
exit;
