<?php
/**
 * includes/auth.php — Guardián de Autenticación y Verificación de Permisos por Tenant
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';

// ---------------------------------------------------------------
// 1. VERIFICACIÓN ANTI-BYPASS DE ESTADO SUSPENDIDO
// ---------------------------------------------------------------
if (defined('TENANT_ESTADO') && TENANT_ESTADO === 'suspendido') {
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
    http_response_code(403);
    die("Servicio suspendido para esta empresa.");
}

// ---------------------------------------------------------------
// 2. VERIFICACIÓN DE PERTENENCIA DE SESIÓN AL TENANT ACTUAL
// ---------------------------------------------------------------
if (!empty($_SESSION['session_tenant_slug']) && $_SESSION['session_tenant_slug'] !== TENANT_SLUG) {
    // La sesión activa pertenece a otro negocio: destruir credenciales del tenant anterior
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
    header('Location: ' . url('login.php?empresa=' . urlencode(TENANT_SLUG)));
    exit;
}

if (empty($_SESSION['usuario'])) {
    header('Location: ' . url('login.php?empresa=' . urlencode(TENANT_SLUG)));
    exit;
}

require_once __DIR__ . '/permisos.php';

if (!isset($_SESSION['rol']) && !empty($_SESSION['id_usuario'])) {
    $stmtSesion = mysqli_prepare($conexion, "SELECT rol, permisos FROM usuarios WHERE id_usuario = ? LIMIT 1");
    if ($stmtSesion) {
        mysqli_stmt_bind_param($stmtSesion, "i", $_SESSION['id_usuario']);
        mysqli_stmt_execute($stmtSesion);
        $resSesion = mysqli_stmt_get_result($stmtSesion);
        $filaSesion = $resSesion ? mysqli_fetch_assoc($resSesion) : null;
        mysqli_stmt_close($stmtSesion);
        $_SESSION['rol'] = $filaSesion['rol'] ?? 'Administrador';
        $_SESSION['permisos'] = decodificarPermisos($filaSesion['permisos'] ?? null);
    }
}

verificarPermisoActual();
