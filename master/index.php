<?php
/**
 * master/index.php — Panel Principal del SuperAdmin (LuarSoft Master)
 */
require_once __DIR__ . '/includes/auth_master.php';

$flash = $_SESSION['master_flash'] ?? null;
unset($_SESSION['master_flash']);

// Métricas de Resumen
$totalNegocios = 0;
$activos = 0;
$suspendidos = 0;

$resStats = mysqli_query($conexionMaster, "SELECT estado, COUNT(*) as c FROM sys_negocios GROUP BY estado");
if ($resStats) {
    while ($row = mysqli_fetch_assoc($resStats)) {
        if ($row['estado'] === 'activo') $activos = (int)$row['c'];
        if ($row['estado'] === 'suspendido') $suspendidos = (int)$row['c'];
        $totalNegocios += (int)$row['c'];
    }
}

// Búsqueda y listado de negocios
$buscar = trim($_GET['b'] ?? '');
$sql = "SELECT id, nombre_comercial, razon_social, ruc, direccion, slug, db_name, admin_user, admin_pass, logo_url, modulos_activos, estado, fecha_registro FROM sys_negocios";
if (!empty($buscar)) {
    $sql .= " WHERE nombre_comercial LIKE '%" . mysqli_real_escape_string($conexionMaster, $buscar) . "%' OR ruc LIKE '%" . mysqli_real_escape_string($conexionMaster, $buscar) . "%' OR slug LIKE '%" . mysqli_real_escape_string($conexionMaster, $buscar) . "%'";
}
$sql .= " ORDER BY id ASC";
$resNegocios = mysqli_query($conexionMaster, $sql);
$negocios = [];
if ($resNegocios) {
    while ($f = mysqli_fetch_assoc($resNegocios)) {
        $f['modulos'] = json_decode($f['modulos_activos'] ?? '[]', true) ?: [];
        $negocios[] = $f;
    }
}
// Cálculo dinámico de la URL real de acceso al login del ERP en el servidor
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$projectRootDir = dirname(dirname($scriptName));
if ($projectRootDir === '/' || $projectRootDir === '\\') {
    $projectRootDir = '';
}
$tenantLoginBase = $projectRootDir . '/luarsoft/login.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Master Central · LuarSoft SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0B132B; color: #E0E6ED; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }
        .navbar-master { background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px); }
        .kpi-card { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; display: flex; align-items: center; gap: 16px; }
        .kpi-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
        .card-custom { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; backdrop-filter: blur(12px); padding: 24px; }
        .table-custom { color: #E0E6ED; margin-bottom: 0; }
        .table-custom th { background: rgba(255,255,255,0.05); color: #8C9BAE; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 12px 16px; }
        .table-custom td { border-bottom: 1px solid rgba(255,255,255,0.05); padding: 14px 16px; vertical-align: middle; }
        .badge-mod { background: rgba(31, 163, 92, 0.15); color: #4ADE80; border: 1px solid rgba(31, 163, 92, 0.3); font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; margin: 2px; display: inline-block; }
        .btn-custom-add { background: #1FA35C; color: #fff; font-weight: 600; border-radius: 8px; padding: 10px 20px; border: none; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-custom-add:hover { background: #178249; color: #fff; }
        .tenant-logo { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; background: #1A2640; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<!-- Topbar -->
<nav class="navbar navbar-dark navbar-master py-3 px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-buildings-fill text-success fs-4"></i> LuarSoft <span class="text-success">Master</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary small"><i class="bi bi-person-circle me-1"></i> SuperAdmin: <strong class="text-light"><?= h_master($_SESSION['superadmin_nombre']) ?></strong></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-2"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 px-4">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= h_master($flash['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-25 text-primary"><i class="bi bi-building"></i></div>
                <div>
                    <div class="text-secondary small fw-semibold">Total Proyectos / Negocios</div>
                    <div class="fs-3 fw-bold text-white"><?= $totalNegocios ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-25 text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-secondary small fw-semibold">Servicios Activos</div>
                    <div class="fs-3 fw-bold text-success"><?= $activos ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-danger bg-opacity-25 text-danger"><i class="bi bi-slash-circle"></i></div>
                <div>
                    <div class="text-secondary small fw-semibold">Servicios Suspendidos</div>
                    <div class="fs-3 fw-bold text-danger"><?= $suspendidos ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="card-custom mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-white">Gestión Global de Empresas (Tenants)</h4>
                <p class="text-secondary small mb-0">Habilita, inhabilita o agrega proyectos con su propia base de datos y módulos a medida.</p>
            </div>
            <a href="nuevo_negocio.php" class="btn-custom-add">
                <i class="bi bi-plus-lg fs-5"></i> Registrar Nuevo Negocio
            </a>
        </div>

        <form method="GET" action="index.php" class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" name="b" class="form-control bg-dark text-light border-secondary" placeholder="Buscar por Nombre, RUC o Slug..." value="<?= h_master($buscar) ?>">
                    <?php if ($buscar): ?>
                        <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Tabla de Negocios -->
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Negocio / Razón Social</th>
                        <th>RUC</th>
                        <th>Slug & Base de Datos</th>
                        <th>Credenciales Acceso</th>
                        <th>Módulos Habilitados</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($negocios)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No se encontraron negocios registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($negocios as $n): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../luarsoft/<?= h_master($n['logo_url'] ?: 'assets/img/eros.jpg') ?>" alt="Logo" class="tenant-logo" onerror="this.src='../luarsoft/assets/img/eros.jpg'">
                                        <div>
                                            <div class="fw-bold text-white fs-6">
                                                <?= h_master($n['nombre_comercial']) ?>
                                                <?php if ($n['slug'] === 'eros'): ?>
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">INSTANCIA BASE</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-secondary small"><?= h_master($n['razon_social']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary font-monospace"><?= h_master($n['ruc']) ?></span>
                                </td>
                                <td>
                                    <div><code class="text-success fs-6">?empresa=<?= h_master($n['slug']) ?></code></div>
                                    <div class="small text-muted"><i class="bi bi-database me-1"></i><?= h_master($n['db_name']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-light"><i class="bi bi-person-fill text-success me-1"></i><?= h_master($n['admin_user'] ?: 'admin') ?></div>
                                    <div class="small text-muted font-monospace d-flex align-items-center gap-1">
                                        <i class="bi bi-key-fill text-warning me-1"></i>
                                        <span id="pass_<?= $n['id'] ?>">••••••••</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary ms-1" onclick="togglePass(<?= $n['id'] ?>, '<?= h_master($n['admin_pass'] ?: 'Admin123*') ?>')" title="Ver/Ocultar Contraseña">
                                            <i class="bi bi-eye" id="eye_<?= $n['id'] ?>"></i>
                                        </button>
                                    </div>
                                </td>
                                <td style="max-width: 240px;">
                                    <?php foreach ($n['modulos'] as $m): ?>
                                        <span class="badge-mod"><?= h_master($m) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if ($n['estado'] === 'activo'): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> ACTIVO</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger px-2 py-1"><i class="bi bi-slash-circle me-1"></i> SUSPENDIDO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?= $tenantLoginBase ?>?empresa=<?= urlencode($n['slug']) ?>&user=<?= urlencode($n['admin_user'] ?: 'admin') ?>&pass=<?= urlencode($n['admin_pass'] ?: 'Admin123*') ?>" target="_blank" class="btn btn-outline-info btn-sm fw-semibold" title="Autologin al ERP de este negocio">
                                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                                        </a>
                                        <a href="editar_negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-light btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="cambiar_estado.php" class="d-inline" onsubmit="return confirm('¿Estás seguro de cambiar el estado de este servicio?');">
                                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                            <?php if ($n['estado'] === 'activo'): ?>
                                                <input type="hidden" name="estado" value="suspendido">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Suspender Servicio">
                                                    <i class="bi bi-pause-circle"></i> Suspender
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="estado" value="activo">
                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Activar Servicio">
                                                    <i class="bi bi-play-circle"></i> Activar
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function togglePass(id, realPass) {
    const el = document.getElementById('pass_' + id);
    const eye = document.getElementById('eye_' + id);
    if (el.innerText === '••••••••') {
        el.innerText = realPass;
        eye.classList.remove('bi-eye');
        eye.classList.add('bi-eye-slash');
    } else {
        el.innerText = '••••••••';
        eye.classList.remove('bi-eye-slash');
        eye.classList.add('bi-eye');
    }
}
</script>

</body>
</html>
