<?php
require_once __DIR__ . '/config/conexion_web.php';
require_once __DIR__ . '/includes/funciones_web.php';

$tituloPagina = 'Inicio';

// Trae los productos reales cargados por el staff en LuarSoft.
$productos = [];
$res = mysqli_query($conexion, "SELECT id, descripcion, precio_venta, stock, imagen, categoria FROM productos ORDER BY fecha_registro DESC LIMIT 16");
if ($res) {
    while ($fila = mysqli_fetch_assoc($res)) {
        $productos[] = $fila;
    }
}

$acceso = tieneAccesoCompleto();
// Sin cuenta aprobada, solo se ven nítidas las primeras 4 tarjetas.
$limiteVisible = 4;

// Categorías reales presentes en el catálogo (para los botones de filtro).
$categorias = [];
foreach ($productos as $p) {
    if (!empty($p['categoria']) && !in_array($p['categoria'], $categorias, true)) {
        $categorias[] = $p['categoria'];
    }
}

$totalCatalogo = 0;
$resTotal = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM productos");
if ($resTotal) {
    $totalCatalogo = (int)mysqli_fetch_assoc($resTotal)['total'];
}

// Un ícono simple por categoría real (nada inventado, solo estilo).
$iconosCategoria = [
    'Tecnologia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/></svg>',
    'Accesorios' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14a8 8 0 0 1 16 0"/><rect x="2" y="14" width="5" height="7" rx="1.5"/><rect x="17" y="14" width="5" height="7" rx="1.5"/></svg>',
    'Repuestos'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a4 4 0 0 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 1 5.4-5.4l-2.6 2.6-2-2 2.6-2.6z"/></svg>',
    'Servicios'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>',
    'Otros'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>',
];
$iconoTodos = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>';

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-top">
      <p class="eyebrow"><span class="dot"></span>Sistema en línea</p>
      <div class="ctas">
        <a href="#catalogo" class="btn btn-azul">Ver catálogo</a>
        <a href="https://wa.me/51949092352" target="_blank" class="btn btn-outline">Cotiza por WhatsApp</a>
      </div>
    </div>
    <h1>REPARA.<br>ACCEDE.<br><span>CONFÍA.</span></h1>
  </div>
  <div class="hero-strip" style="background-image:url('<?= RUTA_SISTEMA_WEB ?>assets/img/tienda-eros.jpg')">
    <p class="lead">Servicio técnico, accesorios e instalación de software en Imperial, Cañete.</p>
  </div>
</section>

<div class="carrusel" id="carruselAnuncios">
  <div class="carrusel-slide foto activo" style="background-image:url('<?= RUTA_SISTEMA_WEB ?>assets/img/tienda-eros.jpg')">
    <div class="carrusel-overlay"></div>
    <div class="contenido">
      <span>Bienvenido</span>
      <h3>10 años atendiendo Imperial, Cañete</h3>
      <p>Servicio técnico, accesorios e instalación de software en un solo lugar.</p>
      <a href="#servicios" class="btn btn-blanco">Ver servicios</a>
    </div>
  </div>
  <div class="carrusel-slide azul">
    <div class="contenido">
      <span>Diagnóstico rápido</span>
      <h3>Tu equipo, revisado en 48 horas</h3>
      <p>Diagnóstico honesto antes de cualquier reparación.</p>
      <a href="https://wa.me/51949092352" target="_blank" class="btn btn-blanco">Cotiza ahora</a>
    </div>
  </div>
  <?php if (!empty($productos)):
    $slideProd = $productos[0];
    $imgSlide = !empty($slideProd['imagen']) ? RUTA_SISTEMA_WEB . 'uploads/productos/' . $slideProd['imagen'] : null;
  ?>
  <div class="carrusel-slide foto" style="background-image:url('<?= h($imgSlide) ?>')">
    <div class="carrusel-overlay"></div>
    <div class="contenido">
      <span>Recién llegado</span>
      <h3><?= h($slideProd['descripcion']) ?></h3>
      <p>Accesorios originales, siempre en stock.</p>
      <a href="#catalogo" class="btn btn-blanco">Ver catálogo</a>
    </div>
  </div>
  <?php endif; ?>
  <div class="carrusel-slide rojo">
    <div class="contenido">
      <span>Acceso completo</span>
      <h3>Regístrate y desbloquea todo el catálogo</h3>
      <p>Precios reales, stock y seguimiento de tus equipos.</p>
      <a href="registro.php" class="btn btn-blanco">Solicitar registro</a>
    </div>
  </div>
  <button type="button" class="carrusel-flecha izq" aria-label="Anterior">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
  </button>
  <button type="button" class="carrusel-flecha der" aria-label="Siguiente">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
  </button>
  <div class="carrusel-dots"></div>
</div>

<div class="confianza">
  <div class="wrap">
    <span class="chip">Compatible con HP</span>
    <span class="chip">Epson</span>
    <span class="chip">Windows</span>
    <span class="chip">Office 365</span>
    <span class="chip">Sistemas EcoTank</span>
  </div>
</div>

<?php if (count($categorias) > 1): ?>
<div class="cat-nav">
  <div class="wrap">
    <button type="button" class="activo" data-filtro="todos"><?= $iconoTodos ?> Todos</button>
    <?php foreach ($categorias as $c): ?>
      <button type="button" data-filtro="<?= h($c) ?>"><?= $iconosCategoria[$c] ?? $iconoTodos ?> <?= h($c) ?></button>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="diag">
  <div class="wrap">
    <div><span class="k">EQUIPOS ATENDIDOS</span><span class="v">512</span></div>
    <div><span class="k">TIEMPO PROMEDIO</span><span class="v">48 horas</span></div>
    <div><span class="k">GARANTÍA</span><span class="v">30 días</span></div>
    <div><span class="k">EXPERIENCIA</span><span class="v">10 años</span></div>
  </div>
</div>

<section id="servicios">
  <div class="wrap serv-grid">
    <div class="serv-photo">
      <img src="<?= RUTA_SISTEMA_WEB ?>assets/img/tienda-eros.jpg" alt="Taller Eros Tecnología">
    </div>
    <div class="serv-list">
      <span class="kicker">Lo que resolvemos</span>
      <h2>Un problema de tecnología, una sola visita</h2>
      <div class="serv-item"><h4>Mantenimiento preventivo</h4><p>Limpieza y revisión antes de que falle</p></div>
      <div class="serv-item"><h4>Instalación de software</h4><p>Office, sistema operativo y más</p></div>
      <div class="serv-item"><h4>Impresoras y copias</h4><p>Reparación, tinta y suministros</p></div>
      <div class="serv-item"><h4>Asesoría técnica</h4><p>Te recomendamos antes de comprar</p></div>
    </div>
  </div>
</section>

<section id="catalogo" style="padding-top:0;">
  <div class="wrap">
    <div class="section-head reveal">
      <h2>Catálogo</h2>
      <p>
        <?php if ($acceso): ?>
          Estás viendo el catálogo completo con precios y stock reales.
        <?php else: ?>
          Explora libremente. <a href="registro.php" style="color:var(--azul); font-weight:600;">Regístrate</a> para desbloquear precios, stock y el resto del catálogo.
        <?php endif; ?>
      </p>
      <div class="buscador-cat">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="text" id="buscarProducto" placeholder="Busca por nombre de producto...">
      </div>
      <span class="contador-cat"><?= $totalCatalogo ?> productos en el catálogo</span>
    </div>

    <?php if (empty($productos)): ?>
      <p style="color:var(--gris); font-size:14px;">Aún no hay productos cargados en el sistema.</p>
    <?php else: ?>
      <div class="grid-cat" id="gridCatalogo">
        <?php foreach ($productos as $i => $p):
          $bloqueado = !$acceso && $i >= $limiteVisible;
          $esNuevo = strtotime($p['fecha_registro'] ?? 'now') >= strtotime('-20 days');
          $imgSrc = !empty($p['imagen'])
              ? RUTA_SISTEMA_WEB . 'uploads/productos/' . $p['imagen']
              : RUTA_SISTEMA_WEB . 'assets/img/eros.jpg';
        ?>
        <div class="prod reveal <?= $bloqueado ? 'locked' : '' ?>" data-categoria="<?= h($p['categoria']) ?>" data-nombre="<?= h(mb_strtolower($p['descripcion'])) ?>">
          <?php if (!$bloqueado): ?>
            <?= $p['stock'] > 0 ? '<span class="badge-stock">Disponible</span>' : '<span class="badge-agotado">Agotado</span>' ?>
            <?php if ($esNuevo): ?><span class="badge-nuevo">Nuevo</span><?php endif; ?>
          <?php endif; ?>
          <img src="<?= h($imgSrc) ?>" alt="<?= h($p['descripcion']) ?>">
          <?php if ($bloqueado): ?>
            <div class="lock-overlay">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
              <p>Inicia sesión para ver</p>
            </div>
          <?php endif; ?>
          <div class="info">
            <p class="name"><?= h($p['descripcion']) ?></p>
            <p class="price"><?= $bloqueado ? 'S/ ••.••' : moneda($p['precio_venta']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <p id="sinResultados" style="display:none; color:var(--gris); font-size:14px; margin-top:16px;">No encontramos productos con ese nombre.</p>
    <?php endif; ?>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="section-head reveal">
      <h2>Nuestro taller, en imágenes</h2>
      <p>Así trabajamos y así llegan tus accesorios: originales, en stock y listos para llevar.</p>
    </div>
    <div class="galeria-grid reveal">
      <div class="g-big">
        <img src="<?= RUTA_SISTEMA_WEB ?>assets/img/tienda-eros.jpg" alt="Fachada de Eros Tecnología">
        <span class="g-tag">Nuestro local en Imperial</span>
      </div>
      <?php
        $muestraGaleria = array_slice($productos, 0, 4);
        foreach ($muestraGaleria as $p):
          $imgG = !empty($p['imagen']) ? RUTA_SISTEMA_WEB . 'uploads/productos/' . $p['imagen'] : RUTA_SISTEMA_WEB . 'assets/img/eros.jpg';
      ?>
        <div><img src="<?= h($imgG) ?>" alt="<?= h($p['descripcion']) ?>"></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!$acceso): ?>
<section class="comparativa">
  <div class="wrap">
    <div class="section-head reveal">
      <h2>¿Visitante o cliente registrado?</h2>
      <p>La diferencia es rápida de notar.</p>
    </div>
    <div class="comp-grid reveal">
      <div class="comp-card">
        <h3>Sin registrarte</h3>
        <ul>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>Ves solo 4 productos con precio</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>No puedes ver el stock real</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>Sin seguimiento de tu equipo en línea</li>
        </ul>
      </div>
      <div class="comp-card destacado">
        <h3>Con tu cuenta aprobada</h3>
        <ul>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>Catálogo completo con precios reales</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>Stock actualizado al instante</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>Seguimiento de tus reparaciones</li>
        </ul>
        <a href="registro.php" class="btn btn-blanco">Registrarme ahora</a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$acceso): ?>
<section class="cta-registro reveal" style="padding-top:56px;">
  <div class="wrap">
    <div>
      <h2>Desbloquea el catálogo completo</h2>
      <p>Regístrate, valida tu cuenta por WhatsApp y accede a precios, stock real y seguimiento de tus equipos.</p>
    </div>
    <a href="registro.php" class="btn btn-blanco">Solicitar registro</a>
  </div>
</section>

<section class="pasos">
  <div class="wrap">
    <div class="section-head reveal">
      <h2>Registrarte toma menos de un minuto</h2>
    </div>
    <div class="pasos-grid reveal">
      <div class="paso"><p class="num">01</p><h4>Completa el formulario</h4><p>Nombre, teléfono y correo — nada más.</p></div>
      <div class="paso"><p class="num">02</p><h4>Te escribimos por WhatsApp</h4><p>Confirmamos tu identidad y te damos acceso.</p></div>
      <div class="paso"><p class="num">03</p><h4>Ingresa y listo</h4><p>Ya puedes ver precios y seguir tus reparaciones.</p></div>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
(function () {
  var carrusel = document.getElementById('carruselAnuncios');
  if (!carrusel) return;
  var slides = carrusel.querySelectorAll('.carrusel-slide');
  var contDots = carrusel.querySelector('.carrusel-dots');
  var indice = 0;
  var timer;

  slides.forEach(function (_, i) {
    var punto = document.createElement('button');
    if (i === 0) punto.className = 'activo';
    punto.addEventListener('click', function () { irA(i); });
    contDots.appendChild(punto);
  });
  var puntos = contDots.querySelectorAll('button');

  function irA(i) {
    slides[indice].classList.remove('activo');
    puntos[indice].classList.remove('activo');
    indice = (i + slides.length) % slides.length;
    slides[indice].classList.add('activo');
    puntos[indice].classList.add('activo');
  }

  function auto() {
    timer = setInterval(function () { irA(indice + 1); }, 5000);
  }

  carrusel.querySelector('.carrusel-flecha.izq').addEventListener('click', function () { irA(indice - 1); clearInterval(timer); auto(); });
  carrusel.querySelector('.carrusel-flecha.der').addEventListener('click', function () { irA(indice + 1); clearInterval(timer); auto(); });
  carrusel.addEventListener('mouseenter', function () { clearInterval(timer); });
  carrusel.addEventListener('mouseleave', auto);

  auto();
})();
</script>

<script>
// Filtro combinado: categoría (barra de íconos) + búsqueda por nombre
var filtroActivo = 'todos';

function aplicarFiltros() {
  var texto = (document.getElementById('buscarProducto').value || '').toLowerCase().trim();
  var visibles = 0;
  document.querySelectorAll('#gridCatalogo .prod').forEach(function (card) {
    var coincideCategoria = (filtroActivo === 'todos' || card.dataset.categoria === filtroActivo);
    var coincideTexto = (texto === '' || card.dataset.nombre.indexOf(texto) !== -1);
    var visible = coincideCategoria && coincideTexto;
    card.style.display = visible ? '' : 'none';
    if (visible) visibles++;
  });
  var sinResultados = document.getElementById('sinResultados');
  if (sinResultados) sinResultados.style.display = visibles === 0 ? 'block' : 'none';
}

document.querySelectorAll('.cat-nav button').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.cat-nav button').forEach(function (b) { b.classList.remove('activo'); });
    btn.classList.add('activo');
    filtroActivo = btn.dataset.filtro;
    document.querySelector('#catalogo').scrollIntoView({ behavior: 'smooth', block: 'start' });
    aplicarFiltros();
  });
});

var inputBuscar = document.getElementById('buscarProducto');
if (inputBuscar) inputBuscar.addEventListener('input', aplicarFiltros);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
