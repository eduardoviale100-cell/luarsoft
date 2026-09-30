<?php
/**
 * includes/auth.php
 * ------------------------------------------------------------------
 * Debe incluirse (después de config/conexion.php) en cada página
 * protegida del sistema. Si no hay sesión de usuario activa,
 * redirige al login. Esto no existía de forma explícita en el
 * sistema original (ninguna página validaba la sesión), por lo que
 * es una mejora de seguridad que NO elimina ninguna funcionalidad:
 * simplemente exige haber iniciado sesión antes de operar el ERP,
 * tal como se espera de un sistema con login.
 *
 * Ampliación (Roles y Matriz de Permisos): además de exigir sesión,
 * ahora también verifica que el rol/permisos del usuario en sesión
 * le den acceso al módulo correspondiente al script actual. Un
 * Administrador siempre tiene acceso total; un Cajero / Usuario solo
 * a los módulos marcados en su matriz de permisos. Si intenta entrar
 * escribiendo la URL directamente, se le redirige a una sección a la
 * que sí tenga acceso.
 */

if (empty($_SESSION['usuario'])) {
    header('Location: ' . url('login.php'));
    exit;
}

require_once __DIR__ . '/permisos.php';

// Compatibilidad con sesiones abiertas ANTES de esta actualización (no
// tenían rol/permisos guardados en $_SESSION): se completan una sola vez
// consultando la base de datos, sin cerrar la sesión del usuario.
if (!isset($_SESSION['rol']) && !empty($_SESSION['id_usuario'])) {
    $stmtSesion = mysqli_prepare($conexion, "SELECT rol, permisos FROM usuarios WHERE id_usuario = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtSesion, "i", $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmtSesion);
    $filaSesion = mysqli_stmt_get_result($stmtSesion)->fetch_assoc();
    $_SESSION['rol'] = $filaSesion['rol'] ?? 'Administrador';
    $_SESSION['permisos'] = decodificarPermisos($filaSesion['permisos'] ?? null);
}

verificarPermisoActual();
