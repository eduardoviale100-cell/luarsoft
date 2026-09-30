<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['cliente_web_id'], $_SESSION['cliente_web_nombre'], $_SESSION['cliente_web_estado']);
unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos']);
session_destroy();
header('Location: index.php');
exit;
