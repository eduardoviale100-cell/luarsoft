<?php
/**
 * master/includes/auth_master.php — Verificación de Sesión SuperAdmin
 */

require_once __DIR__ . '/../config/conexion_master.php';

if (empty($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}
