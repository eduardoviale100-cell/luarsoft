<?php
/**
 * config/conexion.php — CONECTOR MULTI-TENANT DINÁMICO & AISLAMIENTO DE SESIÓN (LuarSoft SaaS)
 * ------------------------------------------------------------------
 * - Captura y valida el slug del negocio (?empresa=slug).
 * - Aislamiento estricto de sesiones: Si el usuario cambia de tenant en la URL,
 *   limpia automáticamente la sesión del tenant anterior para evitar cruces de datos.
 * - Anti-Bypass de Suspendidos: Si la empresa se encuentra 'suspendida', destruye
 *   cualquier intento de sesión, bloquea el acceso con HTTP 403 y muestra la pantalla oficial.
 * ------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Detección automática y dinámica de la ruta base del sistema (BASE_URL)
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strrpos($scriptName, '/luarsoft/');
    if ($pos !== false) {
        $basePath = substr($scriptName, 0, $pos + 10);
    } else {
        $basePath = rtrim(dirname($scriptName), '/') . '/';
    }
    define('BASE_URL', $basePath);
}

// 2. Captura y sanitización del parámetro GET 'empresa' (slug del negocio)
$empresa_slug = 'eros';
if (!empty($_GET['empresa'])) {
    $empresa_slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['empresa']));
} elseif (!empty($_SESSION['current_tenant_slug'])) {
    $empresa_slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['current_tenant_slug']));
} elseif (!empty($_SESSION['tenant_slug'])) {
    $empresa_slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['tenant_slug']));
}

// ---------------------------------------------------------------
// AISLAMIENTO ABSOLUTO DE SESIONES AL CAMBIAR DE TENANT
// ---------------------------------------------------------------
if (isset($_SESSION['current_tenant_slug']) && $_SESSION['current_tenant_slug'] !== $empresa_slug) {
    // Se detectó cambio de negocio en la URL: limpiar credenciales del tenant anterior
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
}
$_SESSION['current_tenant_slug'] = $empresa_slug;
$_SESSION['tenant_slug'] = $empresa_slug;

// Parámetros de conexión predeterminados (Respaldo para Instancia Base Eros)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'luarsoft';

$tenantInfo = [
    'id' => 1,
    'nombre_comercial' => 'Multiservicios Eros',
    'razon_social' => 'Eros Tecnología S.A.C.',
    'ruc' => '20600000001',
    'slug' => 'eros',
    'admin_user' => 'admin',
    'admin_pass' => 'Admin123*',
    'logo_url' => 'assets/img/eros.jpg',
    'modulos_activos' => ["dashboard", "pos", "clientes", "productos", "tecnicos", "ordenes", "ventas", "reportes", "usuarios", "compras"],
    'estado' => 'activo'
];

// 3. Consulta a la Base de Datos Central 'luarsoft_master'
mysqli_report(MYSQLI_REPORT_OFF);
$conexionMaster = @mysqli_connect('localhost', 'root', '', 'luarsoft_master');

if ($conexionMaster) {
    mysqli_set_charset($conexionMaster, 'utf8mb4');
    $stmtMaster = mysqli_prepare($conexionMaster, "SELECT * FROM sys_negocios WHERE slug = ? LIMIT 1");
    if ($stmtMaster) {
        mysqli_stmt_bind_param($stmtMaster, "s", $empresa_slug);
        mysqli_stmt_execute($stmtMaster);
        $resMaster = mysqli_stmt_get_result($stmtMaster);
        
        if ($resMaster && $filaMaster = mysqli_fetch_assoc($resMaster)) {
            $tenantInfo = $filaMaster;
            $tenantInfo['modulos_activos'] = !empty($filaMaster['modulos_activos']) 
                ? (is_array($filaMaster['modulos_activos']) ? $filaMaster['modulos_activos'] : json_decode($filaMaster['modulos_activos'], true)) 
                : [];

            $dbNombreReal = $filaMaster['db_name'] ?? ($filaMaster['bd_nombre'] ?? 'luarsoft');

            $dbHost = !empty($filaMaster['db_host']) ? $filaMaster['db_host'] : 'localhost';
            $dbUser = !empty($filaMaster['db_user']) ? $filaMaster['db_user'] : 'root';
            $dbPass = isset($filaMaster['db_pass']) ? $filaMaster['db_pass'] : '';
            $dbName = !empty($dbNombreReal) ? $dbNombreReal : 'luarsoft';
        }
        mysqli_stmt_close($stmtMaster);
    }
    mysqli_close($conexionMaster);
}

// ---------------------------------------------------------------
// BLOQUEO ESTRICTO POR ESTADO (ANTI-BYPASS DE NEGOCIOS SUSPENDIDOS)
// ---------------------------------------------------------------
$estadoActual = strtolower(trim($tenantInfo['estado'] ?? 'activo'));

if ($estadoActual === 'suspendido') {
    // Destrucción inmediata de cualquier sesión activa
    unset($_SESSION['usuario'], $_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['permisos'], $_SESSION['session_tenant_slug']);
    
    http_response_code(403);
    $nombreNegocio = htmlspecialchars($tenantInfo['nombre_comercial'] ?? 'Este negocio');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Servicio Suspendido · LuarSoft SaaS</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body { background: #0B132B; color: #E0E6ED; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .card-suspendido { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border-radius: 16px; padding: 40px; max-width: 520px; width: 90%; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
            .icon-warn { font-size: 4rem; color: #FF4949; margin-bottom: 20px; }
            .btn-soporte { background: #1FA35C; color: #fff; font-weight: 600; border-radius: 8px; padding: 12px 24px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; transition: all 0.2s; }
            .btn-soporte:hover { background: #178249; color: #fff; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="card-suspendido">
            <i class="bi bi-shield-x icon-warn"></i>
            <h2 class="fw-bold mb-2"><?= $nombreNegocio ?></h2>
            <div class="badge bg-danger mb-4 px-3 py-2 fs-6">SERVICIO SUSPENDIDO</div>
            <p class="text-secondary mb-4">
                El acceso a la plataforma LuarSoft para esta empresa ha sido inhabilitado por administración.
            </p>
            <p class="small text-muted mb-0">Para restablecer la cuenta o realizar consultas de pago, comunícate con el soporte central:</p>
            <a href="https://wa.me/51949092352?text=Hola,%20requiero%20asistencia%20con%20el%20servicio%20suspendido%20de%20<?= urlencode($tenantInfo['nombre_comercial']) ?>" target="_blank" class="btn-soporte">
                <i class="bi bi-whatsapp"></i> Contactar a Soporte Central
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Definición de Constantes de Contexto del Tenant
if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
if (!defined('DB_USER')) define('DB_USER', $dbUser);
if (!defined('DB_PASS')) define('DB_PASS', $dbPass);
if (!defined('DB_NAME')) define('DB_NAME', $dbName);

if (!defined('TENANT_ID')) define('TENANT_ID', (int)($tenantInfo['id'] ?? 1));
if (!defined('TENANT_NOMBRE')) define('TENANT_NOMBRE', $tenantInfo['nombre_comercial'] ?? 'Multiservicios Eros');
if (!defined('TENANT_RUC')) define('TENANT_RUC', $tenantInfo['ruc'] ?? '20600000001');
if (!defined('TENANT_SLUG')) define('TENANT_SLUG', $tenantInfo['slug'] ?? 'eros');
if (!defined('TENANT_LOGO')) define('TENANT_LOGO', $tenantInfo['logo_url'] ?? 'assets/img/eros.jpg');
if (!defined('TENANT_MODULOS')) define('TENANT_MODULOS', is_array($tenantInfo['modulos_activos']) ? $tenantInfo['modulos_activos'] : []);
if (!defined('TENANT_ESTADO')) define('TENANT_ESTADO', $estadoActual);

// 5. Conexión Dinámica a la Base de Datos del Negocio
$conexion = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conexion) {
    http_response_code(500);
    $errMsj = mysqli_connect_error();
    die("
        <div style='background:#1A1D20; color:#F8F9FA; font-family:sans-serif; padding:40px; border-radius:12px; margin:40px auto; max-width:600px; box-shadow:0 10px 30px rgba(0,0,0,0.5); border:1px solid #DC3545;'>
            <h3 style='color:#DC3545; margin-top:0;'>❌ Error de Conexión Multi-Tenant</h3>
            <p>No se pudo conectar a la base de datos asignada al negocio <strong>" . htmlspecialchars(TENANT_NOMBRE) . "</strong>.</p>
            <p><strong>Base de datos objetivo:</strong> <code>" . htmlspecialchars(DB_NAME) . "</code></p>
            <p style='background:rgba(255,255,255,0.05); padding:10px; border-radius:6px; font-family:monospace; font-size:13px; color:#FF8A8A;'>Detalle técnico: {$errMsj}</p>
            <hr style='border-color:rgba(255,255,255,0.1); margin:20px 0;'>
            <small style='color:#ADB5BD;'>Sugerencia: Asegúrate de que la base de datos de este negocio exista en MySQL y que el nombre en la tabla 'sys_negocios' coincida exactamente.</small>
        </div>
    ");
}

mysqli_set_charset($conexion, 'utf8mb4');
date_default_timezone_set('America/Lima');
