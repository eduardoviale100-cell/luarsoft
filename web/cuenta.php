<?php
require_once __DIR__ . '/config/conexion_web.php';
require_once __DIR__ . '/includes/funciones_web.php';

$tituloPagina = 'Mi cuenta';

if (!esClienteActivo() && !esAdmin()) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/includes/header.php';
?>

<section>
  <div class="wrap">
    <div class="section-head">
      <h2>Hola, <?= h($_SESSION['cliente_web_nombre'] ?? $_SESSION['usuario'] ?? '') ?> 👋</h2>
      <p>Desde aquí puedes revisar el estado de tus equipos y ver el catálogo completo.</p>
    </div>
    <a href="seguimiento.php" class="btn btn-azul">Ver seguimiento de mi equipo</a>
    <a href="index.php" class="btn btn-outline-oscuro" style="margin-left:10px;">Ir al catálogo</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
