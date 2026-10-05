<?php
/**
 * index.php — Panel Principal (Dashboard)
 * ------------------------------------------------------------------
 * Reemplaza a "1.php" del sistema original. Conserva exactamente la
 * misma lógica de datos (Top 5 productos más vendidos y Top 5
 * mejores clientes con Chart.js) y añade tarjetas KPI de resumen.
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permisos.php';

$labels_productos = $data_productos = $labels_clientes = $data_clientes = [];
$error_conexion = '';
$alertas_stock_bajo = [];
$alertas_ordenes_atrasadas = [];
$kpi_total_clientes = $kpi_total_productos = $kpi_stock_bajo = $kpi_ordenes_atrasadas = 0;
$kpi_ventas_hoy = ['t' => 0, 'n' => 0];

// --- Vista Administrador: métricas completas, gráficos y alertas. ---
// --- Vista Cajero / Usuario: panel operativo simple (ver includes/layout_top.php más abajo). ---
if (esAdministrador()):

// --- 1. Top 5 productos más vendidos (misma consulta del sistema original) ---
$sql_productos = "
    SELECT p.descripcion AS nombre_producto, SUM(dv.cantidad) AS total_unidades_vendidas
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.producto_codigo = p.codigo
    GROUP BY p.descripcion
    ORDER BY total_unidades_vendidas DESC
    LIMIT 5
";
$res_productos = mysqli_query($conexion, $sql_productos);
if ($res_productos) {
    while ($fila = mysqli_fetch_assoc($res_productos)) {
        $labels_productos[] = $fila['nombre_producto'];
        $data_productos[] = (int)$fila['total_unidades_vendidas'];
    }
}

// --- 2. Top 5 mejores clientes (misma consulta del sistema original) ---
$sql_clientes = "
    SELECT nombre_cliente, SUM(total_venta) AS total_comprado
    FROM ventas
    GROUP BY nombre_cliente
    ORDER BY total_comprado DESC
    LIMIT 5
";
$res_clientes = mysqli_query($conexion, $sql_clientes);
if ($res_clientes) {
    while ($fila = mysqli_fetch_assoc($res_clientes)) {
        $nombre = $fila['nombre_cliente'];
        $labels_clientes[] = $nombre;
        $data_clientes[] = (float)$fila['total_comprado'];
    }
}

// --- 3. KPIs adicionales para las tarjetas de resumen (mejora nueva) ---
$kpi_total_clientes = (int)(mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM clientes"))['c'] ?? 0);
$kpi_total_productos = (int)(mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM productos"))['c'] ?? 0);
$kpi_stock_bajo = (int)(mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM productos WHERE stock < 10"))['c'] ?? 0);
$kpi_ventas_hoy = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COALESCE(SUM(total_venta),0) t, COUNT(*) n FROM ventas WHERE DATE(fecha) = CURDATE()"));

// --- 4. Alertas simples para el panel (mejora nueva): productos con stock bajo
//     y órdenes de reparación sin movimiento hace más de 15 días. Solo lectura,
//     no modifican ninguna otra consulta ni módulo existente. ---
$alertas_stock_bajo = [];
$resAlertaStock = mysqli_query($conexion, "SELECT codigo, descripcion, stock FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 6");
if ($resAlertaStock) {
    while ($f = mysqli_fetch_assoc($resAlertaStock)) { $alertas_stock_bajo[] = $f; }
}

$alertas_ordenes_atrasadas = [];
$kpi_ordenes_atrasadas = 0;
$resAlertaOrdenes = mysqli_query($conexion, "
    SELECT id_orden, cliente, modelo_impresora, estado, fecha_ingreso, DATEDIFF(CURDATE(), fecha_ingreso) AS dias
    FROM ordenes
    WHERE estado NOT IN ('Entregado', 'Reparado') AND fecha_ingreso <= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
    ORDER BY fecha_ingreso ASC
");
if ($resAlertaOrdenes) {
    while ($f = mysqli_fetch_assoc($resAlertaOrdenes)) {
        $kpi_ordenes_atrasadas++;
        if (count($alertas_ordenes_atrasadas) < 6) { $alertas_ordenes_atrasadas[] = $f; }
    }
}

endif; // esAdministrador()

$labels_productos_json = json_encode($labels_productos);
$data_productos_json = json_encode($data_productos);
$labels_clientes_json = json_encode($labels_clientes);
$data_clientes_json = json_encode($data_clientes);

$page_title = 'Panel Principal';
$page_subtitle = 'Resumen general del sistema';
$active_menu = 'dashboard';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="hero-banner">
    <i class="bi bi-house-heart"></i>
    <h1>Sistema de Ventas · <?= h(defined('TENANT_NOMBRE') ? TENANT_NOMBRE : 'Multiservicios Eros') ?></h1>
    <span class="hero-banner-sep">·</span>
    <p>Administra clientes, productos, técnicos, ventas y reportes desde un solo lugar.</p>
</div>

<?php if (!esAdministrador()): ?>

    <!-- ================================================================
         VISTA CAJERO / USUARIO LIMITADO
         Panel operativo simple: sin métricas confidenciales de ventas,
         ganancias ni mejores clientes. Solo accesos rápidos al trabajo
         diario, y únicamente a los módulos que este usuario sí tiene
         permitidos en su Matriz de Permisos.
    ================================================================= -->
    <div class="card-erp mb-4 shadow-sm dashboard-card-modern">
        <div class="card-erp-body text-center py-4">
            <i class="bi bi-person-check" style="font-size:2.4rem; color:var(--accent-dark);"></i>
            <h2 class="mt-2 mb-1" style="font-family:'Plus Jakarta Sans',sans-serif;">Hola, <?= h(usuarioActual()) ?> 👋</h2>
            <p class="text-muted mb-0">Este es tu panel operativo. Usa los accesos rápidos para tus tareas del día a día.</p>
        </div>
    </div>

    <div class="row g-3">
        <?php if (tienePermiso('pos')): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= url('modules/ventas/pos.php') ?>" class="card-erp text-decoration-none d-block h-100 shadow-sm dashboard-card-modern" style="padding:26px 16px; text-align:center; color:var(--n-900);">
                <i class="bi bi-cart3" style="font-size:2rem; color:var(--accent-dark);"></i>
                <div class="fw-bold mt-2">Ir al POS / Nueva Venta</div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (tienePermiso('clientes')): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= url('modules/clientes/nuevo.php') ?>" class="card-erp text-decoration-none d-block h-100 shadow-sm dashboard-card-modern" style="padding:26px 16px; text-align:center; color:var(--n-900);">
                <i class="bi bi-person-plus" style="font-size:2rem; color:var(--accent-dark);"></i>
                <div class="fw-bold mt-2">Registrar Nuevo Cliente</div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (tienePermiso('ordenes')): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= url('modules/ordenes/nuevo.php') ?>" class="card-erp text-decoration-none d-block h-100 shadow-sm dashboard-card-modern" style="padding:26px 16px; text-align:center; color:var(--n-900);">
                <i class="bi bi-tools" style="font-size:2rem; color:var(--accent-dark);"></i>
                <div class="fw-bold mt-2">Crear Orden de Reparación</div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="<?= url('modules/ordenes/listado.php') ?>" class="card-erp text-decoration-none d-block h-100 shadow-sm dashboard-card-modern" style="padding:26px 16px; text-align:center; color:var(--n-900);">
                <i class="bi bi-search" style="font-size:2rem; color:var(--accent-dark);"></i>
                <div class="fw-bold mt-2">Consultar Estado de Equipo</div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!tienePermiso('pos') && !tienePermiso('clientes') && !tienePermiso('ordenes')): ?>
        <div class="alert alert-info mt-3"><i class="bi bi-info-circle"></i> Tu usuario aún no tiene módulos operativos asignados. Solicita al Administrador que active tus permisos en <em>Sistema &gt; Usuarios</em>.</div>
    <?php endif; ?>

<?php elseif (!empty($error_conexion)): ?>
    <div class="alert alert-danger text-center"><?= h($error_conexion) ?></div>
<?php else: ?>

<?php if (!empty($alertas_stock_bajo) || !empty($alertas_ordenes_atrasadas)): ?>
    <div class="row g-3 mb-4">
        <?php if (!empty($alertas_stock_bajo)): ?>
        <div class="col-lg-6">
            <div class="card-erp shadow-sm dashboard-card-modern" style="border-left:4px solid #B8790C;">
                <div class="card-erp-header"><h3><i class="bi bi-exclamation-triangle text-warning"></i> Alerta: Stock Bajo (<?= $kpi_stock_bajo ?>)</h3></div>
                <div class="card-erp-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($alertas_stock_bajo as $p): ?>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span><?= h($p['descripcion']) ?> <small class="text-muted">(<?= h($p['codigo']) ?>)</small></span>
                                <strong class="<?= (int)$p['stock'] === 0 ? 'text-danger' : 'text-warning' ?>"><?= (int)$p['stock'] ?> uds.</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('modules/reportes/productos.php?nivel_stock=bajo') ?>" class="btn btn-sm btn-outline-secondary mt-2">Ver reporte completo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($alertas_ordenes_atrasadas)): ?>
        <div class="col-lg-6">
            <div class="card-erp shadow-sm dashboard-card-modern" style="border-left:4px solid #C0392B;">
                <div class="card-erp-header"><h3><i class="bi bi-clock-history text-danger"></i> Alerta: Órdenes sin Movimiento +15 días (<?= $kpi_ordenes_atrasadas ?>)</h3></div>
                <div class="card-erp-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($alertas_ordenes_atrasadas as $o): ?>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span>#<?= (int)$o['id_orden'] ?> — <?= h($o['cliente']) ?> <small class="text-muted">(<?= h($o['modelo_impresora']) ?>)</small></span>
                                <strong class="text-danger"><?= (int)$o['dias'] ?> días</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('modules/ordenes/listado.php') ?>" class="btn btn-sm btn-outline-secondary mt-2">Ver todas las órdenes</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-sm dashboard-card-modern">
                <div class="kpi-icon bg-brand"><i class="bi bi-person"></i></div>
                <div><div class="kpi-label">Clientes registrados</div><div class="kpi-value"><?= $kpi_total_clientes ?></div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-sm dashboard-card-modern">
                <div class="kpi-icon bg-info"><i class="bi bi-box-seam"></i></div>
                <div><div class="kpi-label">Productos activos</div><div class="kpi-value"><?= $kpi_total_productos ?></div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-sm dashboard-card-modern">
                <div class="kpi-icon bg-warning"><i class="bi bi-exclamation-triangle"></i></div>
                <div><div class="kpi-label">Stock bajo (&lt;10)</div><div class="kpi-value"><?= $kpi_stock_bajo ?></div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-sm dashboard-card-modern">
                <div class="kpi-icon bg-success"><i class="bi bi-cash-coin"></i></div>
                <div><div class="kpi-label">Ventas de hoy</div><div class="kpi-value"><?= moneda($kpi_ventas_hoy['t'] ?? 0) ?></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-erp shadow-sm dashboard-card-modern">
                <div class="card-erp-header"><h3><i class="bi bi-fire"></i> Top 5 Productos Más Vendidos</h3></div>
                <div class="card-erp-body" style="height:380px;">
                    <canvas id="productosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-erp shadow-sm dashboard-card-modern">
                <div class="card-erp-header"><h3><i class="bi bi-trophy"></i> Top 5 Mejores Clientes (S/)</h3></div>
                <div class="card-erp-body" style="height:380px;">
                    <canvas id="clientesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const labelsProductos = <?= $labels_productos_json ?>;
    const dataProductos = <?= $data_productos_json ?>;
    const labelsClientes = <?= $labels_clientes_json ?>;
    const dataClientes = <?= $data_clientes_json ?>;

    // Los gráficos se dibujan en <canvas>, fuera del alcance de las
    // variables CSS, así que sus colores de cuadrícula/texto se calculan
    // aquí según el tema activo y se actualizan si el usuario cambia de
    // Modo Claro/Oscuro con el botón del topbar, sin recargar la página.
    function esTemaOscuro() { return document.documentElement.getAttribute('data-bs-theme') === 'dark'; }
    function colorCuadricula() { return esTemaOscuro() ? 'rgba(255,255,255,0.08)' : '#eef1f6'; }
    function colorTexto() { return esTemaOscuro() ? '#C3D6CB' : '#52645A'; }

    let chartProductos = null;
    let chartClientes = null;

    const ctxProductos = document.getElementById('productosChart');
    if (ctxProductos && dataProductos.length > 0) {
        chartProductos = new Chart(ctxProductos.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labelsProductos,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: dataProductos,
                    backgroundColor: 'rgba(31, 163, 92, 0.75)',
                    borderColor: '#1FA35C',
                    borderRadius: 6,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: colorCuadricula() }, ticks: { color: colorTexto() } },
                    x: { grid: { display: false }, ticks: { color: colorTexto() } }
                },
                plugins: { legend: { display: false } }
            }
        });
    } else if (ctxProductos) {
        ctxProductos.parentNode.innerHTML = '<div class="alert alert-warning text-center mt-5"><i class="bi bi-exclamation-triangle"></i> No hay datos de productos disponibles para este gráfico.</div>';
    }

    const ctxClientes = document.getElementById('clientesChart');
    if (ctxClientes && dataClientes.length > 0) {
        chartClientes = new Chart(ctxClientes.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labelsClientes,
                datasets: [{
                    data: dataClientes,
                    backgroundColor: ['#0E3620', '#1FA35C', '#2C67C7', '#B8790C', '#6FCB93'],
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 576 ? 'bottom' : 'right',
                        labels: {
                            color: colorTexto(),
                            generateLabels: function (chart) {
                                const data = chart.data;
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0.0%';
                                    const nombreCorto = label.length > 24 ? label.slice(0, 22) + '…' : label;
                                    return {
                                        text: `${nombreCorto}: S/ ${value.toFixed(2)} (${pct})`,
                                        fillStyle: data.datasets[0].backgroundColor[i], index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: { callbacks: { label: (c) => c.label + ': S/ ' + c.parsed.toFixed(2) } }
                }
            }
        });
    } else if (ctxClientes) {
        ctxClientes.parentNode.innerHTML = '<div class="alert alert-warning text-center mt-5"><i class="bi bi-exclamation-triangle"></i> No hay datos de clientes disponibles para este gráfico.</div>';
    }

    document.addEventListener('erp:theme-changed', function () {
        if (chartProductos) {
            chartProductos.options.scales.y.grid.color = colorCuadricula();
            chartProductos.options.scales.y.ticks.color = colorTexto();
            chartProductos.options.scales.x.ticks.color = colorTexto();
            chartProductos.update();
        }
        if (chartClientes) {
            chartClientes.options.plugins.legend.labels.color = colorTexto();
            chartClientes.update();
        }
    });
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
