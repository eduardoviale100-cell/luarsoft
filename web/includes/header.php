<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tituloPagina) ? h($tituloPagina) . ' · ' : '' ?>Eros Tecnología</title>
<link rel="icon" href="<?= RUTA_SISTEMA_WEB ?>assets/img/eros.jpg">
<link rel="stylesheet" href="<?= WEB_ROOT ?>assets/css/estilo.css">
</head>
<body>

<?php $flash = obtenerFlash(); ?>

<div class="topbar">
  <div class="wrap">
    <div class="topbar-contacto">
      <a href="https://wa.me/51949092352" target="_blank">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8 1-.2.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4c.1-.1.2-.3.2-.4.1-.2 0-.3 0-.4-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5.6.2 1 .4 1.3.5.6.2 1.1.2 1.5.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1 .1-1.2z"/></svg>
        949 092 352
      </a>
      <a href="mailto:contacto@erostecnologia.com">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        contacto@erostecnologia.com
      </a>
    </div>
    <span class="topbar-horario">Lun a sáb 9am–8pm</span>
  </div>
</div>

<nav>
  <div class="wrap">
    <a class="brand" href="<?= WEB_ROOT ?>index.php">
      <img src="<?= RUTA_SISTEMA_WEB ?>assets/img/eros.jpg" alt="Eros Tecnología">
      <span>EROS TECNOLOGÍA</span>
    </a>
    <div class="navlinks">
      <a href="<?= WEB_ROOT ?>index.php#catalogo">Catálogo</a>
      <a href="<?= WEB_ROOT ?>seguimiento.php">Seguimiento de equipo</a>
      <a href="<?= WEB_ROOT ?>index.php#servicios">Servicios</a>
    </div>
    <div class="navcta">
      <?php if (esAdmin()): ?>
        <span class="chip-admin">Admin: <?= h($_SESSION['usuario']) ?></span>
        <a href="<?= h(urlAccesoSistema((int)$_SESSION['id_usuario'])) ?>" class="btn btn-azul">Ir al panel de gestión</a>
        <a href="<?= WEB_ROOT ?>admin/solicitudes.php" class="ingresar">Solicitudes</a>
        <a href="<?= WEB_ROOT ?>logout.php" class="ingresar">Salir</a>
      <?php elseif (esClienteActivo()): ?>
        <a href="<?= WEB_ROOT ?>cuenta.php" class="ingresar">Hola, <?= h($_SESSION['cliente_web_nombre']) ?></a>
        <a href="<?= WEB_ROOT ?>logout.php" class="btn btn-outline-oscuro">Salir</a>
      <?php else: ?>
        <a href="<?= WEB_ROOT ?>login.php" class="ingresar">Ingresar</a>
        <a href="<?= WEB_ROOT ?>registro.php" class="btn btn-azul">Registrarme</a>
      <?php endif; ?>
    </div>
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="menu-movil" id="menuMovil">
    <a href="<?= WEB_ROOT ?>index.php#catalogo">Catálogo</a>
    <a href="<?= WEB_ROOT ?>seguimiento.php">Seguimiento de equipo</a>
    <a href="<?= WEB_ROOT ?>index.php#servicios">Servicios</a>
    <hr>
    <?php if (esAdmin()): ?>
      <span class="chip-admin" style="display:inline-block; margin-bottom:10px;">Admin: <?= h($_SESSION['usuario']) ?></span>
      <a href="<?= h(urlAccesoSistema((int)$_SESSION['id_usuario'])) ?>" class="btn btn-azul" style="display:block; text-align:center; margin-bottom:8px;">Ir al panel de gestión</a>
      <a href="<?= WEB_ROOT ?>admin/solicitudes.php">Solicitudes</a>
      <a href="<?= WEB_ROOT ?>logout.php">Salir</a>
    <?php elseif (esClienteActivo()): ?>
      <a href="<?= WEB_ROOT ?>cuenta.php">Hola, <?= h($_SESSION['cliente_web_nombre']) ?></a>
      <a href="<?= WEB_ROOT ?>logout.php">Salir</a>
    <?php else: ?>
      <a href="<?= WEB_ROOT ?>login.php">Ingresar</a>
      <a href="<?= WEB_ROOT ?>registro.php" class="btn btn-azul" style="display:block; text-align:center;">Registrarme</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($flash): ?>
  <div class="wrap" style="padding-top:16px;">
    <div class="alerta <?= $flash['tipo'] === 'exito' ? 'exito' : 'error' ?>"><?= h($flash['mensaje']) ?></div>
  </div>
<?php endif; ?>
