<?php
/**
 * config/conexion_web.php
 * ------------------------------------------------------------------
 * Conexión de la WEB PÚBLICA a la misma base de datos que usa
 * LuarSoft. En una instalación en producción, cambia DB_USER/DB_PASS
 * por un usuario de MySQL "web_readonly" con permisos SELECT
 * únicamente sobre `productos`, `clientes_web` y `ordenes` — así,
 * aunque haya una falla en la web pública, no se puede tocar el
 * resto del sistema (ventas, usuarios, etc.).
 * ------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permite que páginas en subcarpetas (como web/admin/) definan
// WEB_ROOT = '../' ANTES de incluir este archivo, para que todos los
// enlaces y rutas de esta constante se ajusten automáticamente.
if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', '');
}

// Con la estructura de este ZIP (web/ y luarsoft/ como hermanas),
// desde la raíz de /web/ la ruta relativa es '../luarsoft/'.
define('RUTA_SISTEMA', WEB_ROOT . '../luarsoft/');
define('RUTA_SISTEMA_WEB', RUTA_SISTEMA); // usada en <img src="..."> e enlaces

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'luarsoft');

// Misma clave que sso_login.php dentro de LuarSoft.
define('CLAVE_SSO_COMPARTIDA', 'eros-2026-cambia-esta-clave');

$conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conexion) {
    http_response_code(500);
    die('Error de conexión a la base de datos: ' . mysqli_connect_error());
}
mysqli_set_charset($conexion, 'utf8mb4');
date_default_timezone_set('America/Lima');

/** Genera la URL de acceso directo al sistema para un admin ya logueado en la web. */
function urlAccesoSistema(int $idUsuario): string
{
    $ts = time();
    $token = hash_hmac('sha256', $idUsuario . '|' . $ts, CLAVE_SSO_COMPARTIDA);
    return RUTA_SISTEMA . "sso_login.php?uid={$idUsuario}&ts={$ts}&t={$token}";
}
