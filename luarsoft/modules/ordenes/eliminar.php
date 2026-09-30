<?php
/**
 * modules/ordenes/eliminar.php — Reemplaza a "eliminar.php" (órdenes).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmtImg = mysqli_prepare($conexion, "SELECT foto_equipo, evidencia_video FROM ordenes WHERE id_orden = ?");
    mysqli_stmt_bind_param($stmtImg, "i", $id);
    mysqli_stmt_execute($stmtImg);
    $archivosActuales = mysqli_stmt_get_result($stmtImg)->fetch_assoc();

    $stmt = mysqli_prepare($conexion, "DELETE FROM ordenes WHERE id_orden = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    eliminarImagenReferencia($archivosActuales['foto_equipo'] ?? null, 'ordenes');
    eliminarImagenReferencia($archivosActuales['evidencia_video'] ?? null, 'evidencias');

    flash('success', 'Orden eliminada correctamente.');
} else {
    flash('error', 'ID de orden no válido.');
}

header('Location: ' . url('modules/ordenes/listado.php'));
exit;
