<?php
/**
 * modules/usuarios/nuevo.php — NUEVO.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permisos.php';

$error = '';

if (isset($_POST['guardar'])) {
    $usuario = limpiar($_POST['usuario'] ?? '');
    $clave = (string)($_POST['contrasena'] ?? '');
    $claveConfirmar = (string)($_POST['contrasena_confirmar'] ?? '');
    $rol = limpiar($_POST['rol'] ?? 'Administrador');
    if (!in_array($rol, ['Administrador', 'Cajero / Usuario'], true)) { $rol = 'Administrador'; }
    $permisosSeleccionados = $_POST['permisos'] ?? [];
    $permisos = ($rol === 'Administrador') ? '' : codificarPermisos($permisosSeleccionados);

    if ($usuario === '' || $clave === '') {
        $error = 'Debes ingresar un usuario y una contraseña.';
    } elseif (strlen($clave) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres.';
    } elseif ($clave !== $claveConfirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmtCheck = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, "s", $usuario);
        mysqli_stmt_execute($stmtCheck);
        if (mysqli_stmt_get_result($stmtCheck)->fetch_assoc()) {
            $error = 'Ya existe un usuario con ese nombre.';
        } else {
            $foto = null;
            try {
                $foto = subirImagenReferencia($_FILES['foto'] ?? [], 'usuarios', $usuario);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if ($error === '') {
                $hash = password_hash($clave, PASSWORD_BCRYPT);
                $stmt = mysqli_prepare($conexion, "INSERT INTO usuarios (usuario, rol, permisos, foto, contraseña) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssss", $usuario, $rol, $permisos, $foto, $hash);

                if (mysqli_stmt_execute($stmt)) {
                    flash('success', 'Usuario creado correctamente.');
                    header('Location: ' . url('modules/usuarios/listado.php'));
                    exit;
                } else {
                    $error = 'Error al guardar el usuario: ' . mysqli_error($conexion);
                }
            }
        }
    }
}

$page_title = 'Nuevo Usuario';
$page_subtitle = 'Usuarios · Nuevo registro';
$active_menu = 'usuarios';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-person-plus"></i> Registrar Nuevo Usuario</h1>
    <p>Crea una cuenta adicional para acceder al panel del sistema.</p>
</div>

<div class="card-erp">
    <div class="card-erp-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST" id="formNuevoUsuario" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-person"></i> Usuario</label>
                    <input type="text" name="usuario" class="form-control" placeholder="Ej: cajero2" value="<?= h($_POST['usuario'] ?? '') ?>" required>
                </div>
                <div class="field-group"></div>
                <div class="field-group">
                    <label><i class="bi bi-key"></i> Contraseña</label>
                    <input type="password" name="contrasena" class="form-control" placeholder="Mínimo 4 caracteres" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-key-fill"></i> Confirmar Contraseña</label>
                    <input type="password" name="contrasena_confirmar" class="form-control" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Perfil (opcional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB.</small>
                </div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" name="guardar" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                <a href="<?= url('modules/usuarios/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<div class="card-erp mt-3">
    <div class="card-erp-header"><h3><i class="bi bi-shield-lock"></i> Permisos de Acceso</h3></div>
    <div class="card-erp-body">
        <div class="field-group" style="max-width:320px;">
            <label><i class="bi bi-person-badge"></i> Rol del Usuario</label>
            <select name="rol" id="selectRol" class="form-select" form="formNuevoUsuario" onchange="togglePermisosMatrix()">
                <option value="Administrador">Administrador (acceso total)</option>
                <option value="Cajero / Usuario">Cajero / Usuario (acceso limitado)</option>
            </select>
        </div>

        <div id="matrizPermisos" class="mt-3">
            <p class="text-muted small mb-2">Marca a qué módulos exactos del sistema tendrá acceso este usuario:</p>
            <div class="perm-grid">
                <?php foreach (MODULOS_SISTEMA as $clave => $mod): ?>
                    <?php $marcado = in_array($clave, PERMISOS_CAJERO_DEFECTO, true); ?>
                    <label class="perm-tile <?= $marcado ? 'is-checked' : '' ?>" data-perm-tile>
                        <span class="perm-tile-badge"><i class="bi bi-check-lg"></i></span>
                        <span class="perm-tile-icon"><i class="bi <?= h($mod['icon']) ?>"></i></span>
                        <span class="perm-tile-label"><?= h($mod['label']) ?></span>
                        <input type="checkbox" class="perm-tile-check" name="permisos[]" value="<?= h($clave) ?>" form="formNuevoUsuario"
                            <?= $marcado ? 'checked' : '' ?>>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="avisoAdmin" class="alert alert-info small mb-0" style="display:none;">
            <i class="bi bi-info-circle"></i> Un usuario Administrador tiene acceso total y automático a todo el sistema; no necesita marcar módulos.
        </div>
    </div>
</div>

<script>
function togglePermisosMatrix() {
    const esAdmin = document.getElementById('selectRol').value === 'Administrador';
    document.getElementById('matrizPermisos').style.display = esAdmin ? 'none' : 'block';
    document.getElementById('avisoAdmin').style.display = esAdmin ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function () {
    togglePermisosMatrix();
    document.querySelectorAll('[data-perm-tile]').forEach(function (tile) {
        const check = tile.querySelector('.perm-tile-check');
        check.addEventListener('change', function () {
            tile.classList.toggle('is-checked', check.checked);
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
