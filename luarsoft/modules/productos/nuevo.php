<?php
/**
 * modules/productos/nuevo.php
 * ------------------------------------------------------------------
 * Reemplaza a "a#U00f1adir_producto.php" (cuyo nombre real quedó mal
 * codificado como "añadir_producto.php", generando un enlace roto
 * en el sistema original) y a "guardar_producto.php". Aquí ambos
 * pasos (formulario + guardado) están unificados en un solo flujo
 * POST-Redirect-GET, con sentencias preparadas.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo        = limpiar($_POST['codigo']);
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $codigo_barras = $codigo_barras !== '' ? $codigo_barras : null;
    $descripcion   = limpiar($_POST['descripcion']);
    $precio_compra = (float)$_POST['precio_compra'];
    $precio_venta  = (float)$_POST['precio_venta'];
    $stock         = (int)$_POST['stock'];
    $categoria     = limpiar($_POST['categoria']);
    $tipo_impuesto = limpiar($_POST['tipo_impuesto']);
    $caracteristicas = trim($_POST['caracteristicas'] ?? '');
    $especificaciones_tecnicas = trim($_POST['especificaciones_tecnicas'] ?? '');
    $error_imagen  = '';
    $imagen        = null;

    try {
        $imagen = subirImagenReferencia($_FILES['imagen'] ?? [], 'productos', $codigo);
    } catch (RuntimeException $e) {
        $error_imagen = $e->getMessage();
    }

    if ($error_imagen !== '') {
        flash('error', $error_imagen);
        header('Location: ' . url('modules/productos/nuevo.php'));
        exit;
    }

    $stmt = mysqli_prepare($conexion, "INSERT INTO productos (codigo, codigo_barras, descripcion, precio_compra, precio_venta, stock, categoria, imagen, caracteristicas, especificaciones_tecnicas, tipo_impuesto) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "sssddisssss", $codigo, $codigo_barras, $descripcion, $precio_compra, $precio_venta, $stock, $categoria, $imagen, $caracteristicas, $especificaciones_tecnicas, $tipo_impuesto);

    if (mysqli_stmt_execute($stmt)) {
        flash('success', "Producto «$descripcion» registrado correctamente.");
        header('Location: ' . url('modules/productos/listado.php'));
        exit;
    } elseif (mysqli_errno($conexion) === 1062) {
        flash('error', 'Ese código de barras ya está usado por otro producto.');
    } else {
        flash('error', 'Error al guardar el producto: ' . mysqli_error($conexion));
    }
}

$page_title = 'Registrar Producto';
$page_subtitle = 'Productos · Nuevo registro';
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-plus-lg"></i> Registrar Nuevo Producto</h1>
    <p>Agrega un producto o repuesto al inventario.</p>
</div>

<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST" action="" id="formProducto" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label>Nombre / Descripción</label>
                    <input type="text" name="descripcion" id="inputDescripcion" class="form-control" placeholder="Ej: Cartucho de Tinta 101" required>
                </div>
                <div class="field-group">
                    <label>Precio de Compra</label>
                    <input type="number" name="precio_compra" step="0.01" class="form-control" placeholder="0.00" required>
                </div>
                <div class="field-group">
                    <label>Precio de Venta</label>
                    <input type="number" name="precio_venta" step="0.01" class="form-control" placeholder="0.00" required>
                </div>
                <div class="field-group">
                    <label>Stock inicial</label>
                    <input type="number" name="stock" class="form-control" placeholder="0" required>
                </div>
                <div class="field-group">
                    <label>Categoría</label>
                    <select name="categoria" id="selectCategoria" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="Tecnologia">Tecnología</option>
                        <option value="Repuestos">Repuestos</option>
                        <option value="Accesorios">Accesorios</option>
                        <option value="Servicios">Servicios</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Tipo de Impuesto (IGV)</label>
                    <select name="tipo_impuesto" class="form-select" required>
                        <option value="">Seleccione el Tipo de Impuesto...</option>
                        <option value="10">Gravado (18% IGV)</option>
                        <option value="20">Exonerado de IGV</option>
                        <option value="30">Inafecto al IGV</option>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Imagen de Referencia (opcional)</label>
                    <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB.</small>
                </div>
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label><i class="bi bi-stars"></i> Características (para la Galería de Productos)</label>
                    <textarea name="caracteristicas" class="form-control" rows="2" placeholder="Descripción corta y vendible, ej: Cartucho original, fácil instalación, ideal para uso diario en oficina."></textarea>
                </div>
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label><i class="bi bi-list-check"></i> Especificaciones Técnicas (para la Galería de Productos)</label>
                    <textarea name="especificaciones_tecnicas" class="form-control" rows="3" placeholder="Detalle técnico, un dato por línea, ej:&#10;Compatibilidad: HP DeskJet 1015/1515&#10;Rendimiento: 120 páginas"></textarea>
                </div>

                <div class="field-group" style="grid-column: 1 / -1; border-top: 1px dashed var(--n-200, #e5e7eb); margin-top: 8px; padding-top: 16px;">
                    <small class="text-muted d-block mb-2"><i class="bi bi-magic"></i> Lo siguiente se genera solo — no hace falta que completes nada aquí abajo.</small>
                </div>
                <div class="field-group">
                    <label class="text-muted">Código del producto (SKU) <i class="bi bi-magic" title="Se genera automáticamente"></i></label>
                    <div class="input-group">
                        <input type="text" name="codigo" id="inputCodigoSku" class="form-control" placeholder="Se genera solo al elegir categoría y nombre" readonly style="background:var(--n-50,#f9fafb);">
                        <button type="button" class="btn btn-outline-secondary" id="btnRegenerarSku" title="Regenerar código automático">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                    <small class="text-muted">Se arma solo como CATEGORÍA-PRODUCTO-N° (ej. TEC-CAR-001) al completar la categoría y el nombre.</small>
                </div>
                <div class="field-group">
                    <label class="text-muted"><i class="bi bi-upc-scan"></i> Código de Barras (opcional)</label>
                    <div class="input-group">
                        <input type="text" name="codigo_barras" id="inputCodigoBarras" class="form-control" placeholder="Escanea, escribe, o genera uno interno">
                        <button type="button" class="btn btn-outline-secondary" onclick="ERPEscaner.abrir(function(c){ document.getElementById('inputCodigoBarras').value = c; })" title="Escanear con cámara">
                            <i class="bi bi-camera"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="generarCodigoInterno()" title="Generar código interno (para productos sin código de fábrica)">
                            <i class="bi bi-magic"></i>
                        </button>
                    </div>
                    <small class="text-muted">Se dejó vacío a propósito: solo hace falta si vas a escanear o generar uno interno.</small>
                </div>
            </div>

            <div class="text-end mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Producto</button>
                <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-secondary"><i class="bi bi-list-ul"></i> Ver listado</a>
            </div>
        </form>
    </div>
</div>

<script>
// ====================================================================
// AUTORELLENO DEL CÓDIGO DE PRODUCTO (SKU): CATEGORIA-PRODUCTO-N°
// Se dispara al elegir la categoría y/o escribir el nombre, siempre
// que el usuario no haya escrito el código a mano él mismo.
// ====================================================================
(function () {
    const inputCodigo = document.getElementById('inputCodigoSku');
    const selectCategoria = document.getElementById('selectCategoria');
    const inputDescripcion = document.getElementById('inputDescripcion');
    const btnRegenerar = document.getElementById('btnRegenerarSku');

    let codigoEditadoManualmente = false;

    // Si el usuario escribe algo distinto de lo que generamos nosotros,
    // dejamos de tocar el campo hasta que pida regenerarlo.
    inputCodigo.addEventListener('input', () => { codigoEditadoManualmente = true; });

    async function autorellenarSku(forzar = false) {
        const categoria = selectCategoria.value.trim();
        const descripcion = inputDescripcion.value.trim();
        if (categoria === '' || descripcion === '') return;
        if (codigoEditadoManualmente && !forzar) return;

        try {
            const params = new URLSearchParams({ categoria, descripcion });
            const res = await fetch('<?= url('modules/productos/generar_codigo_producto.php') ?>?' + params.toString());
            const data = await res.json();
            if (data.success) {
                inputCodigo.value = data.codigo;
                codigoEditadoManualmente = false; // el valor sigue siendo "automático"
            }
        } catch (e) {
            // Silencioso: el usuario siempre puede escribir el código a mano.
        }
    }

    selectCategoria.addEventListener('change', () => autorellenarSku());
    inputDescripcion.addEventListener('blur', () => autorellenarSku());
    btnRegenerar.addEventListener('click', () => autorellenarSku(true));

    // Seguro final: si por algún corte de red el código quedó vacío,
    // se genera uno de respaldo con la fecha/hora, para no guardar
    // nunca un producto sin código.
    document.getElementById('formProducto').addEventListener('submit', (e) => {
        if (inputCodigo.value.trim() === '') {
            const cat = (selectCategoria.value || 'PROD').substring(0, 3).toUpperCase();
            inputCodigo.value = cat + '-' + Date.now().toString().slice(-8);
        }
    });
})();

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

// Dictado Guiado por Voz: si se llegó aquí diciendo "nuevo producto por voz",
// la URL trae ?dictado=1. Se verifica dentro de "load" porque voz.js se
// carga después de este script (layout_bottom.php va al final).
window.addEventListener('load', function () {
    if (new URLSearchParams(window.location.search).get('dictado') !== '1' || !window.ERPDictado) return;

    ERPDictado.iniciar({
        titulo: "nuevo producto",
        pasos: [
            { campo: "codigo", pregunta: "¿Cuál será el código o SKU del producto?", tipo: "texto" },
            { campo: "descripcion", pregunta: "¿Cuál es el nombre o descripción del producto?", tipo: "texto" },
            { campo: "precio_compra", pregunta: "¿Cuál es el precio de compra?", tipo: "numero" },
            { campo: "precio_venta", pregunta: "¿Cuál es el precio de venta?", tipo: "numero" },
            { campo: "stock", pregunta: "¿Cuánto stock inicial tiene?", tipo: "numero" },
            { campo: "categoria", pregunta: "¿Qué categoría es? Di Tecnología, Repuestos, Accesorios, Servicios, u Otros.", tipo: "opcion",
              opciones: [
                  { valor: "Tecnologia", frases: ["tecnologia"] },
                  { valor: "Repuestos", frases: ["repuestos"] },
                  { valor: "Accesorios", frases: ["accesorios"] },
                  { valor: "Servicios", frases: ["servicios"] },
                  { valor: "Otros", frases: ["otros", "otro"] },
              ] },
            { campo: "tipo_impuesto", pregunta: "¿Es gravado con IGV, exonerado, o inafecto?", tipo: "opcion",
              opciones: [
                  { valor: "10", frases: ["gravado"] },
                  { valor: "20", frases: ["exonerado"] },
                  { valor: "30", frases: ["inafecto"] },
              ] },
        ],
        onCompletar: function (r) {
            document.querySelector('[name="codigo"]').value = r.codigo || '';
            document.querySelector('[name="descripcion"]').value = r.descripcion || '';
            document.querySelector('[name="precio_compra"]').value = r.precio_compra || '';
            document.querySelector('[name="precio_venta"]').value = r.precio_venta || '';
            document.querySelector('[name="stock"]').value = r.stock || '';
            document.querySelector('[name="categoria"]').value = r.categoria || '';
            document.querySelector('[name="tipo_impuesto"]').value = r.tipo_impuesto || '';
            document.getElementById('formProducto').requestSubmit();
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
