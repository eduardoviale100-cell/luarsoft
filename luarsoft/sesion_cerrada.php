<?php
/**
 * sesion_cerrada.php
 * ------------------------------------------------------------------
 * Pantalla que se muestra justo después de cerrar sesión en LuarSoft.
 * No requiere sesión activa (es pública, como el propio login).
 * Ofrece dos caminos: volver a entrar al sistema, o ir a la web
 * pública de Eros Tecnología.
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sesión cerrada · LuarSoft</title>
<link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<style>
  body.pantalla-salida{min-height:100vh; margin:0; display:flex; align-items:center; justify-content:center; background:#F5F3EE; font-family:'Inter',sans-serif;}
  .tarjeta-salida{background:#fff; border-radius:14px; padding:40px 32px; max-width:360px; width:90%; text-align:center; box-shadow:0 10px 30px rgba(15,29,56,0.1);}
</style>
</head>
<body class="pantalla-salida">
  <div class="tarjeta-salida">
    <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros Tecnología" style="width:64px; height:64px; border-radius:50%; object-fit:cover; margin:0 auto 16px;">
    <h1 style="margin-bottom:6px;">Sesión cerrada</h1>
    <p style="color:var(--n-500,#6b7280); margin-bottom:28px;">Gracias por usar el sistema. ¿A dónde quieres ir ahora?</p>

    <a href="<?= url('login.php') ?>" style="display:block; width:100%; padding:13px; border-radius:8px; background:#0F1D38; color:#fff; font-weight:600; text-decoration:none; margin-bottom:12px;">
      Volver a iniciar sesión en el sistema
    </a>
    <a href="../web/index.php" style="display:block; width:100%; padding:13px; border-radius:8px; background:#3B6FE0; color:#fff; font-weight:600; text-decoration:none;">
      Ir a la página web de Eros Tecnología
    </a>
  </div>
</body>
</html>
