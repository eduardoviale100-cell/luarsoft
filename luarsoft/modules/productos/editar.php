<?php
/**
 * modules/productos/editar.php — Reemplaza a "editar_producto.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$codigo_producto = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($codigo_producto === '') {
    flash('error', 'Código de producto (SKU) no especificado.');
    header('Location: ' . url('modules/productos/listado.php'));
    exit;
}

if (isset($_POST['actualizar'])) {
    $descripcion   = limpiar($_POST['descripcion']);
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $codigo_barras = $codigo_barras !== '' ? $codigo_barras : null;
    $precio_compra = (float)$_POST['precio_compra'];
    $precio_venta  = (float)$_POST['precio_venta'];
    $stock         = (int)$_POST['stock'];
    $categoria     = limpiar($_POST['categoria']);
    $tipo_impuesto = limpiar($_POST['tipo_impuesto']);
    $codigo_base   = limpiar($_POST['codigo_base']);
    $caracteristicas = trim($_POST['caracteristicas'] ?? '');
    $especificaciones_tecnicas = trim($_POST['especificaciones_tecnicas'] ?? '');

    // Se consulta la imagen actual para conservarla si no se sube una nueva.
    $stmtImgActual = mysqli_prepare($conexion, "SELECT imagen FROM productos WHERE codigo = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtImgActual, "s", $codigo_base);
    mysqli_stmt_execute($stmtImgActual);
    $imagenActual = mysqli_stmt_get_result($stmtImgActual)->fetch_assoc()['imagen'] ?? null;
    $imagen = $imagenActual;

    try {
        $nuevaImagen = subirImagenReferencia($_FILES['imagen'] ?? [], 'productos', $codigo_base);
        if ($nuevaImagen !== null) {
            eliminarImagenReferencia($imagenActual, 'productos');
            $imagen = $nuevaImagen;
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        header('Location: ' . url('modules/productos/editar.php?id=' . urlencode($codigo_base)));
        exit;
    }

    $upd = mysqli_prepare($conexion, "UPDATE productos SET descripcion=?, codigo_barras=?, precio_compra=?, precio_venta=?, stock=?, categoria=?, imagen=?, caracteristicas=?, especificaciones_tecnicas=?, tipo_impuesto=? WHERE codigo=?");
    mysqli_stmt_bind_param($upd, "ssddissssss", $descripcion, $codigo_barras, $precio_compra, $precio_venta, $stock, $categoria, $imagen, $caracteristicas, $especificaciones_tecnicas, $tipo_impuesto, $codigo_base);

    if (mysqli_stmt_execute($upd)) {
        flash('success', 'Producto actualizado correctamente.');
    } elseif (mysqli_errno($conexion) === 1062) {
        flash('error', 'Ese código de barras ya está usado por otro producto.');
    } else {
        flash('error', 'Error al actualizar: ' . mysqli_error($conexion));
    }
    header('Location: ' . url('modules/productos/editar.php?id=' . urlencode($codigo_base)));
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM productos WHERE codigo = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $codigo_producto);
mysqli_stmt_execute($stmt);
$resultado_data = mysqli_stmt_get_result($stmt);

if (!$resultado_data || mysqli_num_rows($resultado_data) === 0) {
    flash('error', 'Producto no encontrado con el código: ' . $codigo_producto);
    header('Location: ' . url('modules/productos/listado.php'));
    exit;
}
$producto = mysqli_fetch_assoc($resultado_data);

$page_title = 'Editar Producto';
$page_subtitle = 'Productos · ' . $producto['codigo'];
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-pencil-square"></i> Editar Producto</h1>
    <p>Modificando: <strong><?= h($producto['codigo']) ?></strong></p>
</div>

<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST" action="<?= url('modules/productos/editar.php?id=' . urlencode($producto['codigo'])) ?>" enctype="multipart/form-data">
            <input type="hidden" name="codigo_base" value="<?= h($producto['codigo']) ?>">

            <div class="form-grid">
                <div class="field-group">
                    <label>Código del producto (SKU)</label>
                    <input type="text" value="<?= h($producto['codigo']) ?>" class="form-control" readonly>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-upc-scan"></i> Código de Barras (opcional)</label>
                    <div class="input-group">
                        <input type="text" name="codigo_barras" id="inputCodigoBarras" class="form-control" value="<?= h($producto['codigo_barras'] ?? '') ?>" placeholder="Escanea, escribe, o genera uno interno">
                        <button type="button" class="btn btn-outline-secondary" onclick="ERPEscaner.abrir(function(c){ document.getElementById('inputCodigoBarras').value = c; })" title="Escanear con cámara">
                            <i class="bi bi-camera"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="generarCodigoInterno()" title="Generar código interno">
                            <i class="bi bi-magic"></i>
                        </button>
                    </div>
                    <?php if (!empty($producto['codigo_barras'])): ?>
                        <a href="#" class="d-inline-block mt-2"
                            onclick="event.preventDefault(); ERP.verDocumento('<?= url('modules/productos/etiqueta.php?codigo=' . urlencode($producto['codigo'])) ?>', 'Etiqueta de <?= h($producto['descripcion']) ?>', '<?= url('modules/productos/editar.php?id=' . urlencode($producto['codigo'])) ?>')">
                            <small><i class="bi bi-tag"></i> Ver / Imprimir etiqueta con código de barras</small>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <label>Nombre / Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="<?= h($producto['descripcion']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Precio de Compra</label>
                    <input type="number" name="precio_compra" step="0.01" class="form-control" value="<?= h($producto['precio_compra']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Precio de Venta</label>
                    <input type="number" name="precio_venta" step="0.01" class="form-control" value="<?= h($producto['precio_venta']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?= h($producto['stock']) ?>" required>
                </div>
                <div class="field-group">
                    <label>Categoría</label>
                    <select name="categoria" class="form-select" required>
                        <?php foreach (['Tecnologia' => 'Tecnología', 'Repuestos' => 'Repuestos', 'Accesorios' => 'Accesorios', 'Servicios' => 'Servicios', 'Otros' => 'Otros'] as $val => $label): ?>
                            <option value="<?= h($val) ?>" <?= $producto['categoria'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>Tipo de Impuesto (IGV)</label>
                    <select name="tipo_impuesto" class="form-select" required>
                        <?php foreach (['10' => 'Gravado (18% IGV)', '20' => 'Exonerado de IGV', '30' => 'Inafecto al IGV'] as $val => $label): ?>
                            <option value="<?= h($val) ?>" <?= $producto['tipo_impuesto'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Imagen de Referencia</label>
                    <?php if (!empty($producto['imagen'])): ?>
                        <div class="mb-2">
                            <img src="<?= url('uploads/productos/' . $producto['imagen']) ?>" alt="Imagen actual" style="width:110px; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--n-200);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB. Deja en blanco para conservar la actual.</small>
                </div>
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label><i class="bi bi-stars"></i> Características (para la Galería de Productos)</label>
                    <textarea name="caracteristicas" class="form-control" rows="2" placeholder="Descripción corta y vendible..."><?= h($producto['caracteristicas'] ?? '') ?></textarea>
                </div>
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label><i class="bi bi-list-check"></i> Especificaciones Técnicas (para la Galería de Productos)</label>
                    <textarea name="especificaciones_tecnicas" class="form-control" rows="3" placeholder="Detalle técnico, un dato por línea..."><?= h($producto['especificaciones_tecnicas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="text-end mt-2">
                <button type="submit" name="actualizar" class="btn btn-success"><i class="bi bi-save"></i> Guardar cambios</button>
                <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Cancelar y Volver</a>
            </div>
        </form>
    </div>
</div>

<script>
async function generarCodigoInterno() {
    try {
        const res = await fetch('<?= url('modules/productos/generar_codigo_barras.php') ?>');
        const data = await res.json();
        if (data.success) {
            document.getElementById('inputCodigoBarras').value = data.codigo;
        } else {
            alert(data.message || 'No se pudo generar el código.');
        }
    } catch (e) {
        alert('Error de conexión al generar el código.');
    }
}
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
