<?php
/**
 * modules/productos/galeria.php — NUEVO.
 * ------------------------------------------------------------------
 * Galería de Productos: vista tipo catálogo (cuadrícula con imagen,
 * nombre y precio) pensada para mostrarle al cliente, no solo para
 * gestión interna como el Listado General. Al hacer clic en un
 * producto se abre una ficha con dos pestañas — "Características" y
 * "Especificaciones Técnicas" — tal como se pidió, más un botón para
 * compartir el producto por WhatsApp.
 *
 * Los datos completos de cada producto se embeben en el HTML (atributo
 * data-producto en JSON) para que el modal se pinte al instante con
 * JavaScript, sin una consulta AJAX adicional por cada clic.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$resultado = mysqli_query($conexion, "SELECT * FROM productos ORDER BY categoria ASC, descripcion ASC");
$productos = [];
$categorias = [];
if ($resultado) {
    while ($p = mysqli_fetch_assoc($resultado)) {
        $productos[] = $p;
        if (!empty($p['categoria'])) { $categorias[$p['categoria']] = true; }
    }
}
$categorias = array_keys($categorias);
sort($categorias);

$iconosCategoria = [
    'Tecnologia' => 'bi-cpu',
    'Repuestos' => 'bi-tools',
    'Accesorios' => 'bi-usb-plug',
    'Servicios' => 'bi-gear-wide-connected',
    'Otros' => 'bi-box-seam',
];

$page_title = 'Galería de Productos';
$page_subtitle = 'Productos · Vista de catálogo';
$active_menu = 'productos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-grid-3x3-gap-fill"></i> Galería de Productos</h1>
        <p>Explora el catálogo con imágenes, precios y detalle de cada producto. Ideal para mostrarle al cliente.</p>
    </div>
    <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-outline-secondary no-print"><i class="bi bi-list-ul"></i> Ver listado de gestión</a>
</div>

<div class="galeria-toolbar no-print">
    <div class="galeria-search">
        <i class="bi bi-search"></i>
        <input type="text" id="galeriaBuscar" placeholder="Buscar producto por nombre o código...">
    </div>
    <div class="galeria-chips" id="galeriaChips">
        <button type="button" class="chip active" data-filtro="__todos__"><i class="bi bi-grid"></i> Todos</button>
        <?php foreach ($categorias as $cat): ?>
            <button type="button" class="chip" data-filtro="<?= h($cat) ?>">
                <i class="bi <?= h($iconosCategoria[$cat] ?? 'bi-tag') ?>"></i> <?= h($cat === 'Tecnologia' ? 'Tecnología' : $cat) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<div class="galeria-grid" id="galeriaGrid">
    <?php foreach ($productos as $p): ?>
        <?php
            $agotado = (int)$p['stock'] === 0;
            $stockBajo = !$agotado && (int)$p['stock'] < 10;
            $dataProducto = json_encode([
                'codigo' => $p['codigo'],
                'descripcion' => $p['descripcion'],
                'precio_venta' => number_format((float)$p['precio_venta'], 2),
                'categoria' => $p['categoria'],
                'stock' => (int)$p['stock'],
                'imagen' => !empty($p['imagen']) ? url('uploads/productos/' . $p['imagen']) : null,
                'caracteristicas' => $p['caracteristicas'] ?? '',
                'especificaciones' => $p['especificaciones_tecnicas'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
        ?>
        <div class="galeria-card" data-nombre="<?= h(strtolower($p['descripcion'] . ' ' . $p['codigo'])) ?>" data-categoria="<?= h($p['categoria'] ?? '') ?>" data-producto='<?= h($dataProducto) ?>'>
            <div class="galeria-card-img">
                <?php if (!empty($p['imagen'])): ?>
                    <img src="<?= url('uploads/productos/' . $p['imagen']) ?>" alt="<?= h($p['descripcion']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="galeria-card-img-placeholder"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <?php if ($agotado): ?>
                    <span class="galeria-ribbon ribbon-agotado">Agotado</span>
                <?php elseif ($stockBajo): ?>
                    <span class="galeria-ribbon ribbon-bajo">Pocas unidades</span>
                <?php endif; ?>
            </div>
            <div class="galeria-card-body">
                <span class="galeria-card-cat"><?= h($p['categoria'] === 'Tecnologia' ? 'Tecnología' : ($p['categoria'] ?: 'General')) ?></span>
                <h4><?= h($p['descripcion']) ?></h4>
                <div class="galeria-card-precio">S/ <?= number_format((float)$p['precio_venta'], 2) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p id="galeriaSinResultados" class="text-center text-muted py-5" style="display:none;">
    <i class="bi bi-search"></i> No se encontraron productos con ese filtro.
</p>

<!-- Modal de ficha de producto -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mpNombre"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-5 text-center">
            <img id="mpImagen" src="" alt="" style="width:100%; max-width:260px; aspect-ratio:1/1; object-fit:cover; border-radius:12px; border:1px solid var(--n-200); display:none;">
            <div id="mpImagenPlaceholder" class="galeria-card-img-placeholder" style="width:100%; max-width:260px; aspect-ratio:1/1; border-radius:12px; margin:0 auto;"><i class="bi bi-image"></i></div>
            <div class="mt-2"><span id="mpCategoria" class="galeria-card-cat"></span></div>
            <div id="mpPrecio" class="galeria-card-precio mt-1" style="font-size:1.6rem;"></div>
            <div id="mpStock" class="small mt-1"></div>
          </div>
          <div class="col-md-7">
            <div class="btn-group w-100 mb-3" role="group">
              <button type="button" class="btn btn-outline-secondary active" id="btnTabCaracteristicas" onclick="mostrarTab('caracteristicas')"><i class="bi bi-stars"></i> Características</button>
              <button type="button" class="btn btn-outline-secondary" id="btnTabEspecificaciones" onclick="mostrarTab('especificaciones')"><i class="bi bi-list-check"></i> Especificaciones Técnicas</button>
            </div>
            <div id="mpCaracteristicas" class="galeria-tab-content"></div>
            <div id="mpEspecificaciones" class="galeria-tab-content" style="display:none;"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" style="background:#25D366; color:#fff; font-weight:600;" onclick="compartirProductoWhatsapp()">
            <i class="bi bi-whatsapp"></i> Compartir por WhatsApp
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
let productoActualModal = null;

function abrirModalProducto(datos) {
    productoActualModal = datos;
    document.getElementById('mpNombre').textContent = datos.descripcion;
    document.getElementById('mpCategoria').textContent = datos.categoria === 'Tecnologia' ? 'Tecnología' : (datos.categoria || 'General');
    document.getElementById('mpPrecio').textContent = 'S/ ' + datos.precio_venta;

    const stockEl = document.getElementById('mpStock');
    if (datos.stock === 0) {
        stockEl.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Sin stock disponible</span>';
    } else if (datos.stock < 10) {
        stockEl.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Pocas unidades (' + datos.stock + ')</span>';
    } else {
        stockEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Disponible</span>';
    }

    const img = document.getElementById('mpImagen');
    const placeholder = document.getElementById('mpImagenPlaceholder');
    if (datos.imagen) {
        img.onerror = function () {
            img.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.title = 'Esta imagen no está disponible en este equipo (revisa que la carpeta uploads/ se haya copiado junto con el sistema)';
        };
        img.src = datos.imagen;
        img.style.display = 'block';
        placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'flex';
    }

    document.getElementById('mpCaracteristicas').innerHTML = datos.caracteristicas
        ? datos.caracteristicas.replace(/\n/g, '<br>')
        : '<span class="text-muted">Este producto aún no tiene características cargadas.</span>';
    document.getElementById('mpEspecificaciones').innerHTML = datos.especificaciones
        ? datos.especificaciones.replace(/\n/g, '<br>')
        : '<span class="text-muted">Este producto aún no tiene especificaciones técnicas cargadas.</span>';

    // Si no hay características pero sí especificaciones, abrir directo en esa pestaña.
    mostrarTab(!datos.caracteristicas && datos.especificaciones ? 'especificaciones' : 'caracteristicas');

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto'));
    modal.show();
}

function mostrarTab(tab) {
    const esCaract = tab === 'caracteristicas';
    document.getElementById('mpCaracteristicas').style.display = esCaract ? 'block' : 'none';
    document.getElementById('mpEspecificaciones').style.display = esCaract ? 'none' : 'block';
    document.getElementById('btnTabCaracteristicas').classList.toggle('active', esCaract);
    document.getElementById('btnTabEspecificaciones').classList.toggle('active', !esCaract);
}

function compartirProductoWhatsapp() {
    if (!productoActualModal) return;
    const d = productoActualModal;
    let mensaje = `Hola, te comparto este producto de Eros Tecnología:\n\n`;
    mensaje += `*${d.descripcion}*\n`;
    mensaje += `Precio: S/ ${d.precio_venta}\n`;
    if (d.caracteristicas) { mensaje += `\n${d.caracteristicas}\n`; }
    if (d.imagen) { mensaje += `\n📷 Ver imagen: ${d.imagen}\n`; }
    mensaje += `\n¿Te interesa? Escríbenos y coordinamos.`;

    // Sin número fijo: se abre el selector de contactos de WhatsApp para que
    // el operador elija a quién enviárselo (no siempre es al mismo cliente).
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(mensaje)}`, '_blank');
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.galeria-card').forEach(function (card) {
        card.addEventListener('click', function () {
            abrirModalProducto(JSON.parse(card.getAttribute('data-producto')));
        });
    });

    const grid = document.getElementById('galeriaGrid');
    const sinResultados = document.getElementById('galeriaSinResultados');
    const inputBuscar = document.getElementById('galeriaBuscar');
    const chips = document.querySelectorAll('#galeriaChips .chip');
    let filtroCategoria = '__todos__';

    function aplicarFiltros() {
        const texto = inputBuscar.value.trim().toLowerCase();
        let visibles = 0;
        document.querySelectorAll('.galeria-card').forEach(function (card) {
            const coincideTexto = texto === '' || card.getAttribute('data-nombre').includes(texto);
            const coincideCategoria = filtroCategoria === '__todos__' || card.getAttribute('data-categoria') === filtroCategoria;
            const visible = coincideTexto && coincideCategoria;
            card.style.display = visible ? '' : 'none';
            if (visible) visibles++;
        });
        sinResultados.style.display = visibles === 0 ? 'block' : 'none';
    }

    inputBuscar.addEventListener('input', aplicarFiltros);
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            filtroCategoria = chip.getAttribute('data-filtro');
            aplicarFiltros();
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
