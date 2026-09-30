<?php
/**
 * modules/tecnicos/eliminar.php — Reemplaza a "eliminar_tecnico.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmtImg = mysqli_prepare($conexion, "SELECT foto FROM tecnicos WHERE id_tecnico = ?");
    mysqli_stmt_bind_param($stmtImg, "i", $id);
    mysqli_stmt_execute($stmtImg);
    $fotoActual = mysqli_stmt_get_result($stmtImg)->fetch_assoc()['foto'] ?? null;

    $stmt = mysqli_prepare($conexion, "DELETE FROM tecnicos WHERE id_tecnico = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        eliminarImagenReferencia($fotoActual, 'tecnicos');
        flash('success', 'Técnico eliminado correctamente.');
    } else {
        flash('error', 'Error al eliminar el técnico.');
    }
} else {
    flash('error', 'ID de técnico no válido.');
}

header('Location: ' . url('modules/tecnicos/listado.php'));
exit;
