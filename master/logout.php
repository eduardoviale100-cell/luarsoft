<?php
/**
 * master/logout.php — Cerrar Sesión SuperAdmin
 */
require_once __DIR__ . '/config/conexion_master.php';

unset($_SESSION['superadmin_id'], $_SESSION['superadmin_nombre'], $_SESSION['superadmin_usuario']);
header('Location: login.php');
exit;
