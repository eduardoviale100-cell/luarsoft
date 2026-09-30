<?php
/**
 * modules/reportes/imprimir_individual.php — NUEVO (Mejora 2).
 * ------------------------------------------------------------------
 * Ficha de impresión INDIVIDUAL para una sola fila de cualquiera de
 * los reportes del sistema:
 *   - tipo=mas_vendidos     (?codigo=<producto_codigo>)
 *   - tipo=mejores_clientes (?documento=<documento_cliente>)
 *   - tipo=clientes         (?id=<id_cliente>)
 *   - tipo=productos        (?codigo=<codigo>)
 *
 * El Reporte General de Ventas reutiliza directamente el comprobante
 * ya existente en modules/ventas/imprimir.php (boleta/factura por
 * id_venta), por lo que no necesita pasar por este archivo.
 *
 * No reemplaza ni modifica ningún reporte existente: es un archivo
 * nuevo y aditivo que las 4 vistas de reporte listadas arriba
 * enlazan desde un botón nuevo por fila.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$tipo = $_GET['tipo'] ?? '';

$titulo = 'Ficha de Reporte';
$icono = 'bi-file-earmark-bar-graph';
$filas = []; // pares [etiqueta, valor] a mostrar en la sección "Información General"
$seccionExtra = ''; // HTML opcional adicional (ej. tabla de compras)

switch ($tipo) {

    // ----------------------------------------------------------------
    case 'mas_vendidos':
        $codigo = limpiar($_GET['codigo'] ?? '');
        if ($codigo === '') { die("<div style='padding:2rem;font-family:sans-serif;'>Código de producto no válido.</div>"); }

        $stmt = mysqli_prepare($conexion, "
            SELECT p.codigo, p.descripcion, p.categoria, p.stock, p.precio_venta,
                   COALESCE(SUM(dv.cantidad), 0) AS total_unidades_vendidas
            FROM productos p
            LEFT JOIN detalle_venta dv ON dv.producto_codigo = p.codigo
            WHERE p.codigo = ?
            GROUP BY p.codigo, p.descripcion, p.categoria, p.stock, p.precio_venta
        ");
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $fila = mysqli_stmt_get_result($stmt)->fetch_assoc();
        if (!$fila) { die("<div style='padding:2rem;font-family:sans-serif;'>Producto no encontrado.</div>"); }

        $titulo = 'Ficha de Producto Más Vendido';
        $icono = 'bi-fire';
        $filas = [
            ['Código / SKU', $fila['codigo']],
            ['Producto', $fila['descripcion']],
            ['Categoría', $fila['categoria']],
            ['Stock Actual', $fila['stock']],
            ['Precio de Venta', moneda($fila['precio_venta'])],
            ['Total de Unidades Vendidas', number_format($fila['total_unidades_vendidas'], 0) . ' unidades'],
        ];
        break;

    // ----------------------------------------------------------------
    case 'mejores_clientes':
        $documento = limpiar($_GET['documento'] ?? '');
        if ($documento === '') { die("<div style='padding:2rem;font-family:sans-serif;'>Documento de cliente no válido.</div>"); }

        $stmt = mysqli_prepare($conexion, "
            SELECT nombre_cliente, documento_cliente, COUNT(id_venta) AS total_ventas, SUM(total_venta) AS total_gastado
            FROM ventas
            WHERE documento_cliente = ?
            GROUP BY nombre_cliente, documento_cliente
        ");
        mysqli_stmt_bind_param($stmt, "s", $documento);
        mysqli_stmt_execute($stmt);
        $fila = mysqli_stmt_get_result($stmt)->fetch_assoc();
        if (!$fila) { die("<div style='padding:2rem;font-family:sans-serif;'>Cliente no encontrado.</div>"); }

        $titulo = 'Ficha de Mejor Cliente';
        $icono = 'bi-trophy';
        $filas = [
            ['Nombre / Razón Social', $fila['nombre_cliente']],
            ['Documento (RUC/DNI)', $fila['documento_cliente']],
            ['N° de Ventas', (int)$fila['total_ventas']],
            ['Total Gastado', moneda($fila['total_gastado'])],
        ];

        $stmtDet = mysqli_prepare($conexion, "SELECT numero_documento, tipo_documento, fecha, total_venta FROM ventas WHERE documento_cliente = ? ORDER BY fecha DESC LIMIT 15");
        mysqli_stmt_bind_param($stmtDet, "s", $documento);
        mysqli_stmt_execute($stmtDet);
        $resDet = mysqli_stmt_get_result($stmtDet);
        $filasDet = [];
        while ($d = mysqli_fetch_assoc($resDet)) { $filasDet[] = $d; }
        if ($filasDet) {
            $seccionExtra .= '<div class="section-title">Últimas Compras</div><table class="tabla-detalle"><thead><tr><th>Documento</th><th>Tipo</th><th>Fecha</th><th style="text-align:right;">Total</th></tr></thead><tbody>';
            foreach ($filasDet as $d) {
                $seccionExtra .= '<tr><td>' . h($d['numero_documento']) . '</td><td>' . h($d['tipo_documento']) . '</td><td>' . h(date('d/m/Y', strtotime($d['fecha']))) . '</td><td style="text-align:right;">' . moneda($d['total_venta']) . '</td></tr>';
            }
            $seccionExtra .= '</tbody></table>';
        }
        break;

    // ----------------------------------------------------------------
    case 'clientes':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { die("<div style='padding:2rem;font-family:sans-serif;'>ID de cliente no válido.</div>"); }

        $stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $cliente = mysqli_stmt_get_result($stmt)->fetch_assoc();
        if (!$cliente) { die("<div style='padding:2rem;font-family:sans-serif;'>Cliente no encontrado.</div>"); }

        $stmtHist = mysqli_prepare($conexion, "SELECT COUNT(id_venta) AS total_compras, COALESCE(SUM(total_venta),0) AS total_gastado, MAX(fecha) AS ultima_compra FROM ventas WHERE documento_cliente = ?");
        mysqli_stmt_bind_param($stmtHist, "s", $cliente['numero_documento']);
        mysqli_stmt_execute($stmtHist);
        $hist = mysqli_stmt_get_result($stmtHist)->fetch_assoc();

        $titulo = 'Ficha de Reporte de Cliente';
        $icono = 'bi-people';
        $filas = [
            ['Nombre', $cliente['nombre']],
            ['Documento', $cliente['tipo_documento'] . ' ' . $cliente['numero_documento']],
            ['Teléfono', $cliente['telefono']],
            ['Ciudad / Región', trim($cliente['ciudad'] . ' / ' . $cliente['region'])],
            ['N° de Compras (histórico)', (int)$hist['total_compras']],
            ['Total Gastado (histórico)', moneda($hist['total_gastado'])],
            ['Última Compra', $hist['ultima_compra'] ? date('d/m/Y', strtotime($hist['ultima_compra'])) : 'Sin compras registradas'],
        ];
        break;

    // ----------------------------------------------------------------
    case 'productos':
        $codigo = limpiar($_GET['codigo'] ?? '');
        if ($codigo === '') { die("<div style='padding:2rem;font-family:sans-serif;'>Código de producto no válido.</div>"); }

        $stmt = mysqli_prepare($conexion, "SELECT * FROM productos WHERE codigo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $p = mysqli_stmt_get_result($stmt)->fetch_assoc();
        if (!$p) { die("<div style='padding:2rem;font-family:sans-serif;'>Producto no encontrado.</div>"); }

        $valorCosto = (float)$p['precio_compra'] * (int)$p['stock'];
        $valorVenta = (float)$p['precio_venta'] * (int)$p['stock'];

        $titulo = 'Ficha de Reporte de Producto';
        $icono = 'bi-box-seam';
        $filas = [
            ['Código', $p['codigo']],
            ['Descripción', $p['descripcion']],
            ['Categoría', $p['categoria']],
            ['Stock Actual', $p['stock']],
            ['Precio de Compra', moneda($p['precio_compra'])],
            ['Precio de Venta', moneda($p['precio_venta'])],
            ['Valor al Costo (stock)', moneda($valorCosto)],
            ['Valor de Venta (stock)', moneda($valorVenta)],
        ];
        break;

    // ----------------------------------------------------------------
    default:
        die("<div style='padding:2rem;font-family:sans-serif;'>Tipo de reporte no válido.</div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($titulo) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8; color:#333; }
    .container { max-width:800px; margin:40px auto; }
    .card { background:#fff; border-radius:12px; padding:30px 40px; box-shadow:0 8px 25px rgba(0,0,0,.1); border-top:5px solid #1FA35C; }
    .header { display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #e0e0e0; padding-bottom:15px; margin-bottom:25px; }
    .header img { width:90px; border-radius:8px; }
    .header h1 { font-size:1.5em; color:#14472A; margin:0; font-weight:700; }
    .section-title { font-size:1.05em; font-weight:700; color:#555; margin:20px 0 10px; border-bottom:1px solid #ddd; padding-bottom:5px; }
    .info p { margin:6px 0; font-size:1.02em; }
    .info p strong { width:230px; display:inline-block; color:#555; }
    .tabla-detalle { width:100%; border-collapse:collapse; margin-top:8px; font-size:0.92em; }
    .tabla-detalle th { text-align:left; border-bottom:2px solid #e1e5ec; padding:6px 4px; font-size:0.8em; text-transform:uppercase; color:#555; }
    .tabla-detalle td { padding:6px 4px; border-bottom:1px solid #f0f0f0; }
    .footer { text-align:center; font-size:.85em; color:#777; margin-top:30px; border-top:1px solid #ddd; padding-top:10px; }
    @media print { body{background:#fff;} .card{box-shadow:none;border-top:none;} }
    @media (max-width: 520px) {
        .container { margin: 16px auto; padding: 0 12px; }
        .card { padding: 22px 18px; }
        .header { flex-wrap: wrap; justify-content: center; text-align: center; gap: 10px; }
        .header h1 { width: 100%; order: -1; font-size: 1.25em; }
        .info p strong { width: 100%; display: block; }
        .tabla-detalle { font-size: 0.82em; }
    }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros">
            <h1><i class="bi <?= h($icono) ?>"></i> <?= h($titulo) ?></h1>
        </div>

        <div class="section-title">Información General</div>
        <div class="info">
            <?php foreach ($filas as [$etiqueta, $valor]): ?>
                <p><strong><?= h($etiqueta) ?>:</strong> <?= h($valor) ?></p>
            <?php endforeach; ?>
        </div>

        <?= $seccionExtra ?>

        <div class="footer">Reporte generado el <?= h(date('d/m/Y H:i')) ?> · LuarSoft - Eros Tecnología</div>
    </div>
</div>
</body>
</html>
