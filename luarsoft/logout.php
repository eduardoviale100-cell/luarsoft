<?php
/**
 * logout.php
 * ------------------------------------------------------------------
 * Cierra la sesión del usuario. Se mantiene el comportamiento
 * original (destruir la sesión); ahora redirige a sesion_cerrada.php,
 * que ofrece volver a entrar o ir a la web pública.
 */
session_start();
session_unset();
session_destroy();

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
header('Location: ' . url('sesion_cerrada.php'));
exit;
