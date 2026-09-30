<?php
/**
 * modules/productos/etiqueta.php — NUEVO.
 * ------------------------------------------------------------------
 * Etiqueta imprimible con el código de barras de un producto (nombre,
 * precio y el código de barras dibujado con JsBarcode), pensada para
 * pegar en el estante o en el propio producto.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
if ($codigo === '') {
    die("<div style='padding:2rem;font-family:sans-serif;'>Código de producto no especificado.</div>");
}

$stmt = mysqli_prepare($conexion, "SELECT codigo, codigo_barras, descripcion, precio_venta FROM productos WHERE codigo = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $codigo);
mysqli_stmt_execute($stmt);
$producto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$producto) {
    die("<div style='padding:2rem;font-family:sans-serif;'>Producto no encontrado.</div>");
}
if (empty($producto['codigo_barras'])) {
    die("<div style='padding:2rem;font-family:sans-serif;'>Este producto todavía no tiene un código de barras asignado.</div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiqueta - <?= h($producto['descripcion']) ?></title>
<script src="<?= url('assets/vendor/jsbarcode/jsbarcode.min.js') ?>"></script>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8; margin:0; padding:24px; }
    .etiqueta {
        width: 260px; margin: 0 auto; background:#fff; border:1px dashed #999; border-radius:8px;
        padding:14px; text-align:center;
    }
    .etiqueta h3 { font-size:0.88rem; margin:0 0 4px; color:#14472A; }
    .etiqueta .precio { font-size:1.3rem; font-weight:800; color:#1FA35C; margin:4px 0 8px; }
    .etiqueta svg { max-width:100%; }
    .no-print button { margin-top:16px; }
    @media print {
        body { background:#fff; padding:0; }
        .etiqueta { border:none; }
        .no-print { display:none; }
    }
</style>
</head>
<body>
    <div class="etiqueta">
        <h3><?= h($producto['descripcion']) ?></h3>
        <div class="precio">S/ <?= number_format((float)$producto['precio_venta'], 2) ?></div>
        <svg id="barcodeSvg"></svg>
    </div>
    <div class="no-print text-center">
        <button onclick="window.print()" style="padding:8px 20px; border-radius:6px; border:none; background:#1FA35C; color:#fff; cursor:pointer;">Imprimir Etiqueta</button>
    </div>
    <script>
        try {
            JsBarcode("#barcodeSvg", "<?= h($producto['codigo_barras']) ?>", {
                format: "EAN13",
                width: 2,
                height: 60,
                fontSize: 14,
                margin: 6
            });
        } catch (e) {
            // El código guardado no tiene formato EAN-13 válido (ej. viene de
            // un lector con otro estándar): se dibuja igual con CODE128, que
            // acepta cualquier texto o número sin restricciones de formato.
            JsBarcode("#barcodeSvg", "<?= h($producto['codigo_barras']) ?>", {
                format: "CODE128",
                width: 2,
                height: 60,
                fontSize: 14,
                margin: 6
            });
        }
    </script>
</body>
</html>
