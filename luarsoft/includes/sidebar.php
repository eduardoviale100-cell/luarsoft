<?php
/**
 * includes/sidebar.php (v3)
 * ------------------------------------------------------------------
 * Menú lateral generado a partir de una estructura de datos, para
 * poder determinar con precisión —desde PHP, no solo con CSS/JS—
 * cuál es el enlace EXACTO en el que está el usuario. Esto corrige
 * el bug reportado: antes, un grupo entero (ej. "Clientes") y su
 * submódulo activo se pintaban ambos con un bloque rojo sólido.
 * Ahora:
 *   - Solo el enlace hoja exacto recibe el marcador fuerte (borde
 *     lateral + fondo sutil + texto en color acento).
 *   - El título del grupo padre solo recibe un tono de ícono más
 *     claro (group-current), como indicador discreto de sección.
 */

require_once __DIR__ . '/permisos.php';

$ruta_actual = $_SERVER['SCRIPT_NAME'] ?? '';
function _es_actual($ruta_actual, $destino) {
    return substr($ruta_actual, -strlen($destino)) === $destino;
}

$menu = [
    'principal' => [
        'titulo' => 'Principal',
        'items' => [
            ['tipo' => 'link', 'perm' => 'dashboard', 'label' => 'Panel Principal', 'icon' => 'bi-house-door', 'url' => 'index.php'],
            ['tipo' => 'link', 'perm' => 'pos', 'label' => 'Punto de Venta (POS)', 'icon' => 'bi-cart-check-fill', 'url' => 'modules/ventas/pos.php'],
        ],
    ],
    'gestion' => [
        'titulo' => 'Gestión',
        'items' => [
            ['tipo' => 'grupo', 'key' => 'clientes', 'perm' => 'clientes', 'label' => 'Clientes', 'icon' => 'bi-people', 'hijos' => [
                ['label' => 'Añadir Cliente', 'icon' => 'bi-person-plus', 'url' => 'modules/clientes/nuevo.php'],
                ['label' => 'Listado General', 'icon' => 'bi-list-ul', 'url' => 'modules/clientes/listado.php'],
                ['label' => 'Buscar Cliente', 'icon' => 'bi-search', 'url' => 'modules/clientes/buscar.php'],
            ]],
            ['tipo' => 'grupo', 'key' => 'productos', 'perm' => 'productos', 'label' => 'Productos', 'icon' => 'bi-box-seam', 'hijos' => [
                ['label' => 'Añadir Producto', 'icon' => 'bi-plus-circle', 'url' => 'modules/productos/nuevo.php'],
                ['label' => 'Listado General', 'icon' => 'bi-list-ul', 'url' => 'modules/productos/listado.php'],
                ['label' => 'Galería de Productos', 'icon' => 'bi-grid-3x3-gap-fill', 'url' => 'modules/productos/galeria.php'],
                ['label' => 'Control de Stock', 'icon' => 'bi-bar-chart-line', 'url' => 'modules/productos/control_stock.php'],
                ['label' => 'Registrar Compra', 'icon' => 'bi-bag-plus', 'url' => 'modules/compras/nuevo.php'],
                ['label' => 'Registro de Compras', 'icon' => 'bi-bag-check', 'url' => 'modules/compras/listado.php'],
            ]],
            ['tipo' => 'grupo', 'key' => 'tecnicos', 'perm' => 'tecnicos', 'label' => 'Técnicos', 'icon' => 'bi-person-gear', 'hijos' => [
                ['label' => 'Nuevo Técnico', 'icon' => 'bi-person-badge', 'url' => 'modules/tecnicos/nuevo.php'],
                ['label' => 'Listado de Técnicos', 'icon' => 'bi-list-ul', 'url' => 'modules/tecnicos/listado.php'],
            ]],
            ['tipo' => 'grupo', 'key' => 'ordenes', 'perm' => 'ordenes', 'label' => 'Órdenes de Reparación', 'icon' => 'bi-laptop', 'hijos' => [
                ['label' => 'Nueva Orden', 'icon' => 'bi-plus-circle', 'url' => 'modules/ordenes/nuevo.php'],
                ['label' => 'Listado de Órdenes', 'icon' => 'bi-list-ul', 'url' => 'modules/ordenes/listado.php'],
            ]],
        ],
    ],
    'ventas_reportes' => [
        'titulo' => 'Ventas &amp; Reportes',
        'items' => [
            ['tipo' => 'grupo', 'key' => 'ventas', 'perm' => 'ventas', 'label' => 'Ventas', 'icon' => 'bi-receipt', 'hijos' => [
                ['label' => 'Listado de Boletas', 'icon' => 'bi-receipt-cutoff', 'url' => 'modules/ventas/lista_boletas.php'],
                ['label' => 'Listado de Facturas', 'icon' => 'bi-file-earmark-text', 'url' => 'modules/ventas/lista_facturas.php'],
            ]],
            ['tipo' => 'grupo', 'key' => 'reportes', 'perm' => 'reportes', 'label' => 'Reportes', 'icon' => 'bi-graph-up-arrow', 'hijos' => [
                ['label' => 'Lo Más Vendido', 'icon' => 'bi-fire', 'url' => 'modules/reportes/productos_mas_vendidos.php'],
                ['label' => 'Mejores Clientes', 'icon' => 'bi-trophy', 'url' => 'modules/reportes/mejores_clientes.php'],
                ['label' => 'Reporte de Clientes', 'icon' => 'bi-people', 'url' => 'modules/reportes/clientes.php'],
                ['label' => 'Reporte de Productos', 'icon' => 'bi-box-seam', 'url' => 'modules/reportes/productos.php'],
                ['label' => 'Productos Sin Stock', 'icon' => 'bi-exclamation-octagon', 'url' => 'modules/reportes/productos.php?nivel_stock=agotado'],
                ['label' => 'Reporte General de Ventas', 'icon' => 'bi-receipt', 'url' => 'modules/reportes/ventas.php'],
                ['label' => 'Kardex de Inventario', 'icon' => 'bi-journal-text', 'url' => 'modules/reportes/kardex.php'],
                ['label' => 'Servicios del Mes', 'icon' => 'bi-tools', 'url' => 'modules/reportes/servicios_mes.php'],
                ['label' => 'Flujo de Ingresos', 'icon' => 'bi-graph-up', 'url' => 'modules/reportes/flujo_ingresos.php'],
                ['label' => 'Reporte Tributario (IGV)', 'icon' => 'bi-calculator', 'url' => 'modules/reportes/tributario.php'],
            ]],
        ],
    ],
    'sistema' => [
        'titulo' => 'Sistema',
        'items' => [
            ['tipo' => 'link', 'perm' => 'usuarios', 'label' => 'Usuarios', 'icon' => 'bi-person-lock', 'url' => 'modules/usuarios/listado.php'],
        ],
    ],
];

// Matriz de Permisos: se descarta de la navegación todo ítem (link o
// grupo completo) al que el usuario en sesión no tenga acceso. Un
// Administrador ve el menú completo, igual que antes de esta mejora.
foreach ($menu as $bloqueKey => $bloqueDef) {
    $menu[$bloqueKey]['items'] = array_values(array_filter($bloqueDef['items'], function ($it) {
        return tienePermiso($it['perm']);
    }));
}
$menu = array_filter($menu, function ($bloqueDef) { return !empty($bloqueDef['items']); });
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= url(defined('TENANT_LOGO') ? TENANT_LOGO : 'assets/img/eros.jpg') ?>" alt="<?= h(defined('TENANT_NOMBRE') ? TENANT_NOMBRE : 'Multiservicios Eros') ?>">
        <div>
            <div class="brand-text"><?= h(defined('TENANT_NOMBRE') ? TENANT_NOMBRE : 'Multiservicios Eros') ?></div>
            <div class="brand-sub">LuarSoft ERP</div>
        </div>
    </div>

    <nav class="sidebar-scroll">
        <?php foreach ($menu as $bloque):
            $items_visibles = array_filter($bloque['items'], function($it) {
                if ($it['tipo'] === 'link') return true;
                foreach ($it['hijos'] as $h) { if (isset($h['url'])) return true; }
                return true;
            });
            if (empty($items_visibles)) continue;
        ?>
        <div class="sidebar-section-title"><?= $bloque['titulo'] ?></div>
        <div class="nav-group">
            <?php foreach ($bloque['items'] as $item): ?>
                <?php if ($item['tipo'] === 'link'):
                    $esActivo = _es_actual($ruta_actual, $item['url']);
                ?>
                    <a href="<?= url($item['url']) ?>" class="nav-link-item <?= $esActivo ? 'active' : '' ?>">
                        <span class="nav-ico"><i class="bi <?= $item['icon'] ?>"></i></span>
                        <span class="nav-label"><?= h($item['label']) ?></span>
                    </a>
                <?php else:
                    $submenuId = 'submenu_' . $item['key'];
                    $hijoActivo = null;
                    foreach ($item['hijos'] as $h) {
                        if (_es_actual($ruta_actual, $h['url'])) { $hijoActivo = $h['url']; break; }
                    }
                    $grupoActual = $hijoActivo !== null;
                ?>
                    <a href="#" class="nav-link-item <?= $grupoActual ? 'group-current open' : '' ?>" data-toggle-submenu="<?= $submenuId ?>">
                        <span class="nav-ico"><i class="bi <?= $item['icon'] ?>"></i></span>
                        <span class="nav-label"><?= h($item['label']) ?></span>
                        <span class="nav-caret"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <div class="nav-submenu <?= $grupoActual ? 'show' : '' ?>" id="<?= $submenuId ?>">
                        <?php foreach ($item['hijos'] as $h): $esActivoHijo = ($hijoActivo === $h['url']); ?>
                            <a href="<?= url($h['url']) ?>" class="nav-link-item <?= $esActivoHijo ? 'active' : '' ?>">
                                <span class="nav-ico"><i class="bi <?= $h['icon'] ?>"></i></span>
                                <span class="nav-label"><?= h($h['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <div style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.06);">
        <a href="<?= url('logout.php') ?>" class="nav-link-item logout-link">
            <span class="nav-ico"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Cerrar Sesión</span>
        </a>
    </div>
</aside>
