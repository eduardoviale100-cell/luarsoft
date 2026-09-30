<?php
/**
 * modules/ordenes/imprimir.php — Reemplaza a "imprimir.php" (órdenes).
 * ------------------------------------------------------------------
 * Ticket / Constancia FINAL de la orden de reparación: incluye el
 * detalle de repuestos utilizados (descontados del inventario), el
 * total de repuestos, la garantía del servicio y una línea de firma
 * de conformidad de entrega.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/_estados.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$fila = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$fila) {
    die("<div style='padding:2rem;font-family:sans-serif;'>Orden no encontrada.</div>");
}

$stmtRep = mysqli_prepare($conexion, "SELECT * FROM orden_repuestos WHERE id_orden = ?");
mysqli_stmt_bind_param($stmtRep, "i", $id);
mysqli_stmt_execute($stmtRep);
$resRep = mysqli_stmt_get_result($stmtRep);
$repuestos = [];
$totalRepuestos = 0;
while ($r = mysqli_fetch_assoc($resRep)) {
    $repuestos[] = $r;
    $totalRepuestos += (float)$r['importe'];
}

$garantiaTexto = ((int)($fila['garantia_dias'] ?? 0) > 0)
    ? (int)$fila['garantia_dias'] . ' días calendario desde la fecha de entrega'
    : 'Sin garantía registrada para este servicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket de Entrega - Orden #<?= h($fila['id_orden']) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8; color:#222; margin:0; }
    .container { max-width:760px; margin:0 auto; padding: 26px; }
    .card { background:#fff; border-radius:12px; padding:30px 36px; box-shadow:0 8px 25px rgba(0,0,0,.08); border-top:5px solid #158A5B; }
    .header { display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #e0e0e0; padding-bottom:14px; margin-bottom:20px; }
    .header img { width:70px; border-radius:8px; }
    .header .titulo-doc { text-align:right; }
    .header .titulo-doc h1 { font-size:1.25em; color:#14472A; margin:0; font-weight:700; }
    .header .titulo-doc .num { font-size:1.5em; font-weight:800; color:#158A5B; }
    .section-title { font-size:0.95em; font-weight:700; color:#525D73; margin:18px 0 8px; border-bottom:1px solid #e1e5ec; padding-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:4px 24px; }
    .grid2 p { margin:4px 0; font-size:0.95em; }
    .grid2 p strong { color:#525D73; display:block; font-size:0.78em; text-transform:uppercase; letter-spacing:.03em; }
    table.repuestos { width:100%; border-collapse:collapse; margin-top:8px; font-size:0.9em; }
    table.repuestos th { text-align:left; border-bottom:2px solid #e1e5ec; padding:6px 4px; font-size:0.78em; text-transform:uppercase; color:#525D73; }
    table.repuestos td { padding:6px 4px; border-bottom:1px solid #f0f0f0; }
    .total-row td { font-weight:700; border-top:2px solid #222; }
    .garantia-box { background:#E4F6EE; border-radius:8px; padding:12px 14px; font-size:0.92em; margin-top:8px; color:#158A5B; font-weight:600; }
    .estado-box { display:inline-block; background:#F5F7FA; border-radius:30px; padding:5px 14px; font-size:0.82em; font-weight:700; color:#525D73; }
    .firma-area { display:flex; justify-content:space-between; margin-top:50px; }
    .firma-box { width:45%; text-align:center; }
    .firma-linea { border-top:1.5px solid #222; margin-bottom:6px; padding-top:6px; }
    .footer { text-align:center; font-size:.8em; color:#717D93; margin-top:24px; border-top:1px solid #e1e5ec; padding-top:10px; }
    @media print { body{background:#fff;} .card{box-shadow:none;border-top:none;} }
    @media (max-width: 520px) {
        .container { padding: 14px; }
        .card { padding: 22px 18px; }
        .header { flex-wrap: wrap; justify-content: center; text-align: center; gap: 10px; }
        .header .titulo-doc { text-align: center; width: 100%; }
        .grid2 { grid-template-columns: 1fr; gap: 2px 0; }
        table.repuestos { font-size: 0.82em; }
        .firma-area { flex-direction: column; gap: 30px; }
        .firma-box { width: 100%; }
    }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros">
            <div class="titulo-doc">
                <h1>TICKET DE ENTREGA DE EQUIPO</h1>
                <div class="num">ORDEN #<?= h(str_pad($fila['id_orden'], 5, '0', STR_PAD_LEFT)) ?></div>
            </div>
        </div>

        <div class="grid2">
            <p><strong>Cliente</strong> <?= h($fila['cliente']) ?></p>
            <p><strong>Estado</strong> <span class="estado-box"><?= h($fila['estado']) ?></span></p>
            <p><strong>Equipo</strong> <?= h(trim(($fila['marca'] ?? '') . ' ' . $fila['modelo_impresora'])) ?></p>
            <p><strong>Fecha de Entrega</strong> <?= h($fila['fecha_entrega'] ? date('d/m/Y', strtotime($fila['fecha_entrega'])) : 'Pendiente') ?></p>
        </div>

        <div class="section-title">Falla Reportada y Trabajo Realizado</div>
        <p style="font-size:0.92em;"><?= nl2br(h($fila['problema'])) ?></p>

        <?php if (!empty($repuestos)): ?>
        <div class="section-title">Repuestos Utilizados</div>
        <table class="repuestos">
            <thead><tr><th>Descripción</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">P. Unit.</th><th style="text-align:right;">Importe</th></tr></thead>
            <tbody>
                <?php foreach ($repuestos as $r): ?>
                <tr>
                    <td><?= h($r['descripcion']) ?></td>
                    <td style="text-align:center;"><?= number_format($r['cantidad'], 2) ?></td>
                    <td style="text-align:right;"><?= number_format($r['precio_unitario'], 2) ?></td>
                    <td style="text-align:right;"><?= number_format($r['importe'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row"><td colspan="3" style="text-align:right;">TOTAL REPUESTOS S/</td><td style="text-align:right;"><?= number_format($totalRepuestos, 2) ?></td></tr>
            </tbody>
        </table>
        <?php else: ?>
        <div class="section-title">Repuestos Utilizados</div>
        <p style="font-size:0.9em; color:#717D93;">No se registraron repuestos del inventario en esta orden (solo mano de obra / diagnóstico).</p>
        <?php endif; ?>

        <div class="section-title">Garantía del Servicio</div>
        <div class="garantia-box"><i>Garantía:</i> <?= h($garantiaTexto) ?></div>

        <div class="firma-area">
            <div class="firma-box">
                <div class="firma-linea">Firma de Conformidad del Cliente</div>
                <div>Recibí el equipo conforme</div>
            </div>
            <div class="firma-box">
                <div class="firma-linea">Entregado por (Eros Tecnología)</div>
                <div>Fecha: <?= h(date('d/m/Y')) ?></div>
            </div>
        </div>

        <div class="footer">Eros Tecnología · Jirón Progreso con Av. La Mar, Imperial · Cel. 949 092 352</div>
    </div>
</div>
</body>
</html>
