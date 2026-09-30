<?php
define('WEB_ROOT', '../');
require_once __DIR__ . '/../config/conexion_web.php';
require_once __DIR__ . '/../includes/funciones_web.php';

$tituloPagina = 'Solicitudes de registro';

// Solo el staff (tabla usuarios) puede entrar aquí.
if (!esAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Aprobar / rechazar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($id > 0 && in_array($accion, ['activo', 'rechazado'], true)) {
        $stmt = mysqli_prepare($conexion, "UPDATE clientes_web SET estado = ?, fecha_aprobacion = NOW() WHERE id_cliente_web = ?");
        mysqli_stmt_bind_param($stmt, "si", $accion, $id);
        mysqli_stmt_execute($stmt);
        flash('exito', $accion === 'activo' ? 'Cuenta aprobada. El cliente ya puede ingresar.' : 'Solicitud rechazada.');
    }
    header('Location: solicitudes.php');
    exit;
}

$solicitudes = [];
$res = mysqli_query($conexion, "SELECT id_cliente_web, nombre, telefono, correo, estado, fecha_registro FROM clientes_web ORDER BY (estado = 'pendiente') DESC, fecha_registro DESC");
while ($fila = mysqli_fetch_assoc($res)) {
    $solicitudes[] = $fila;
}

require __DIR__ . '/../includes/header.php';
?>

<section>
  <div class="wrap">
    <div class="section-head">
      <h2>Solicitudes de registro</h2>
      <p>Clientes que se registraron desde la web pública y esperan validación.</p>
    </div>

    <?php if (empty($solicitudes)): ?>
      <p style="color:var(--gris); font-size:14px;">Todavía no hay solicitudes.</p>
    <?php else: ?>
      <table class="tabla-admin">
        <thead>
          <tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr>
        </thead>
        <tbody>
        <?php foreach ($solicitudes as $s): ?>
          <tr>
            <td><?= h($s['nombre']) ?></td>
            <td><?= h($s['telefono']) ?></td>
            <td><?= h($s['correo'] ?: '—') ?></td>
            <td><span class="estado-pill pill-<?= h($s['estado']) ?>"><?= h(ucfirst($s['estado'])) ?></span></td>
            <td><?= h($s['fecha_registro']) ?></td>
            <td>
              <?php if ($s['estado'] === 'pendiente'): ?>
                <form method="POST" class="acciones-form">
                  <input type="hidden" name="id" value="<?= (int)$s['id_cliente_web'] ?>">
                  <button type="submit" name="accion" value="activo" class="btn-aprobar">Aprobar</button>
                  <button type="submit" name="accion" value="rechazado" class="btn-rechazar">Rechazar</button>
                </form>
              <?php else: ?>
                <span style="color:var(--gris); font-size:12.5px;">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
