<?php
/**
 * modules/ventas/ticket.php — Reemplaza a "ticket.php".
 * ------------------------------------------------------------------
 * Conserva el mismo formato de ticket térmico (80mm) del sistema
 * original. Mejora: el original mostraba <img src="qr.png"> pero ese
 * archivo nunca existía en el paquete entregado (enlace roto); aquí
 * se genera un código QR real con los datos del comprobante usando
 * la librería phpqrcode ya incluida en el proyecto original.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

// Evita que el hosting, un proxy o el navegador del celular guarden en
// caché esta página. Sin esto, algunos servidores llegan a mostrar una
// versión vieja/vacía justo después de una venta recién registrada.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/**
 * Diagnóstico de pantalla en blanco: en muchos hostings de producción,
 * PHP oculta los errores por seguridad (display_errors=Off), así que un
 * fallo aquí se ve como una página completamente en blanco, sin ninguna
 * pista de qué pasó. Esto captura CUALQUIER error fatal y lo muestra de
 * forma legible en vez de dejar la pantalla vacía — así, si algo falla,
 * se puede ver y corregir el motivo exacto en lugar de adivinar.
 */
function mostrarErrorTicket(string $detalle): void
{
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>No se pudo generar el ticket</title></head>'
       . '<body style="font-family:Arial,sans-serif; padding:20px; color:#1C2430;">'
       . '<h3 style="color:#E0102B;">No se pudo generar el ticket</h3>'
       . '<p>Ocurrió un error en el servidor. Detalle técnico (compártelo para poder corregirlo):</p>'
       . '<pre style="background:#F5F3EE; padding:12px; border-radius:6px; white-space:pre-wrap; font-size:13px;">'
       . htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8')
       . '</pre></body></html>';
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            // Limpia cualquier salida parcial (como el HTML a medio imprimir)
            // para que no queden restos mezclados con el mensaje de error.
            while (ob_get_level() > 0) { ob_end_clean(); }
        }
        mostrarErrorTicket($error['message'] . ' en ' . $error['file'] . ':' . $error['line']);
    }
});

try {
    ob_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de Venta no proporcionado o inválido.");
}
$id_venta = (int)$_GET['id'];

$stmt = mysqli_prepare($conexion, "SELECT tipo_documento, serie_documento, numero_documento, fecha, nombre_cliente, documento_cliente, direccion_cliente, subtotal, igv, total_venta, metodo_pago, referencia_pago FROM ventas WHERE id_venta = ?");
mysqli_stmt_bind_param($stmt, "i", $id_venta);
mysqli_stmt_execute($stmt);
$venta = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$venta) {
    die("Venta no encontrada.");
}

$stmt2 = mysqli_prepare($conexion, "SELECT cantidad, descripcion, precio_unitario, importe FROM detalle_venta WHERE id_venta = ?");
mysqli_stmt_bind_param($stmt2, "i", $id_venta);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$productos = [];
while ($row = mysqli_fetch_assoc($res2)) {
    $productos[] = $row;
}

$nombre = $venta['nombre_cliente'];
$documento = $venta['documento_cliente'];
$direccion = $venta['direccion_cliente'];
$fecha = date('d/m/Y H:i:s', strtotime($venta['fecha']));
$tipo_documento_display = strtoupper($venta['tipo_documento']);
$numero_documento_display = $venta['numero_documento'];
$subtotal = $venta['subtotal'];
$igv = $venta['igv'];
$total = $venta['total_venta'];
$metodo_pago = $venta['metodo_pago'] ?? '';
$referencia_pago = $venta['referencia_pago'] ?? '';

if ($tipo_documento_display === 'FACTURA') {
    $etiqueta_nombre = 'Razón Social:';
    $etiqueta_documento = 'RUC:';
} else {
    $etiqueta_nombre = 'Cliente:';
    $etiqueta_documento = 'Doc. Ident:';
}

// --- Generar QR real con los datos del comprobante (mejora: reemplaza imagen rota) ---
// Nota: la generación de QR requiere la extensión GD de PHP. Si el servidor
// no la tiene habilitada, el ticket se muestra igual, solo sin el código QR
// (nunca debe romperse el comprobante completo por esto).
$qr_base64 = '';
$qrLib = __DIR__ . '/../../assets/vendor/phpqrcode.php';
if (file_exists($qrLib) && extension_loaded('gd') && function_exists('imagecreate')) {
    try {
        require_once $qrLib;
        $qrTexto = "RUC:10419418043|{$tipo_documento_display}|{$numero_documento_display}|TOTAL:" . number_format((float)$total, 2);
        ob_start();
        QRcode::png($qrTexto, false, QR_ECLEVEL_L, 3, 2);
        $qrBinario = ob_get_clean();
        $qr_base64 = 'data:image/png;base64,' . base64_encode($qrBinario);
    } catch (\Throwable $e) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        $qr_base64 = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($tipo_documento_display) ?> #<?= $id_venta ?></title>
<style>
@media print { @page { size: 240px auto; margin: 0; } body { margin:0; padding:2px; font-size:10px; } }
body { font-family: Arial, sans-serif; width: 240px; margin: 0 auto; font-size: 11px; padding: 2px; }
.header { text-align:center; margin-bottom:5px; }
.header strong { font-size:12px; }
.linea { border-top:1px dashed #000; margin:4px 0; }
.cliente { margin-bottom:5px; }
table { width:100%; font-size:11px; border-collapse:collapse; }
th { text-align:left; border-bottom:1px dashed #000; }
td { padding:2px 0; }
.totales { margin-top:6px; border-top:1px dashed #000; padding-top:4px; font-size:11px; }
.totales strong { font-size:12px; }
.footer { text-align:center; margin-top:6px; font-size:10px; }
.qr { text-align:center; margin:6px 0; }
.qr img { width:90px; }
</style>
</head>
<body>

<div class="header">
    <img src="<?= url('assets/img/eros.jpg') ?>" width="90"><br>
    <strong>EROS TECNOLOGÍA</strong><br>
    RUC: 10419418043<br>
    Jirón progreso con Av. La Mar, Imperial<br>
    Telf: 949092352
</div>

<div class="linea"></div>
<div class="header">
    <strong><?= h($tipo_documento_display) ?> DE VENTA ELECTRÓNICA</strong><br>
    <?= h($numero_documento_display) ?>
</div>
<div class="linea"></div>

<div class="cliente">
    <strong><?= h($etiqueta_nombre) ?></strong> <?= h($nombre) ?><br>
    <strong><?= h($etiqueta_documento) ?></strong> <?= h($documento) ?><br>
    <strong>Dirección:</strong> <?= h($direccion) ?><br>
    <strong>Fecha:</strong> <?= h($fecha) ?>
</div>

<table>
<thead><tr><th>Cant</th><th>Descripción</th><th style="text-align:right;">P.U</th><th style="text-align:right;">Imp.</th></tr></thead>
<tbody>
<?php foreach ($productos as $p): ?>
<tr>
    <td><?= number_format($p['cantidad'], 2) ?></td>
    <td><?= h($p['descripcion']) ?></td>
    <td style="text-align:right;"><?= number_format($p['precio_unitario'], 2) ?></td>
    <td style="text-align:right;"><?= number_format($p['importe'], 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="totales">
    Gravado: S/ <?= number_format($subtotal, 2) ?><br>
    IGV (18%): S/ <?= number_format($igv, 2) ?><br>
    <strong>TOTAL: S/ <?= number_format($total, 2) ?></strong>
</div>

<?php if (stripos($metodo_pago, 'Yape') !== false || stripos($metodo_pago, 'Plin') !== false): ?>
<div class="linea"></div>
<div style="text-align:center;">
    Pagado con <?= h($metodo_pago) ?><?php if ($referencia_pago): ?><br>Operación N.° <?= h($referencia_pago) ?><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($qr_base64): ?>
<div class="qr"><img src="<?= $qr_base64 ?>" alt="Código QR"></div>
<?php endif; ?>

<div class="footer">
    Gracias por su compra<br>
    Representación impresa de la <?= h($tipo_documento_display) ?><br>
    www.luarsoft.pe
</div>

</body>
</html>
<?php
    ob_end_flush();
} catch (\Throwable $e) {
    if (ob_get_level() > 0) { ob_end_clean(); }
    mostrarErrorTicket($e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
}
