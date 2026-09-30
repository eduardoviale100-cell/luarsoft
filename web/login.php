<?php
require_once __DIR__ . '/config/conexion_web.php';
require_once __DIR__ . '/includes/funciones_web.php';

$tituloPagina = 'Ingresar';
$error = '';

if (esAdmin() || esClienteActivo()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = limpiar($_POST['usuario'] ?? '');
    $clave   = (string)($_POST['clave'] ?? '');

    if ($usuario === '' || $clave === '') {
        $error = 'Ingresa tu usuario/teléfono y tu contraseña.';
    } else {
        // 1) ¿Es un usuario del staff (admin/técnico)?
        $stmt = mysqli_prepare($conexion, "SELECT id_usuario, usuario, contraseña, rol, permisos FROM usuarios WHERE usuario = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $usuario);
        mysqli_stmt_execute($stmt);
        $filaAdmin = mysqli_stmt_get_result($stmt)->fetch_assoc();

        if ($filaAdmin && password_verify($clave, $filaAdmin['contraseña'])) {
            session_regenerate_id(true);
            $_SESSION['usuario']    = $filaAdmin['usuario'];
            $_SESSION['id_usuario'] = $filaAdmin['id_usuario'];
            $_SESSION['rol']        = $filaAdmin['rol'] ?? 'Administrador';
            header('Location: index.php');
            exit;
        }

        // 2) ¿Es un cliente registrado desde la web? (por teléfono o por correo)
        $stmt2 = mysqli_prepare($conexion, "SELECT id_cliente_web, nombre, password_hash, estado FROM clientes_web WHERE telefono = ? OR correo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt2, "ss", $usuario, $usuario);
        mysqli_stmt_execute($stmt2);
        $filaCliente = mysqli_stmt_get_result($stmt2)->fetch_assoc();

        if ($filaCliente && password_verify($clave, $filaCliente['password_hash'])) {
            if ($filaCliente['estado'] !== 'activo') {
                $error = 'Tu cuenta todavía está pendiente de aprobación. Te avisaremos por WhatsApp en cuanto esté lista.';
            } else {
                session_regenerate_id(true);
                $_SESSION['cliente_web_id']     = $filaCliente['id_cliente_web'];
                $_SESSION['cliente_web_nombre'] = $filaCliente['nombre'];
                $_SESSION['cliente_web_estado'] = $filaCliente['estado'];
                header('Location: index.php');
                exit;
            }
        } elseif ($error === '') {
            $error = 'Teléfono o contraseña incorrectos.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section>
  <div class="wrap">
    <div class="form-card">
      <h1>Ingresa a tu cuenta</h1>
      <p class="sub">Eros Tecnología</p>

      <?php if ($error): ?><div class="alerta error"><?= h($error) ?></div><?php endif; ?>

      <form method="POST">
        <div class="campo">
          <label>Teléfono o correo con el que te registraste</label>
          <input type="text" name="usuario" required autofocus placeholder="Ej: 987654321 o tu@correo.com">
        </div>
        <div class="campo">
          <label>Contraseña</label>
          <input type="password" name="clave" required>
        </div>
        <button type="submit" class="btn btn-azul">Ingresar</button>
      </form>
      <p class="enlace">¿Aún no tienes cuenta? <a href="registro.php">Solicitar registro</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
