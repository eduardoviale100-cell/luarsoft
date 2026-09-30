<?php
/**
 * modules/clientes/editar.php — Reemplaza a "editar_cliente.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flash('error', 'ID de cliente no válido.');
    header('Location: ' . url('modules/clientes/listado.php'));
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    flash('error', 'Cliente no encontrado.');
    header('Location: ' . url('modules/clientes/listado.php'));
    exit;
}
$cliente = mysqli_fetch_assoc($resultado);

if (isset($_POST['actualizar'])) {
    $nombre = limpiar($_POST['nombre']);
    $telefono = limpiar($_POST['telefono']);
    $direccion = limpiar($_POST['direccion']);
    $email = limpiar($_POST['email']);
    $tipo_documento = limpiar($_POST['tipo_documento']);
    $numero_documento = limpiar($_POST['numero_documento']);
    $ciudad = limpiar($_POST['ciudad']);
    $region = limpiar($_POST['region']);

    $foto = $cliente['foto'] ?? null;
    try {
        $nuevaFoto = subirImagenReferencia($_FILES['foto'] ?? [], 'clientes', $numero_documento ?: $nombre);
        if ($nuevaFoto !== null) {
            eliminarImagenReferencia($cliente['foto'] ?? null, 'clientes');
            $foto = $nuevaFoto;
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        header('Location: ' . url('modules/clientes/editar.php?id=' . $id));
        exit;
    }

    $upd = mysqli_prepare($conexion, "UPDATE clientes SET nombre=?, telefono=?, direccion=?, email=?, tipo_documento=?, numero_documento=?, ciudad=?, region=?, foto=? WHERE id_cliente=?");
    mysqli_stmt_bind_param($upd, "sssssssssi", $nombre, $telefono, $direccion, $email, $tipo_documento, $numero_documento, $ciudad, $region, $foto, $id);

    if (mysqli_stmt_execute($upd)) {
        flash('success', 'Cliente actualizado correctamente.');
        header('Location: ' . url('modules/clientes/editar.php?id=' . $id));
        exit;
    } else {
        flash('error', 'Error al actualizar: ' . mysqli_error($conexion));
    }
}

$page_title = 'Editar Cliente';
$page_subtitle = 'Clientes · Editar #' . $id;
$active_menu = 'clientes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-pencil-square"></i> Editar Cliente</h1>
    <p>Modifica los datos del cliente seleccionado.</p>
</div>

<div class="card-erp">
    <div class="card-erp-header"><h3>Datos del cliente</h3></div>
    <div class="card-erp-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" value="<?= h($cliente['nombre']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= h($cliente['telefono']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= h($cliente['direccion']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" class="form-control" value="<?= h($cliente['email']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Tipo de documento</label>
                    <select name="tipo_documento" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach (['DNI', 'C.E.', 'Pasaporte', 'Otro'] as $opt): ?>
                            <option value="<?= h($opt) ?>" <?= $cliente['tipo_documento'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>Número de documento</label>
                    <input type="text" name="numero_documento" class="form-control" value="<?= h($cliente['numero_documento']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Ciudad / Pueblo</label>
                    <input type="text" name="ciudad" class="form-control" value="<?= h($cliente['ciudad']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Región</label>
                    <input type="text" name="region" class="form-control" value="<?= h($cliente['region']) ?>" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Referencia</label>
                    <?php if (!empty($cliente['foto'])): ?>
                        <div class="mb-2">
                            <img src="<?= url('uploads/clientes/' . $cliente['foto']) ?>" alt="Foto actual" style="width:110px; height:110px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB. Deja en blanco para conservar la actual.</small>
                </div>
            </div>

            <div class="text-end mt-2">
                <button type="submit" name="actualizar" class="btn btn-success"><i class="bi bi-save"></i> Guardar cambios</button>
                <a href="<?= url('modules/clientes/listado.php') ?>" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Volver</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
