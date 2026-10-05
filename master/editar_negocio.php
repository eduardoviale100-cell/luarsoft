<?php
/**
 * master/editar_negocio.php — Editar Configuración de un Negocio (Tenant)
 */
require_once __DIR__ . '/includes/auth_master.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmtSel = mysqli_prepare($conexionMaster, "SELECT id, nombre_comercial, razon_social, ruc, direccion, slug, db_name, admin_user, admin_pass, logo_url, modulos_activos, estado FROM sys_negocios WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtSel, "i", $id);
mysqli_stmt_execute($stmtSel);
$negocio = mysqli_stmt_get_result($stmtSel)->fetch_assoc();
mysqli_stmt_close($stmtSel);

if (!$negocio) {
    header('Location: index.php');
    exit;
}

$modulosDisponibles = [
    'dashboard' => 'Panel Principal / Dashboard',
    'pos'       => 'Punto de Venta (POS)',
    'clientes'  => 'Gestión de Clientes',
    'productos' => 'Catálogo y Control de Productos',
    'tecnicos'  => 'Gestión de Técnicos',
    'ordenes'   => 'Órdenes de Reparación y Servicio',
    'ventas'    => 'Histórico de Ventas y Comprobantes',
    'reportes'  => 'Reportes y Métricas Financieras',
    'usuarios'  => 'Administración de Usuarios Internos',
    'compras'   => 'Registro de Compras de Mercadería',
];

$modulosActuales = json_decode($negocio['modulos_activos'] ?? '[]', true) ?: [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $razon_social    = trim($_POST['razon_social'] ?? '');
    $ruc             = trim($_POST['ruc'] ?? '');
    $direccion       = trim($_POST['direccion'] ?? '');
    $admin_user      = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass      = trim($_POST['admin_pass'] ?? 'Admin123*');
    $estado          = trim($_POST['estado'] ?? 'activo');
    $modulosSel      = $_POST['modulos'] ?? [];

    if (empty($nombre_comercial) || empty($razon_social) || empty($ruc) || empty($admin_user) || empty($admin_pass)) {
        $error = 'Nombre Comercial, Razón Social, RUC y Credenciales de Administrador son requeridos.';
    } elseif (strlen($ruc) !== 11 || !is_numeric($ruc)) {
        $error = 'El RUC debe tener exactamente 11 dígitos numéricos.';
    } else {
        // Verificar RUC duplicado excluyendo este registro
        $stmtChk = mysqli_prepare($conexionMaster, "SELECT id FROM sys_negocios WHERE ruc = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChk, "si", $ruc, $id);
        mysqli_stmt_execute($stmtChk);
        $resChk = mysqli_stmt_get_result($stmtChk);

        if ($resChk && mysqli_num_rows($resChk) > 0) {
            $error = 'El RUC ingresado ya pertenece a otro negocio.';
        } else {
            $logo_url = $negocio['logo_url'];
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dirUpload = __DIR__ . '/../luarsoft/uploads/logos/';
                    if (!is_dir($dirUpload)) { mkdir($dirUpload, 0755, true); }
                    $nombreFoto = 'logo_' . $negocio['slug'] . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dirUpload . $nombreFoto)) {
                        $logo_url = 'uploads/logos/' . $nombreFoto;
                    }
                }
            }

            $modulosJson = json_encode(array_values(array_intersect($modulosSel, array_keys($modulosDisponibles))));

            $stmtUpd = mysqli_prepare($conexionMaster, "UPDATE sys_negocios SET nombre_comercial = ?, razon_social = ?, ruc = ?, direccion = ?, admin_user = ?, admin_pass = ?, logo_url = ?, modulos_activos = ?, estado = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUpd, "sssssssssi", $nombre_comercial, $razon_social, $ruc, $direccion, $admin_user, $admin_pass, $logo_url, $modulosJson, $estado, $id);
            
            if (mysqli_stmt_execute($stmtUpd)) {
                // Actualizar también en la BD del tenant si existe
                $dbTenant = $negocio['db_name'];
                $connDb = @mysqli_connect('localhost', 'root', '', $dbTenant);
                if ($connDb) {
                    $claveAdminHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $permisosTotal = 'dashboard,pos,clientes,productos,tecnicos,ordenes,ventas,reportes,usuarios,compras';

                    $stmtUser = mysqli_prepare($connDb, "INSERT INTO usuarios (usuario, rol, permisos, contraseña) VALUES (?, 'Administrador', ?, ?) ON DUPLICATE KEY UPDATE contraseña = ?");
                    if ($stmtUser) {
                        mysqli_stmt_bind_param($stmtUser, "ssss", $admin_user, $permisosTotal, $claveAdminHash, $claveAdminHash);
                        mysqli_stmt_execute($stmtUser);
                        mysqli_stmt_close($stmtUser);
                    }
                    mysqli_close($connDb);
                }

                $_SESSION['master_flash'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Configuración de ' . h_master($nombre_comercial) . ' actualizada correctamente.'
                ];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Error al actualizar: ' . mysqli_error($conexionMaster);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Negocio · LuarSoft Master</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0B132B; color: #E0E6ED; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }
        .navbar-master { background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); }
        .card-custom { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; backdrop-filter: blur(12px); padding: 30px; }
        .form-control, .form-select { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px 14px; border-radius: 8px; }
        .form-control:focus, .form-select:focus { background: rgba(255,255,255,0.12); border-color: #1FA35C; color: #fff; box-shadow: 0 0 0 0.2rem rgba(31,163,92,0.25); }
        .form-check-input { background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); }
        .form-check-input:checked { background-color: #1FA35C; border-color: #1FA35C; }
        .btn-success-custom { background: #1FA35C; border: none; font-weight: 600; padding: 10px 24px; border-radius: 8px; }
        .btn-success-custom:hover { background: #178249; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-master py-3 px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-buildings-fill text-success fs-4"></i> LuarSoft <span class="text-success">Master</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver a la Lista</a>
        </div>
    </div>
</nav>

<div class="container py-5" style="max-width: 800px;">
    <div class="card-custom">
        <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary border-opacity-25 pb-3">
            <i class="bi bi-pencil-square text-success fs-2"></i>
            <div>
                <h3 class="fw-bold mb-0 text-white">Editar Negocio: <?= h_master($negocio['nombre_comercial']) ?></h3>
                <p class="text-secondary small mb-0">Slug: <code class="text-success"><?= h_master($negocio['slug']) ?></code> | BD: <code class="text-info"><?= h_master($negocio['db_name']) ?></code></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= h_master($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="editar_negocio.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $negocio['id'] ?>">

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-building"></i> 1. Información General y Estado</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Nombre Comercial *</label>
                    <input type="text" name="nombre_comercial" class="form-control" value="<?= h_master($negocio['nombre_comercial']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Razón Social *</label>
                    <input type="text" name="razon_social" class="form-control" value="<?= h_master($negocio['razon_social']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">RUC (11 dígitos) *</label>
                    <input type="text" name="ruc" class="form-control" maxlength="11" value="<?= h_master($negocio['ruc']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Estado del Servicio *</label>
                    <select name="estado" class="form-select fw-semibold <?= $negocio['estado'] === 'activo' ? 'text-success' : 'text-danger' ?>">
                        <option value="activo" <?= $negocio['estado'] === 'activo' ? 'selected' : '' ?>>🟢 Activo</option>
                        <option value="suspendido" <?= $negocio['estado'] === 'suspendido' ? 'selected' : '' ?>>🔴 Suspendido</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label text-secondary small fw-semibold">Dirección Comercial</label>
                    <input type="text" name="direccion" class="form-control" value="<?= h_master($negocio['direccion']) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label text-secondary small fw-semibold">Actualizar Logotipo (Opcional)</label>
                    <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/webp">
                </div>
            </div>

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-key-fill"></i> 2. Credenciales del Administrador del ERP</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Usuario Administrador *</label>
                    <input type="text" name="admin_user" class="form-control" value="<?= h_master($negocio['admin_user'] ?: 'admin') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Contraseña Administrador *</label>
                    <input type="text" name="admin_pass" class="form-control" value="<?= h_master($negocio['admin_pass'] ?: 'Admin123*') ?>" required>
                </div>
            </div>

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-boxes"></i> 3. Módulos Habilitados para este Negocio</h5>
            <div class="row g-2 mb-4 bg-dark bg-opacity-25 p-3 rounded-3 border border-secondary border-opacity-25">
                <?php foreach ($modulosDisponibles as $key => $label): 
                    $checked = in_array($key, $modulosActuales, true) ? 'checked' : '';
                ?>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $key ?>" id="mod_<?= $key ?>" <?= $checked ?>>
                            <label class="form-check-label text-light small" for="mod_<?= $key ?>">
                                <?= $label ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-outline-secondary px-4">Cancelar</a>
                <button type="submit" class="btn btn-success-custom">
                    <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
