<?php
require_once __DIR__ . '/config/conexion_web.php';
require_once __DIR__ . '/includes/funciones_web.php';

$tituloPagina = 'Registro';
$error = '';
$enviado = false;
$linkWhatsapp = '';

// Número del administrador que recibe las solicitudes.
const WHATSAPP_ADMIN = '51949092352';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = limpiar($_POST['nombre'] ?? '');
    $telefono = limpiar($_POST['telefono'] ?? '');
    $correo   = limpiar($_POST['correo'] ?? '');
    $clave    = (string)($_POST['clave'] ?? '');

    if ($nombre === '' || $telefono === '' || $clave === '') {
        $error = 'Nombre, teléfono y contraseña son obligatorios.';
    } elseif (strlen($clave) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres.';
    } else {
        $stmtExiste = mysqli_prepare($conexion, "SELECT id_cliente_web FROM clientes_web WHERE telefono = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtExiste, "s", $telefono);
        mysqli_stmt_execute($stmtExiste);
        if (mysqli_stmt_get_result($stmtExiste)->fetch_assoc()) {
            $error = 'Ese teléfono ya está registrado. Si es tuyo, intenta iniciar sesión.';
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexion, "INSERT INTO clientes_web (nombre, telefono, correo, password_hash, estado) VALUES (?, ?, ?, ?, 'pendiente')");
            mysqli_stmt_bind_param($stmt, "ssss", $nombre, $telefono, $correo, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $enviado = true;
                $mensaje = "Hola, soy {$nombre} ({$telefono}). Acabo de registrarme en la web de Eros Tecnología y quisiera validar mi cuenta.";
                $linkWhatsapp = 'https://wa.me/' . WHATSAPP_ADMIN . '?text=' . rawurlencode($mensaje);
            } else {
                $error = 'No se pudo completar el registro. Intenta de nuevo.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section>
  <div class="wrap">
    <div class="form-card">
      <?php if ($enviado): ?>
        <h1>¡Ya casi! 🎉</h1>
        <p class="sub">Tu cuenta quedó registrada y está pendiente de aprobación.</p>
        <div class="alerta exito">Escríbenos por WhatsApp para validar tu cuenta ahora mismo. En cuanto la aprobemos, podrás ingresar con tu teléfono y la contraseña que elegiste.</div>
        <a href="<?= h($linkWhatsapp) ?>" target="_blank" class="btn btn-azul">Validar por WhatsApp</a>
        <p class="enlace"><a href="login.php">Ya tengo cuenta aprobada, ingresar</a></p>
      <?php else: ?>
        <h1>Solicitar registro</h1>
        <p class="sub">Toma menos de un minuto. Validamos tu cuenta por WhatsApp.</p>

        <?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

        <form method="POST">
          <div class="campo">
            <label>Nombre completo</label>
            <input type="text" name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>">
          </div>
          <div class="campo">
            <label>Teléfono <span style="color:var(--azul); font-weight:600;">(usarás este número para ingresar después)</span></label>
            <input type="tel" name="telefono" required value="<?= h($_POST['telefono'] ?? '') ?>">
          </div>
          <div class="campo">
            <label>Correo <span style="color:var(--gris); font-weight:400;">(opcional — también puedes usarlo para ingresar)</span></label>
            <input type="email" name="correo" value="<?= h($_POST['correo'] ?? '') ?>">
          </div>
          <div class="campo">
            <label>Crea una contraseña</label>
            <input type="password" name="clave" required minlength="4">
          </div>
          <button type="submit" class="btn btn-azul">Crear cuenta</button>
        </form>
        <p class="enlace">¿Ya tienes cuenta? <a href="login.php">Ingresa aquí</a></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
