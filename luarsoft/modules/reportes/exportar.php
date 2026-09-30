<?php
/**
 * modules/reportes/exportar.php — NUEVO.
 * ------------------------------------------------------------------
 * Exporta a CSV (se abre directamente en Excel) cualquiera de los 5
 * reportes del sistema, respetando los mismos filtros que la vista
 * en pantalla (se le reenvía el mismo query string).
 *   - tipo=mas_vendidos
 *   - tipo=mejores_clientes
 *   - tipo=clientes        (admite buscar, fecha_desde, fecha_hasta)
 *   - tipo=productos       (admite categoria, nivel_stock)
 *   - tipo=ventas          (admite tipo [BOLETA/FACTURA], fecha_desde, fecha_hasta)
 *
 * No reemplaza ni modifica ningún reporte existente: es un archivo
 * nuevo y aditivo enlazado desde un botón "Exportar a Excel" en cada
 * una de las 5 vistas de reporte.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$tipo = $_GET['tipo'] ?? '';

/** Envía las cabeceras HTTP para forzar la descarga como CSV/Excel. */
function iniciarDescargaCsv(string $nombreArchivo): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    // BOM UTF-8 para que Excel muestre correctamente tildes y "ñ".
    echo "\xEF\xBB\xBF";
}

$fechaArchivo = date('Y-m-d_His');
$salida = fopen('php://output', 'w');

switch ($tipo) {

    // ----------------------------------------------------------------
    case 'mas_vendidos':
        iniciarDescargaCsv("mas_vendidos_$fechaArchivo.csv");
        $sql = "
            SELECT p.codigo AS codigo_sku, p.descripcion AS nombre_producto, SUM(dv.cantidad) AS total_unidades_vendidas
            FROM detalle_venta dv
            INNER JOIN productos p ON dv.producto_codigo = p.codigo
            GROUP BY p.codigo, p.descripcion
            ORDER BY total_unidades_vendidas DESC
            LIMIT 10
        ";
        $resultado = mysqli_query($conexion, $sql);
        fputcsv($salida, ['Código / SKU', 'Producto', 'Total Unidades Vendidas']);
        while ($f = mysqli_fetch_assoc($resultado)) {
            fputcsv($salida, [$f['codigo_sku'], $f['nombre_producto'], $f['total_unidades_vendidas']]);
        }
        break;

    // ----------------------------------------------------------------
    case 'mejores_clientes':
        iniciarDescargaCsv("mejores_clientes_$fechaArchivo.csv");
        $sql = "
            SELECT nombre_cliente, documento_cliente, COUNT(id_venta) AS total_ventas, SUM(total_venta) AS total_gastado
            FROM ventas
            WHERE nombre_cliente IS NOT NULL AND nombre_cliente != ''
            GROUP BY nombre_cliente, documento_cliente
            ORDER BY total_gastado DESC
            LIMIT 10
        ";
        $resultado = mysqli_query($conexion, $sql);
        fputcsv($salida, ['Nombre / Razón Social', 'Documento (RUC/DNI)', 'N° de Ventas', 'Total Gastado (S/)']);
        while ($f = mysqli_fetch_assoc($resultado)) {
            fputcsv($salida, [$f['nombre_cliente'], $f['documento_cliente'], $f['total_ventas'], number_format($f['total_gastado'], 2, '.', '')]);
        }
        break;

    // ----------------------------------------------------------------
    case 'clientes':
        iniciarDescargaCsv("reporte_clientes_$fechaArchivo.csv");

        $fecha_desde = limpiar($_GET['fecha_desde'] ?? '');
        $fecha_hasta = limpiar($_GET['fecha_hasta'] ?? '');
        $buscar = limpiar($_GET['buscar'] ?? '');

        $condicionesJoin = [];
        $params = [];
        $types = '';
        if ($fecha_desde !== '') { $condicionesJoin[] = 'v.fecha >= ?'; $params[] = $fecha_desde . ' 00:00:00'; $types .= 's'; }
        if ($fecha_hasta !== '') { $condicionesJoin[] = 'v.fecha <= ?'; $params[] = $fecha_hasta . ' 23:59:59'; $types .= 's'; }
        $onJoin = 'v.documento_cliente = c.numero_documento';
        if ($condicionesJoin) { $onJoin .= ' AND ' . implode(' AND ', $condicionesJoin); }

        $whereBuscar = '';
        if ($buscar !== '') {
            $whereBuscar = 'WHERE c.nombre LIKE ? OR c.numero_documento LIKE ? OR c.ciudad LIKE ?';
            $like = "%$buscar%";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= 'sss';
        }

        $sql = "
            SELECT c.nombre, c.tipo_documento, c.numero_documento, c.telefono, c.ciudad, c.region,
                   COUNT(v.id_venta) AS total_compras, COALESCE(SUM(v.total_venta), 0) AS total_gastado, MAX(v.fecha) AS ultima_compra
            FROM clientes c
            LEFT JOIN ventas v ON $onJoin
            $whereBuscar
            GROUP BY c.id_cliente
            ORDER BY total_gastado DESC, c.nombre ASC
        ";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        fputcsv($salida, ['Cliente', 'Documento', 'Teléfono', 'Ciudad', 'Región', 'N° Compras', 'Total Gastado (S/)', 'Última Compra']);
        while ($f = mysqli_fetch_assoc($resultado)) {
            fputcsv($salida, [
                $f['nombre'], $f['tipo_documento'] . ' ' . $f['numero_documento'], $f['telefono'], $f['ciudad'], $f['region'],
                $f['total_compras'], number_format($f['total_gastado'], 2, '.', ''),
                $f['ultima_compra'] ? date('d/m/Y', strtotime($f['ultima_compra'])) : '',
            ]);
        }
        break;

    // ----------------------------------------------------------------
    case 'productos':
        iniciarDescargaCsv("reporte_productos_$fechaArchivo.csv");

        $categoria = limpiar($_GET['categoria'] ?? '');
        $nivelStock = limpiar($_GET['nivel_stock'] ?? '');
        $condiciones = [];
        $params = [];
        $types = '';
        if ($categoria !== '') { $condiciones[] = 'categoria = ?'; $params[] = $categoria; $types .= 's'; }
        if ($nivelStock === 'bajo') { $condiciones[] = 'stock < 10'; }
        elseif ($nivelStock === 'agotado') { $condiciones[] = 'stock = 0'; }
        elseif ($nivelStock === 'normal') { $condiciones[] = 'stock >= 10'; }
        $where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "SELECT * FROM productos $where ORDER BY categoria ASC, descripcion ASC";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        fputcsv($salida, ['Código', 'Descripción', 'Categoría', 'Stock', 'P. Compra (S/)', 'P. Venta (S/)', 'Valor Costo (S/)', 'Valor Venta (S/)']);
        while ($f = mysqli_fetch_assoc($resultado)) {
            $valorCosto = (float)$f['precio_compra'] * (int)$f['stock'];
            $valorVenta = (float)$f['precio_venta'] * (int)$f['stock'];
            fputcsv($salida, [
                $f['codigo'], $f['descripcion'], $f['categoria'], $f['stock'],
                number_format($f['precio_compra'], 2, '.', ''), number_format($f['precio_venta'], 2, '.', ''),
                number_format($valorCosto, 2, '.', ''), number_format($valorVenta, 2, '.', ''),
            ]);
        }
        break;

    // ----------------------------------------------------------------
    case 'ventas':
        iniciarDescargaCsv("reporte_ventas_$fechaArchivo.csv");

        $tipoDoc = limpiar($_GET['tipo'] ?? '');
        // Nota: en este reporte, el parámetro GET "tipo" identifica BOLETA/FACTURA
        // (viene de modules/reportes/ventas.php), no el tipo de exportación.
        $fecha_desde = limpiar($_GET['fecha_desde'] ?? '');
        $fecha_hasta = limpiar($_GET['fecha_hasta'] ?? '');

        $condiciones = [];
        $params = [];
        $types = '';
        if ($tipoDoc === 'BOLETA' || $tipoDoc === 'FACTURA') { $condiciones[] = 'tipo_documento = ?'; $params[] = $tipoDoc; $types .= 's'; }
        if ($fecha_desde !== '') { $condiciones[] = 'fecha >= ?'; $params[] = $fecha_desde . ' 00:00:00'; $types .= 's'; }
        if ($fecha_hasta !== '') { $condiciones[] = 'fecha <= ?'; $params[] = $fecha_hasta . ' 23:59:59'; $types .= 's'; }
        $where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "SELECT * FROM ventas $where ORDER BY fecha DESC";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        fputcsv($salida, ['Tipo', '# Documento', 'Fecha', 'Cliente', 'Documento Cliente', 'Método de Pago', 'Total (S/)']);
        while ($f = mysqli_fetch_assoc($resultado)) {
            fputcsv($salida, [
                $f['tipo_documento'], $f['numero_documento'], date('d/m/Y H:i', strtotime($f['fecha'])),
                $f['nombre_cliente'], $f['documento_cliente'], $f['metodo_pago'] ?: '', number_format($f['total_venta'], 2, '.', ''),
            ]);
        }
        break;

    // ----------------------------------------------------------------
    default:
        die('Tipo de exportación no válido.');
}

fclose($salida);
exit;
