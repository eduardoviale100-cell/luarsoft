<?php
/**
 * modules/tecnicos/editar.php — Reemplaza a "editar_tecnico.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "SELECT * FROM tecnicos WHERE id_tecnico = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    flash('error', 'Técnico no encontrado.');
    header('Location: ' . url('modules/tecnicos/listado.php'));
    exit;
}
$tecnico = mysqli_fetch_assoc($resultado);

if (isset($_POST['actualizar'])) {
    $nombre = limpiar($_POST['nombre']);
    $especialidad = limpiar($_POST['especialidad']);
    $telefono = limpiar($_POST['telefono']);
    $email = limpiar($_POST['email']);

    $foto = $tecnico['foto'] ?? null;
    try {
        $nuevaFoto = subirImagenReferencia($_FILES['foto'] ?? [], 'tecnicos', $nombre);
        if ($nuevaFoto !== null) {
            eliminarImagenReferencia($tecnico['foto'] ?? null, 'tecnicos');
            $foto = $nuevaFoto;
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        header('Location: ' . url('modules/tecnicos/editar.php?id=' . $id));
        exit;
    }

    $upd = mysqli_prepare($conexion, "UPDATE tecnicos SET nombre=?, especialidad=?, telefono=?, email=?, foto=? WHERE id_tecnico=?");
    mysqli_stmt_bind_param($upd, "sssssi", $nombre, $especialidad, $telefono, $email, $foto, $id);

    if (mysqli_stmt_execute($upd)) {
        flash('success', 'Técnico actualizado correctamente.');
        header('Location: ' . url('modules/tecnicos/listado.php'));
        exit;
    } else {
        flash('error', 'Error al actualizar: ' . mysqli_error($conexion));
    }
}

$page_title = 'Editar Técnico';
$active_menu = 'tecnicos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading"><h1><i class="bi bi-pencil-square"></i> Editar Técnico</h1></div>

<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group"><label>Nombre Completo</label><input type="text" name="nombre" value="<?= h($tecnico['nombre']) ?>" class="form-control" required></div>
                <div class="field-group"><label>Especialidad</label><input type="text" name="especialidad" value="<?= h($tecnico['especialidad']) ?>" class="form-control"></div>
                <div class="field-group"><label>Teléfono</label><input type="text" name="telefono" value="<?= h($tecnico['telefono']) ?>" class="form-control"></div>
                <div class="field-group"><label>Email</label><input type="email" name="email" value="<?= h($tecnico['email']) ?>" class="form-control"></div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Referencia</label>
                    <?php if (!empty($tecnico['foto'])): ?>
                        <div class="mb-2">
                            <img src="<?= url('uploads/tecnicos/' . $tecnico['foto']) ?>" alt="Foto actual" style="width:110px; height:110px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB. Deja en blanco para conservar la actual.</small>
                </div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" name="actualizar" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                <a href="<?= url('modules/tecnicos/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
