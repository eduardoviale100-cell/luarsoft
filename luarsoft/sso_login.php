<?php
/**
 * sso_login.php
 * ------------------------------------------------------------------
 * Puerta de entrada especial para el administrador que viene desde
 * la web pública (carpeta /web/). Si ambas partes viven en el mismo
 * servidor y dominio, la sesión de PHP ya se comparte sola y esto ni
 * siquiera hace falta (basta un link a index.php). Este archivo es
 * la versión robusta para cuando la web pública y el sistema quedan
 * en dominios o subdominios DISTINTOS: valida un token firmado de un
 * solo uso antes de crear la sesión de administrador.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/permisos.php';

// Debe ser la MISMA clave que usa web/config/conexion_web.php
// para generar el token. Cámbiala por algo propio antes de publicar
// el sitio, y no la subas nunca a un repositorio público.
define('CLAVE_SSO_COMPARTIDA', 'eros-2026-cambia-esta-clave');

$idUsuario = (int)($_GET['uid'] ?? 0);
$timestamp = (int)($_GET['ts'] ?? 0);
$token     = (string)($_GET['t'] ?? '');

$ahora = time();

// El token vale solo 60 segundos: si tardó más en llegar, se rechaza.
if ($idUsuario <= 0 || $timestamp <= 0 || $token === '' || ($ahora - $timestamp) > 60) {
    http_response_code(403);
    die('Enlace de acceso inválido o vencido. Vuelve a intentarlo desde la web.');
}

$tokenEsperado = hash_hmac('sha256', $idUsuario . '|' . $timestamp, CLAVE_SSO_COMPARTIDA);

if (!hash_equals($tokenEsperado, $token)) {
    http_response_code(403);
    die('Enlace de acceso inválido.');
}

// Token válido: buscamos al usuario y creamos su sesión real del sistema,
// exactamente con las mismas variables que usa login.php.
$stmt = mysqli_prepare($conexion, "SELECT id_usuario, usuario, rol, permisos FROM usuarios WHERE id_usuario = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $idUsuario);
mysqli_stmt_execute($stmt);
$fila = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$fila) {
    http_response_code(403);
    die('Usuario no encontrado.');
}

session_regenerate_id(true);
$_SESSION['usuario']    = $fila['usuario'];
$_SESSION['id_usuario'] = $fila['id_usuario'];
$_SESSION['rol']        = $fila['rol'] ?? 'Administrador';
$_SESSION['permisos']   = decodificarPermisos($fila['permisos'] ?? null);

header('Location: ' . url('index.php'));
exit;
