<?php
/**
 * modules/tecnicos/nuevo.php — Reemplaza a "nuevo_tecnico.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isset($_POST['guardar'])) {
    $nombre = limpiar($_POST['nombre']);
    $especialidad = limpiar($_POST['especialidad']);
    $telefono = limpiar($_POST['telefono']);
    $email = limpiar($_POST['email']);

    $foto = null;
    try {
        $foto = subirImagenReferencia($_FILES['foto'] ?? [], 'tecnicos', $nombre);
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        header('Location: ' . url('modules/tecnicos/nuevo.php'));
        exit;
    }

    $stmt = mysqli_prepare($conexion, "INSERT INTO tecnicos (nombre, especialidad, telefono, email, foto) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "sssss", $nombre, $especialidad, $telefono, $email, $foto);

    if (mysqli_stmt_execute($stmt)) {
        flash('success', 'Técnico agregado correctamente.');
        header('Location: ' . url('modules/tecnicos/listado.php'));
        exit;
    } else {
        flash('error', 'Error al guardar el técnico: ' . mysqli_error($conexion));
    }
}

$page_title = 'Registrar Técnico';
$page_subtitle = 'Técnicos · Nuevo registro';
$active_menu = 'tecnicos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-plus-lg"></i> Registrar Nuevo Técnico</h1>
    <p>Agrega un técnico al equipo de reparaciones.</p>
</div>

<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group"><label>Nombre Completo</label><input type="text" name="nombre" class="form-control" required></div>
                <div class="field-group"><label>Especialidad</label><input type="text" name="especialidad" class="form-control" placeholder="Ej: Impresoras láser, tinta, plotters..."></div>
                <div class="field-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" placeholder="Ej: 987654321"></div>
                <div class="field-group"><label>Email</label><input type="email" name="email" class="form-control" placeholder="Ej: tecnico@luarsoft.com"></div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Referencia (opcional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB.</small>
                </div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" name="guardar" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                <a href="<?= url('modules/tecnicos/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
