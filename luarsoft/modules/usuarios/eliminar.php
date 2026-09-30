<?php
/**
 * modules/usuarios/eliminar.php — NUEVO.
 * Protecciones básicas: no se puede eliminar el último usuario del
 * sistema (te quedarías sin forma de iniciar sesión) ni la cuenta
 * con la que se tiene la sesión abierta actualmente.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flash('error', 'ID de usuario no válido.');
    header('Location: ' . url('modules/usuarios/listado.php'));
    exit;
}

if (!empty($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] === $id) {
    flash('error', 'No puedes eliminar el usuario con el que tienes la sesión abierta.');
    header('Location: ' . url('modules/usuarios/listado.php'));
    exit;
}

$totalUsuarios = 0;
if ($res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios")) {
    $totalUsuarios = (int)mysqli_fetch_assoc($res)['total'];
}

if ($totalUsuarios <= 1) {
    flash('error', 'Debe existir al menos un usuario en el sistema.');
    header('Location: ' . url('modules/usuarios/listado.php'));
    exit;
}

$stmtImg = mysqli_prepare($conexion, "SELECT foto FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmtImg, "i", $id);
mysqli_stmt_execute($stmtImg);
$fotoActual = mysqli_stmt_get_result($stmtImg)->fetch_assoc()['foto'] ?? null;

$stmt = mysqli_prepare($conexion, "DELETE FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    eliminarImagenReferencia($fotoActual, 'usuarios');
    flash('success', 'Usuario eliminado correctamente.');
} else {
    flash('error', 'Error al eliminar el usuario.');
}

header('Location: ' . url('modules/usuarios/listado.php'));
exit;
