<?php
/**
 * master/cambiar_estado.php — Alternar Estado de Negocio (Activo / Suspendido)
 */
require_once __DIR__ . '/includes/auth_master.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $nuevoEstado = trim($_POST['estado'] ?? $_GET['estado'] ?? '');

    if ($id > 0 && in_array($nuevoEstado, ['activo', 'suspendido'], true)) {
        $stmt = mysqli_prepare($conexionMaster, "UPDATE sys_negocios SET estado = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $nuevoEstado, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['master_flash'] = [
                'tipo' => 'success',
                'mensaje' => 'El estado del negocio ha sido actualizado a ' . strtoupper($nuevoEstado) . ' correctamente.'
            ];
        }
    }
}

header('Location: index.php');
exit;
