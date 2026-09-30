<?php
/**
 * modules/ventas/imprimir.php
 * ------------------------------------------------------------------
 * Reemplaza a "imprimir_boleta.php". Esta plantilla ya era genérica
 * en el sistema original (detecta BOLETA o FACTURA por el campo
 * tipo_documento), pero "lista_facturas.php" enlazaba a un archivo
 * inexistente "imprimir_factura.php" (enlace roto). Aquí un único
 * archivo sirve ambos casos correctamente.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/** Ver la explicación completa de este mecanismo en ticket.php. */
function mostrarErrorImprimir(string $detalle): void
{
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>No se pudo generar el documento</title></head>'
       . '<body style="font-family:Arial,sans-serif; padding:20px; color:#1C2430;">'
       . '<h3 style="color:#E0102B;">No se pudo generar el documento</h3>'
       . '<p>Ocurrió un error en el servidor. Detalle técnico (compártelo para poder corregirlo):</p>'
       . '<pre style="background:#F5F3EE; padding:12px; border-radius:6px; white-space:pre-wrap; font-size:13px;">'
       . htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8')
       . '</pre></body></html>';
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        mostrarErrorImprimir($error['message'] . ' en ' . $error['file'] . ':' . $error['line']);
    }
});

try {
    ob_start();

$id_venta = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "
    SELECT v.tipo_documento, v.numero_documento, v.fecha, v.nombre_cliente, v.documento_cliente,
           v.direccion_cliente, v.subtotal, v.igv, v.total_venta, v.metodo_pago, v.referencia_pago,
           dv.descripcion, dv.cantidad, dv.precio_unitario, dv.importe
    FROM ventas v
    JOIN detalle_venta dv ON v.id_venta = dv.id_venta
    WHERE v.id_venta = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_venta);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$detalles = [];
$venta = null;
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        if ($venta === null) {
            $venta = [
                'tipo_documento' => $row['tipo_documento'],
                'numero_documento' => $row['numero_documento'],
                'fecha' => $row['fecha'],
                'nombre_cliente' => $row['nombre_cliente'],
                'documento_cliente' => $row['documento_cliente'],
                'direccion_cliente' => $row['direccion_cliente'],
                'subtotal' => $row['subtotal'],
                'igv' => $row['igv'],
                'total_venta' => $row['total_venta'],
                'metodo_pago' => $row['metodo_pago'],
                'referencia_pago' => $row['referencia_pago'],
            ];
        }
        $detalles[] = [
            'descripcion' => $row['descripcion'],
            'cantidad' => $row['cantidad'],
            'precio_unitario' => $row['precio_unitario'],
            'importe' => $row['importe'],
        ];
    }
} else {
    die("Venta no encontrada.");
}

if ($venta['tipo_documento'] === 'FACTURA') {
    $titulo_documento = "FACTURA ELECTRÓNICA";
    $etiqueta_cliente_doc = "RUC";
} else {
    $titulo_documento = "BOLETA DE VENTA ELECTRÓNICA";
    $etiqueta_cliente_doc = "Doc. Ident";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= h($titulo_documento) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; width: 80mm; margin: 0 auto; padding: 10px; }
        .header, .footer { text-align: center; }
        .document-title { font-size: 12pt; font-weight: bold; margin: 10px 0; text-align: center; }
        .data-section p { margin: 2px 0; }
        .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .item-table th, .item-table td { border-bottom: 1px dashed #ccc; padding: 3px 0; text-align: left; }
        .item-table th { border-top: 1px dashed #ccc; }
        .totals-table { width: 100%; margin-top: 10px; }
        .totals-table td { padding: 2px 0; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <img src="<?= url('assets/img/eros.jpg') ?>" alt="EROS TECNOLOGÍA" style="width:50px; border-radius:6px;">
        <p style="margin:0; font-size:10pt;">EROS TECNOLOGÍA</p>
        <p style="margin:0; font-size:9pt;">RUC: 10419418043</p>
        <p style="margin:0; font-size:9pt;">Jirón progreso con Av. La Mar, Imperial</p>
        <p style="margin:0 0 10px 0; font-size:9pt;">Telf: 949092352</p>
    </div>

    <hr style="border:1px dashed black; margin:5px 0;">

    <div class="document-title">
        <?= h($titulo_documento) ?>
        <p style="margin:0;"><?= h($venta['numero_documento']) ?></p>
    </div>

    <hr style="border:1px dashed black; margin:5px 0;">

    <div class="data-section" style="font-size:9pt;">
        <p><span style="font-weight:bold;">Cliente:</span> <?= h($venta['nombre_cliente']) ?></p>
        <p><span style="font-weight:bold;"><?= h($etiqueta_cliente_doc) ?>:</span> <?= h($venta['documento_cliente']) ?></p>
        <p><span style="font-weight:bold;">Dirección:</span> <?= h($venta['direccion_cliente']) ?></p>
        <p><span style="font-weight:bold;">Fecha:</span> <?= h(date('d/m/Y H:i:s', strtotime($venta['fecha']))) ?></p>
        <?php if (!empty($venta['metodo_pago'])): ?>
        <p><span style="font-weight:bold;">Método de Pago:</span> <?= h($venta['metodo_pago']) ?></p>
        <?php endif; ?>
        <?php if (!empty($venta['referencia_pago'])): ?>
        <p><span style="font-weight:bold;">N.° de Operación:</span> <?= h($venta['referencia_pago']) ?></p>
        <?php endif; ?>
    </div>

    <hr style="border:1px dashed black; margin:5px 0;">

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:40%;">Descripción</th>
                <th style="width:10%; text-align:center;">Cant</th>
                <th style="width:25%; text-align:right;">P.U</th>
                <th style="width:25%; text-align:right;">Imp.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
            <tr>
                <td><?= h($detalle['descripcion']) ?></td>
                <td style="text-align:center;"><?= number_format($detalle['cantidad'], 2) ?></td>
                <td style="text-align:right;"><?= number_format($detalle['precio_unitario'], 2) ?></td>
                <td style="text-align:right;"><?= number_format($detalle['importe'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr style="border:1px dashed black; margin:0;">

    <table class="totals-table" style="font-size:10pt;">
        <tr><td style="width:70%; text-align:left;">Gravado: S/</td><td style="width:30%;"><?= number_format($venta['subtotal'], 2) ?></td></tr>
        <tr><td style="text-align:left;">IGV (18%): S/</td><td><?= number_format($venta['igv'], 2) ?></td></tr>
        <tr><td style="text-align:left;"><span style="font-weight:bold;">TOTAL: S/</span></td><td><span style="font-weight:bold;"><?= number_format($venta['total_venta'], 2) ?></span></td></tr>
    </table>

    <div class="footer" style="margin-top:15px; font-size:9pt;">
        <p>Gracias por su compra</p>
        <p>Representación impresa de la <?= h($venta['tipo_documento']) ?></p>
        <p>www.luarsoft.pe</p>
    </div>

</body>
</html>
<?php
    ob_end_flush();
} catch (\Throwable $e) {
    if (ob_get_level() > 0) { ob_end_clean(); }
    mostrarErrorImprimir($e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
}
