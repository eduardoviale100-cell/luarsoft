<?php
/**
 * master/login.php — Login del Panel Master (SuperAdmin)
 */
require_once __DIR__ . '/config/conexion_master.php';

$error = '';

if (!empty($_SESSION['superadmin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingresa usuario y contraseña.';
    } else {
        $stmt = mysqli_prepare($conexionMaster, "SELECT id, nombre, usuario, email, password, estado FROM sys_superadmins WHERE usuario = ? OR email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $usuario, $usuario);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && $row = mysqli_fetch_assoc($res)) {
                if ($row['estado'] !== 'activo') {
                    $error = 'Tu cuenta de SuperAdmin ha sido inhabilitada.';
                } elseif (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['superadmin_id'] = $row['id'];
                    $_SESSION['superadmin_nombre'] = $row['nombre'];
                    $_SESSION['superadmin_usuario'] = $row['usuario'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Credenciales incorrectas.';
                }
            } else {
                $error = 'Credenciales incorrectas.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Master · LuarSoft SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: radial-gradient(circle at top left, #1A2640, #0B132B); color: #E0E6ED; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.12); backdrop-filter: blur(16px); border-radius: 20px; padding: 40px; width: 100%; max-width: 440px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); }
        .brand-icon { font-size: 3rem; color: #1FA35C; }
        .form-control { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 12px 16px; border-radius: 10px; }
        .form-control:focus { background: rgba(255,255,255,0.12); border-color: #1FA35C; color: #fff; box-shadow: 0 0 0 0.25rem rgba(31, 163, 92, 0.25); }
        .form-control::placeholder { color: #8C9BAE; }
        .btn-master { background: linear-gradient(135deg, #1FA35C, #157A43); border: none; color: #fff; font-weight: 700; padding: 14px; border-radius: 10px; width: 100%; transition: all 0.3s; margin-top: 10px; }
        .btn-master:hover { background: linear-gradient(135deg, #24BF6C, #1FA35C); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(31,163,92,0.4); }
    </style>
</head>
<body>

<div class="login-box">
    <div class="text-center mb-4">
        <i class="bi bi-buildings-fill brand-icon"></i>
        <h3 class="fw-bold mt-2 text-white">LuarSoft <span style="color:#1FA35C;">Master</span></h3>
        <p class="text-secondary small">Panel de Administración SaaS Multi-Tenant</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 text-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= h_master($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label text-secondary small fw-semibold">Usuario o Email Master</label>
            <div class="input-group">
                <input type="text" name="usuario" class="form-control" placeholder="adminmaster" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label text-secondary small fw-semibold">Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-master">
            INGRESAR AL PANEL MASTER <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>

    <div class="text-center mt-4 text-muted small">
        © <?= date('Y') ?> LuarSoft SaaS · Gestión Global de Empresas
    </div>
</div>

</body>
</html>
