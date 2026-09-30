<?php
/**
 * modules/usuarios/listado.php
 * ------------------------------------------------------------------
 * Gestión de usuarios del sistema desde la interfaz (antes solo se
 * podían crear/editar directamente en la base de datos). Incluye Rol
 * (Administrador / Cajero-Usuario) y Matriz de Permisos por checkbox
 * para el acceso a cada módulo del sistema.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$resultado = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY id_usuario ASC");
$totalUsuarios = $resultado ? mysqli_num_rows($resultado) : 0;

$page_title = 'Usuarios del Sistema';
$page_subtitle = 'Usuarios · Listado general';
$active_menu = 'usuarios';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-person-lock"></i> Usuarios del Sistema</h1>
        <p>Cuentas que pueden iniciar sesión en el panel, con su rol y permisos de acceso.</p>
    </div>
    <a href="<?= url('modules/usuarios/nuevo.php') ?>" class="btn btn-primary no-print"><i class="bi bi-plus-lg"></i> Nuevo Usuario</a>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th class="no-print">Foto</th><th>ID</th><th>Usuario</th><th>Rol</th><th>Creado</th><th class="no-print text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td class="no-print">
                            <?php if (!empty($fila['foto'])): ?>
                                <img src="<?= url('uploads/usuarios/' . $fila['foto']) ?>" alt="<?= h($fila['usuario']) ?>" style="width:44px; height:44px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                            <?php else: ?>
                                <span style="width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--n-100); color:var(--n-400);"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($fila['id_usuario']) ?></td>
                        <td>
                            <?= h($fila['usuario']) ?>
                            <?php if (!empty($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] === (int)$fila['id_usuario']): ?>
                                <span class="badge-status brand">Tú</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $esAdminFila = ($fila['rol'] ?? 'Administrador') === 'Administrador'; ?>
                            <span class="badge-status <?= $esAdminFila ? 'brand' : 'info' ?>">
                                <i class="bi <?= $esAdminFila ? 'bi-shield-check' : 'bi-person-badge' ?>"></i>
                                <?= h($fila['rol'] ?? 'Administrador') ?>
                            </span>
                        </td>
                        <td><?= !empty($fila['fecha_creacion']) ? h(date('d/m/Y H:i', strtotime($fila['fecha_creacion']))) : '—' ?></td>
                        <td class="no-print text-center">
                            <a href="<?= url('modules/usuarios/editar.php?id=' . (int)$fila['id_usuario']) ?>" class="btn btn-warning btn-sm" title="Editar / cambiar contraseña"><i class="bi bi-pencil-square"></i></a>
                            <?php if ($totalUsuarios > 1): ?>
                                <a href="<?= url('modules/usuarios/eliminar.php?id=' . (int)$fila['id_usuario']) ?>" class="btn btn-danger btn-sm" title="Eliminar" data-confirm-delete="¿Eliminar al usuario «<?= h($fila['usuario']) ?>»? Ya no podrá iniciar sesión.">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger btn-sm" disabled title="Debe existir al menos un usuario en el sistema"><i class="bi bi-trash3"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
