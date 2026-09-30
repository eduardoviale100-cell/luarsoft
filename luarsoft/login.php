<?php
/**
 * login.php
 * ------------------------------------------------------------------
 * Página de inicio de sesión. Sustituye a login.html + login.php del
 * sistema original (que estaban desconectados entre sí: el formulario
 * enviaba los datos a "1.html" en vez de a login.php). Aquí el mismo
 * archivo muestra el formulario (GET) y procesa el envío (POST),
 * usando sentencias preparadas para evitar inyección SQL.
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/permisos.php';

$error = '';

// Si ya hay sesión iniciada, ir directo al panel principal
if (!empty($_SESSION['usuario'])) {
    header('Location: ' . url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = limpiar($_POST['usuario'] ?? '');
    $clave   = (string)($_POST['contraseña'] ?? ($_POST['contrasena'] ?? ''));
    $recordarme = !empty($_POST['recordarme']);

    if ($usuario === '' || $clave === '') {
        $error = 'Debes ingresar usuario y contraseña.';
    } else {
        $stmt = mysqli_prepare($conexion, "SELECT id_usuario, usuario, contraseña, rol, permisos FROM usuarios WHERE usuario = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $usuario);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;

        if ($fila && password_verify($clave, $fila['contraseña'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $fila['usuario'];
            $_SESSION['id_usuario'] = $fila['id_usuario'];
            $_SESSION['rol'] = $fila['rol'] ?? 'Administrador';
            $_SESSION['permisos'] = decodificarPermisos($fila['permisos'] ?? null);

            // "Recordarme": extiende la cookie de sesión a 30 días (por defecto
            // dura solo hasta cerrar el navegador) y el tiempo de vida en el
            // servidor, para no tener que iniciar sesión de nuevo cada rato.
            if ($recordarme) {
                $duracion = 60 * 60 * 24 * 30; // 30 días
                ini_set('session.gc_maxlifetime', (string)$duracion);
                setcookie(session_name(), session_id(), time() + $duracion, '/');
            }

            header('Location: ' . url('index.php'));
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · LuarSoft - Eros Tecnología</title>
    <link rel="icon" href="<?= url('assets/img/eros.jpg') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<div class="login-hero" style="background-image: url('<?= url('assets/img/tienda-eros.jpg') ?>');">

    <div class="login-hero-topbar">
        <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros">
        <span>Multiservicios Eros</span>
    </div>

    <div class="login-hero-center">
        <div class="login-hero-title">
            <h1>MULTISERVICIOS <span>EROS</span></h1>
            <p>"Soluciones rápidas y eficientes para tu equipo"</p>
        </div>

        <div class="login-hero-card">
            <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros Tecnología">
            <h3>INICIAR <span>SESIÓN</span></h3>
            <p class="subtitle">Bienvenido al panel de LuarSoft</p>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center py-2" style="border-radius: var(--radius-sm); font-size:0.85rem;">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('login.php') ?>">
                <div class="field-group">
                    <label class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="usuario" class="form-control" placeholder="Tu usuario" required autofocus>
                    </div>
                </div>
                <div class="field-group">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group has-eye">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="contraseña" id="inputClave" class="form-control" placeholder="Tu contraseña" required>
                        <button type="button" class="btn btn-eye" onclick="togglePasswordLogin()" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                            <i class="bi bi-eye" id="iconoOjoClave"></i>
                        </button>
                    </div>
                </div>

                <div class="login-hero-row">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="checkRecordarme" name="recordarme" value="1">
                        <label class="form-check-label" for="checkRecordarme">Recordarme</label>
                    </div>
                    <button type="button" class="login-forgot-link" data-bs-toggle="modal" data-bs-target="#modalOlvideClave">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>

                <button type="submit" class="btn-submit-login">
                    INICIAR SESIÓN <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="login-hero-footer">
        © <?= date('Y') ?> Multiservicios Eros · Sistema desarrollado por <strong>CWSSy</strong> | LuarSoft
    </div>
</div>

<!-- Modal informativo: recuperación de contraseña -->
<div class="modal fade" id="modalOlvideClave" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-key"></i> ¿Olvidaste tu contraseña?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Por seguridad, las contraseñas de LuarSoft no se recuperan automáticamente desde esta pantalla.</p>
        <p>Comunícate con el <strong>Administrador del sistema</strong> para que restablezca tu acceso desde
        <em>Sistema &gt; Usuarios</em>.</p>
        <div class="d-flex align-items-center gap-2 mt-3 p-2" style="background:var(--n-100); border-radius:8px;">
            <i class="bi bi-whatsapp" style="font-size:1.3rem; color:#25D366;"></i>
            <div>
                <div class="fw-bold" style="font-size:0.9rem;">Contacto: Eros Tecnología</div>
                <div class="text-muted" style="font-size:0.85rem;">Cel. 949 092 352</div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script>
function togglePasswordLogin() {
    const input = document.getElementById('inputClave');
    const icono = document.getElementById('iconoOjoClave');
    const mostrar = input.type === 'password';
    input.type = mostrar ? 'text' : 'password';
    icono.classList.toggle('bi-eye', !mostrar);
    icono.classList.toggle('bi-eye-slash', mostrar);
}
</script>
</body>
</html>
