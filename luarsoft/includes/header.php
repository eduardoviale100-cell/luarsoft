<?php
/**
 * includes/header.php
 * ------------------------------------------------------------------
 * Barra superior (topbar). Usa $page_title y $page_subtitle si están
 * definidas en la página que lo incluye.
 */
$page_title = $page_title ?? 'LuarSoft';
$page_subtitle = $page_subtitle ?? '';
$nombre_usuario = usuarioActual();
$inicial_usuario = strtoupper(substr($nombre_usuario, 0, 1));
$rol_usuario = $_SESSION['rol'] ?? 'Administrador';

$foto_usuario = null;
if (!empty($_SESSION['id_usuario']) && isset($conexion)) {
    $stmtFotoTopbar = mysqli_prepare($conexion, "SELECT foto FROM usuarios WHERE id_usuario = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtFotoTopbar, "i", $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmtFotoTopbar);
    $foto_usuario = mysqli_stmt_get_result($stmtFotoTopbar)->fetch_assoc()['foto'] ?? null;
}
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-toggle" id="toggleSidebar" type="button" title="Mostrar/ocultar menú"><i class="bi bi-list icon-lg"></i></button>
        <div>
            <div class="topbar-title"><?= h($page_title) ?></div>
            <?php if ($page_subtitle): ?>
                <div class="topbar-breadcrumb"><?= h($page_subtitle) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="topbar-right">
        <div class="status-pill">
            <span class="status-dot"></span> <span class="status-pill-text">Servidor en línea</span>
        </div>

        <?php if (tienePermiso('pos')): ?>
        <button class="icon-btn" title="Accesos rápidos: Punto de Venta" onclick="window.location.href='<?= url('modules/ventas/pos.php') ?>'"><i class="bi bi-lightning-charge-fill"></i></button>
        <?php endif; ?>

        <button class="theme-toggle-btn" id="themeToggleBtn" type="button" title="Cambiar a modo oscuro">
            <i class="bi bi-moon-stars-fill"></i>
            <i class="bi bi-sun-fill"></i>
        </button>

        <button class="icon-btn" title="Notificaciones">
            <i class="bi bi-bell"></i><span class="badge-dot"></span>
        </button>

        <div class="user-chip" title="Sesión de <?= h($nombre_usuario) ?>">
            <?php if (!empty($foto_usuario)): ?>
                <img class="user-avatar" src="<?= url('uploads/usuarios/' . $foto_usuario) ?>" alt="<?= h($nombre_usuario) ?>" style="object-fit:cover;">
            <?php else: ?>
                <div class="user-avatar"><?= h($inicial_usuario) ?></div>
            <?php endif; ?>
            <div>
                <div class="user-name"><?= h($nombre_usuario) ?></div>
                <div class="user-role"><?= h($rol_usuario) ?></div>
            </div>
        </div>
    </div>
</header>
