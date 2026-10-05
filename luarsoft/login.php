<?php
/**
 * login.php — Login ERP con Auto-Login Inteligente y Bloqueo Anti-Bypass
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/permisos.php';

$error = '';

// ---------------------------------------------------------------
// 1. BLOQUEO ESTRICTO POR ESTADO SUSPENDIDO (ANTI-BYPASS)
// ---------------------------------------------------------------
if (defined('TENANT_ESTADO') && TENANT_ESTADO === 'suspendido') {
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
    http_response_code(403);
    die("Servicio suspendido para esta empresa.");
}

// ---------------------------------------------------------------
// 2. AISLAMIENTO DE SESIÓN DE TENANT
// Si hay una sesión activa pero pertenece a OTRO tenant diferente, limpiarla
// ---------------------------------------------------------------
if (!empty($_SESSION['session_tenant_slug']) && $_SESSION['session_tenant_slug'] !== TENANT_SLUG) {
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
}

// Si ya hay sesión válida iniciada para ESTE tenant, ir directo al panel principal
if (!empty($_SESSION['usuario']) && (!isset($_SESSION['session_tenant_slug']) || $_SESSION['session_tenant_slug'] === TENANT_SLUG)) {
    header('Location: ' . url('index.php'));
    exit;
}

// ---------------------------------------------------------------
// 3. AUTO-LOGIN INTELIGENTE DESDE EL PANEL MASTER
// ---------------------------------------------------------------
$autoUser = limpiar($_GET['user'] ?? ($_GET['auto_user'] ?? ''));
$autoPass = (string)($_GET['pass'] ?? ($_GET['auto_pass'] ?? ''));

if ($autoUser !== '' && $autoPass !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmtAuto = mysqli_prepare($conexion, "SELECT id_usuario, usuario, contraseña, rol, permisos FROM usuarios WHERE usuario = ? LIMIT 1");
    if ($stmtAuto) {
        mysqli_stmt_bind_param($stmtAuto, "s", $autoUser);
        mysqli_stmt_execute($stmtAuto);
        $resAuto = mysqli_stmt_get_result($stmtAuto);
        $filaAuto = $resAuto ? mysqli_fetch_assoc($resAuto) : null;
        mysqli_stmt_close($stmtAuto);

        if ($filaAuto && password_verify($autoPass, $filaAuto['contraseña'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $filaAuto['usuario'];
            $_SESSION['id_usuario'] = $filaAuto['id_usuario'];
            $_SESSION['rol'] = $filaAuto['rol'] ?? 'Administrador';
            $_SESSION['permisos'] = decodificarPermisos($filaAuto['permisos'] ?? null);
            $_SESSION['session_tenant_slug'] = TENANT_SLUG;

            header('Location: ' . url('index.php'));
            exit;
        }
    }
}

// ---------------------------------------------------------------
// 4. INICIO DE SESIÓN MANUAL (POST)
// ---------------------------------------------------------------
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
            $_SESSION['session_tenant_slug'] = TENANT_SLUG;

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
    <title>Iniciar Sesión · <?= h(TENANT_NOMBRE) ?></title>
    <link rel="icon" href="<?= url(TENANT_LOGO) ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<?php 
$bgStyle = (TENANT_SLUG === 'eros') 
    ? "background-image: url('" . url('assets/img/tienda-eros.jpg') . "');" 
    : "background: radial-gradient(circle at top left, #1A2640, #0B132B);";
?>
<div class="login-hero" style="<?= $bgStyle ?>">

    <div class="login-hero-topbar">
        <img src="<?= url(TENANT_LOGO) ?>" alt="<?= h(TENANT_NOMBRE) ?>" onerror="this.src='<?= url('assets/img/eros.jpg') ?>'">
        <span><?= h(TENANT_NOMBRE) ?></span>
    </div>

    <div class="login-hero-center">
        <div class="login-hero-title">
            <h1><?= h(mb_strtoupper(TENANT_NOMBRE)) ?></h1>
            <p>"Sistema de Gestión Empresarial LuarSoft"</p>
        </div>

        <div class="login-hero-card">
            <img src="<?= url(TENANT_LOGO) ?>" alt="<?= h(TENANT_NOMBRE) ?>" onerror="this.src='<?= url('assets/img/eros.jpg') ?>'">
            <h3>INICIAR <span>SESIÓN</span></h3>
            <p class="subtitle">Bienvenido al panel de <?= h(TENANT_NOMBRE) ?></p>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center py-2" style="border-radius: var(--radius-sm); font-size:0.85rem;">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('login.php?empresa=' . urlencode(TENANT_SLUG)) ?>">
                <div class="field-group">
                    <label class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="usuario" class="form-control" placeholder="Tu usuario" value="<?= h($autoUser) ?>" required autofocus>
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
        © <?= date('Y') ?> <?= h(TENANT_NOMBRE) ?> · Plataforma LuarSoft SaaS
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
