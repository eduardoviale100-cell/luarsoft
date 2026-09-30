<?php
require_once __DIR__ . '/config/conexion_web.php';
require_once __DIR__ . '/includes/funciones_web.php';

$tituloPagina = 'Seguimiento de equipo';
$orden = null;
$buscado = false;

$mapaEstados = [
    'Ingresado'    => 'estado-ingresado',
    'Diagnóstico'  => 'estado-diagnostico',
    'En reparación'=> 'estado-reparacion',
    'Listo'        => 'estado-listo',
    'Entregado'    => 'estado-entregado',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buscado  = true;
    $idOrden  = (int)($_POST['id_orden'] ?? 0);
    $telefono = limpiar($_POST['telefono'] ?? '');

    if ($idOrden > 0 && $telefono !== '') {
        $stmt = mysqli_prepare($conexion, "SELECT id_orden, cliente, tipo_equipo, marca, problema, estado, fecha_ingreso, fecha_entrega FROM ordenes WHERE id_orden = ? AND telefono_cliente = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "is", $idOrden, $telefono);
        mysqli_stmt_execute($stmt);
        $orden = mysqli_stmt_get_result($stmt)->fetch_assoc();
    }
}

require __DIR__ . '/includes/header.php';
?>

<section>
  <div class="wrap">
    <div class="form-card">
      <h1>Seguimiento de tu equipo</h1>
      <p class="sub">Ingresa el número de orden y el teléfono con el que lo dejaste.</p>

      <form method="POST">
        <div class="campo">
          <label>N° de orden</label>
          <input type="number" name="id_orden" required value="<?= h($_POST['id_orden'] ?? '') ?>">
        </div>
        <div class="campo">
          <label>Teléfono</label>
          <input type="text" name="telefono" required value="<?= h($_POST['telefono'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-azul">Consultar estado</button>
      </form>
    </div>

    <?php if ($buscado): ?>
      <?php if ($orden): ?>
        <?php $claseEstado = $mapaEstados[$orden['estado']] ?? 'estado-ingresado'; ?>
        <div class="orden-resultado">
          <span class="estado <?= $claseEstado ?>"><?= h($orden['estado']) ?></span>
          <dl>
            <dt>Orden</dt><dd>#<?= h($orden['id_orden']) ?></dd>
            <dt>Equipo</dt><dd><?= h($orden['tipo_equipo'] ?: '—') ?></dd>
            <dt>Marca</dt><dd><?= h($orden['marca'] ?: '—') ?></dd>
            <dt>Ingresado</dt><dd><?= h($orden['fecha_ingreso']) ?></dd>
            <dt>Problema reportado</dt><dd style="grid-column:1/-1;"><?= h($orden['problema']) ?></dd>
          </dl>
        </div>
      <?php else: ?>
        <div class="alerta error" style="max-width:480px; margin:20px auto 0;">No encontramos ninguna orden con esos datos. Verifica el número y el teléfono.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
