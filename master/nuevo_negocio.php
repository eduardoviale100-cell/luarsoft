<?php
/**
 * master/nuevo_negocio.php — Asistente de Creación de Nuevo Negocio (Tenant)
 */
require_once __DIR__ . '/includes/auth_master.php';

$error = '';
$exito = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $razon_social    = trim($_POST['razon_social'] ?? '');
    $ruc             = trim($_POST['ruc'] ?? '');
    $direccion       = trim($_POST['direccion'] ?? '');
    $slug            = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($_POST['slug'] ?? '')));
    $db_name         = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['db_name'] ?? '')));
    $admin_user      = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass      = trim($_POST['admin_pass'] ?? 'Admin123*');
    $modulosSel      = $_POST['modulos'] ?? [];

    if (empty($nombre_comercial) || empty($razon_social) || empty($ruc) || empty($slug) || empty($db_name) || empty($admin_user) || empty($admin_pass)) {
        $error = 'Todos los campos obligatorios deben ser completados.';
    } elseif (strlen($ruc) !== 11 || !is_numeric($ruc)) {
        $error = 'El RUC debe tener exactamente 11 dígitos numéricos.';
    } else {
        // Verificar duplicados en luarsoft_master
        $stmtChk = mysqli_prepare($conexionMaster, "SELECT id FROM sys_negocios WHERE ruc = ? OR slug = ? OR db_name = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChk, "sss", $ruc, $slug, $db_name);
        mysqli_stmt_execute($stmtChk);
        $resChk = mysqli_stmt_get_result($stmtChk);

        if ($resChk && mysqli_num_rows($resChk) > 0) {
            $error = 'Ya existe un negocio registrado con ese RUC, Identificador (Slug) o Nombre de BD.';
        } else {
            // Manejar logotipo opcional
            $logo_url = 'assets/img/eros.jpg';
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dirUpload = __DIR__ . '/../luarsoft/uploads/logos/';
                    if (!is_dir($dirUpload)) { mkdir($dirUpload, 0755, true); }
                    $nombreFoto = 'logo_' . $slug . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dirUpload . $nombreFoto)) {
                        $logo_url = 'uploads/logos/' . $nombreFoto;
                    }
                }
            }

            $modulosJson = json_encode(array_values(array_intersect($modulosSel, array_keys($modulosDisponibles))));

            // 1. Insertar en sys_negocios
            $stmtIns = mysqli_prepare($conexionMaster, "INSERT INTO sys_negocios (nombre_comercial, razon_social, ruc, direccion, slug, db_name, admin_user, admin_pass, logo_url, modulos_activos, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
            mysqli_stmt_bind_param($stmtIns, "ssssssssss", $nombre_comercial, $razon_social, $ruc, $direccion, $slug, $db_name, $admin_user, $admin_pass, $logo_url, $modulosJson);
            
            if (mysqli_stmt_execute($stmtIns)) {
                // 2. Crear y aprovisionar la Base de Datos del nuevo Tenant
                $connDb = mysqli_connect('localhost', 'root', '');
                if ($connDb) {
                    mysqli_query($connDb, "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
                    mysqli_select_db($connDb, $db_name);

                    // Importar esquema inicial desde luarsoft_completo.sql
                    $sqlFile = __DIR__ . '/../luarsoft/database/luarsoft_completo.sql';
                    if (file_exists($sqlFile)) {
                        $lines = file($sqlFile);
                        $query = '';
                        foreach ($lines as $line) {
                            if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 2) == '/*' || stripos($line, 'CREATE DATABASE') !== false || stripos($line, 'USE `luarsoft`') !== false)
                                continue;
                            $query .= $line;
                            if (substr(trim($line), -1, 1) == ';') {
                                mysqli_query($connDb, $query);
                                $query = '';
                            }
                        }

                        // Limpiar usuarios de prueba si los hay e insertar el Administrador configurado
                        mysqli_query($connDb, "DELETE FROM usuarios WHERE usuario IN ('Luis Fernández', 'Eduardo Viale', 'eduardo', '$admin_user')");
                        
                        $claveAdminHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                        $permisosTotal = 'dashboard,pos,clientes,productos,tecnicos,ordenes,ventas,reportes,usuarios,compras';

                        $stmtUser = mysqli_prepare($connDb, "INSERT INTO usuarios (usuario, rol, permisos, contraseña) VALUES (?, 'Administrador', ?, ?) ON DUPLICATE KEY UPDATE contraseña = ?");
                        if ($stmtUser) {
                            mysqli_stmt_bind_param($stmtUser, "ssss", $admin_user, $permisosTotal, $claveAdminHash, $claveAdminHash);
                            mysqli_stmt_execute($stmtUser);
                            mysqli_stmt_close($stmtUser);
                        }
                    }
                    mysqli_close($connDb);
                }

                $_SESSION['master_flash'] = [
                    'tipo' => 'success',
                    'mensaje' => "¡Negocio '$nombre_comercial' creado con éxito! Credenciales del ERP: Usuario: $admin_user | Clave: $admin_pass"
                ];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Error al registrar el negocio: ' . mysqli_error($conexionMaster);
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
    <title>Nuevo Negocio · LuarSoft Master</title>
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
            <i class="bi bi-plus-circle-fill text-success fs-2"></i>
            <div>
                <h3 class="fw-bold mb-0 text-white">Registrar Nuevo Negocio / Proyecto</h3>
                <p class="text-secondary small mb-0">Asistente de personalización y aprovisionamiento automático</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= h_master($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="nuevo_negocio.php" enctype="multipart/form-data">
            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-building"></i> 1. Información General del Negocio</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Nombre Comercial *</label>
                    <input type="text" name="nombre_comercial" id="inpNombre" class="form-control" placeholder="Ej. Panadería San José" required oninput="generarSlug()">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Razón Social *</label>
                    <input type="text" name="razon_social" class="form-control" placeholder="Ej. Panadería San José E.I.R.L." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">RUC (11 dígitos) *</label>
                    <input type="text" name="ruc" class="form-control" maxlength="11" placeholder="20123456789" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Dirección Comercial</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Av. Principal 456, Lima">
                </div>
            </div>

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-hdd-stack"></i> 2. Configuración Técnica y Base de Datos</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Identificador de URL (Slug) *</label>
                    <input type="text" name="slug" id="inpSlug" class="form-control" placeholder="panaderia_sanjose" required readonly>
                    <div class="form-text text-muted small">Usado para identificar este negocio en la URL (?empresa=slug).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Nombre de Base de Datos MySQL *</label>
                    <input type="text" name="db_name" id="inpDb" class="form-control" placeholder="erp_panaderia_sanjose" required readonly>
                    <div class="form-text text-muted small">Se creará automáticamente en MySQL.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label text-secondary small fw-semibold">Logotipo del Negocio (Opcional)</label>
                    <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/webp">
                </div>
            </div>

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-key-fill"></i> 3. Credenciales Iniciales del Administrador del ERP</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Usuario Administrador *</label>
                    <input type="text" name="admin_user" class="form-control" value="admin" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Contraseña Administrador *</label>
                    <input type="text" name="admin_pass" class="form-control" value="Admin123*" required>
                </div>
            </div>

            <h5 class="text-success fw-semibold mb-3"><i class="bi bi-boxes"></i> 4. Módulos Habilitados para este Negocio</h5>
            <div class="row g-2 mb-4 bg-dark bg-opacity-25 p-3 rounded-3 border border-secondary border-opacity-25">
                <?php foreach ($modulosDisponibles as $key => $label): ?>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $key ?>" id="mod_<?= $key ?>" checked>
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
                    <i class="bi bi-magic me-1"></i> Aprovisionar y Registrar Negocio
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function generarSlug() {
    const nombre = document.getElementById('inpNombre').value;
    const slug = nombre.toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    document.getElementById('inpSlug').value = slug || '';
    document.getElementById('inpDb').value = slug ? ('erp_' + slug) : '';
}
</script>

</body>
</html>
