<?php
/**
 * modules/usuarios/editar.php — NUEVO.
 * Permite cambiar el nombre de usuario y, opcionalmente, la
 * contraseña (si se dejan los campos de contraseña en blanco, se
 * conserva la contraseña actual).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permisos.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    flash('error', 'Usuario no encontrado.');
    header('Location: ' . url('modules/usuarios/listado.php'));
    exit;
}
$usuarioActual = mysqli_fetch_assoc($resultado);
$esUno_mismo = !empty($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] === $id;
$error = '';

if (isset($_POST['actualizar'])) {
    $usuario = limpiar($_POST['usuario'] ?? '');
    $clave = (string)($_POST['contrasena'] ?? '');
    $claveConfirmar = (string)($_POST['contrasena_confirmar'] ?? '');
    $rol = limpiar($_POST['rol'] ?? 'Administrador');
    if (!in_array($rol, ['Administrador', 'Cajero / Usuario'], true)) { $rol = 'Administrador'; }
    $permisosSeleccionados = $_POST['permisos'] ?? [];
    $permisos = ($rol === 'Administrador') ? '' : codificarPermisos($permisosSeleccionados);

    if ($usuario === '') {
        $error = 'El nombre de usuario no puede estar vacío.';
    } elseif ($esUno_mismo && $rol !== 'Administrador') {
        $error = 'No puedes quitarte a ti mismo el rol de Administrador mientras tienes la sesión abierta.';
    } elseif ($clave !== '' && strlen($clave) < 4) {
        $error = 'La nueva contraseña debe tener al menos 4 caracteres.';
    } elseif ($clave !== $claveConfirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmtCheck = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE usuario = ? AND id_usuario != ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, "si", $usuario, $id);
        mysqli_stmt_execute($stmtCheck);
        if (mysqli_stmt_get_result($stmtCheck)->fetch_assoc()) {
            $error = 'Ya existe otro usuario con ese nombre.';
        } else {
            $foto = $usuarioActual['foto'] ?? null;
            try {
                $nuevaFoto = subirImagenReferencia($_FILES['foto'] ?? [], 'usuarios', $usuario);
                if ($nuevaFoto !== null) {
                    eliminarImagenReferencia($usuarioActual['foto'] ?? null, 'usuarios');
                    $foto = $nuevaFoto;
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if ($error === '') {
                if ($clave !== '') {
                    $hash = password_hash($clave, PASSWORD_BCRYPT);
                    $upd = mysqli_prepare($conexion, "UPDATE usuarios SET usuario = ?, rol = ?, permisos = ?, foto = ?, contraseña = ? WHERE id_usuario = ?");
                    mysqli_stmt_bind_param($upd, "sssssi", $usuario, $rol, $permisos, $foto, $hash, $id);
                } else {
                    $upd = mysqli_prepare($conexion, "UPDATE usuarios SET usuario = ?, rol = ?, permisos = ?, foto = ? WHERE id_usuario = ?");
                    mysqli_stmt_bind_param($upd, "ssssi", $usuario, $rol, $permisos, $foto, $id);
                }

                if (mysqli_stmt_execute($upd)) {
                    // Si el usuario editado es el que tiene la sesión abierta, refrescamos sus datos en sesión.
                    if ($esUno_mismo) {
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['rol'] = $rol;
                        $_SESSION['permisos'] = decodificarPermisos($permisos);
                    }
                    flash('success', 'Usuario actualizado correctamente.');
                    header('Location: ' . url('modules/usuarios/listado.php'));
                    exit;
                } else {
                    $error = 'Error al actualizar: ' . mysqli_error($conexion);
                }
            }
        }
    }
}

$rolActualForm = $usuarioActual['rol'] ?? 'Administrador';
$permisosActualesForm = decodificarPermisos($usuarioActual['permisos'] ?? null);

$page_title = 'Editar Usuario';
$active_menu = 'usuarios';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading"><h1><i class="bi bi-pencil-square"></i> Editar Usuario</h1></div>

<div class="card-erp">
    <div class="card-erp-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST" id="formEditarUsuario" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-person"></i> Usuario</label>
                    <input type="text" name="usuario" class="form-control" value="<?= h($usuarioActual['usuario']) ?>" required>
                </div>
                <div class="field-group"></div>
                <div class="field-group">
                    <label><i class="bi bi-key"></i> Nueva Contraseña</label>
                    <input type="password" name="contrasena" class="form-control" placeholder="Dejar en blanco para no cambiarla">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-key-fill"></i> Confirmar Nueva Contraseña</label>
                    <input type="password" name="contrasena_confirmar" class="form-control" placeholder="Dejar en blanco para no cambiarla">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Perfil</label>
                    <?php if (!empty($usuarioActual['foto'])): ?>
                        <div class="mb-2">
                            <img src="<?= url('uploads/usuarios/' . $usuarioActual['foto']) ?>" alt="Foto actual" style="width:110px; height:110px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB. Deja en blanco para conservar la actual.</small>
                </div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" name="actualizar" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                <a href="<?= url('modules/usuarios/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<div class="card-erp mt-3">
    <div class="card-erp-header"><h3><i class="bi bi-shield-lock"></i> Permisos de Acceso</h3></div>
    <div class="card-erp-body">
        <?php if ($esUno_mismo): ?>
            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i> Estás editando tu propia cuenta: no puedes quitarte el rol de Administrador mientras tienes la sesión abierta.</div>
        <?php endif; ?>
        <div class="field-group" style="max-width:320px;">
            <label><i class="bi bi-person-badge"></i> Rol del Usuario</label>
            <select name="rol" id="selectRol" class="form-select" form="formEditarUsuario" onchange="togglePermisosMatrix()" <?= $esUno_mismo ? 'disabled' : '' ?>>
                <option value="Administrador" <?= $rolActualForm === 'Administrador' ? 'selected' : '' ?>>Administrador (acceso total)</option>
                <option value="Cajero / Usuario" <?= $rolActualForm === 'Cajero / Usuario' ? 'selected' : '' ?>>Cajero / Usuario (acceso limitado)</option>
            </select>
            <?php if ($esUno_mismo): ?>
                <input type="hidden" name="rol" value="<?= h($rolActualForm) ?>" form="formEditarUsuario">
            <?php endif; ?>
        </div>

        <div id="matrizPermisos" class="mt-3">
            <p class="text-muted small mb-2">Marca a qué módulos exactos del sistema tendrá acceso este usuario:</p>
            <div class="perm-grid">
                <?php foreach (MODULOS_SISTEMA as $clave => $mod): ?>
                    <?php $marcado = in_array($clave, $permisosActualesForm, true); ?>
                    <label class="perm-tile <?= $marcado ? 'is-checked' : '' ?>" data-perm-tile>
                        <span class="perm-tile-badge"><i class="bi bi-check-lg"></i></span>
                        <span class="perm-tile-icon"><i class="bi <?= h($mod['icon']) ?>"></i></span>
                        <span class="perm-tile-label"><?= h($mod['label']) ?></span>
                        <input type="checkbox" class="perm-tile-check" name="permisos[]" value="<?= h($clave) ?>" form="formEditarUsuario"
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
