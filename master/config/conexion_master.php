<?php
/**
 * master/config/conexion_master.php — Conexión a BD Central Master
 * ------------------------------------------------------------------
 * Conecta al Panel Master con la base de datos central luarsoft_master.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('MASTER_DB_HOST', 'localhost');
define('MASTER_DB_USER', 'root');
define('MASTER_DB_PASS', '');
define('MASTER_DB_NAME', 'luarsoft_master');

mysqli_report(MYSQLI_REPORT_OFF);

$conexionMaster = mysqli_connect(MASTER_DB_HOST, MASTER_DB_USER, MASTER_DB_PASS, MASTER_DB_NAME);

if (!$conexionMaster) {
    http_response_code(500);
    die("Error crítico: No se pudo conectar a la base de datos central master (luarsoft_master): " . mysqli_connect_error());
}

mysqli_set_charset($conexionMaster, 'utf8mb4');
date_default_timezone_set('America/Lima');

function h_master($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
