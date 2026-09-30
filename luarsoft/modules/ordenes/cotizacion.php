<?php
/**
 * modules/ordenes/cotizacion.php — NUEVO.
 * ------------------------------------------------------------------
 * Documento de Cotización / Presupuesto de la reparación, basado en
 * el campo `monto_estimado` capturado en la orden. Pensado para
 * entregar o enviar al cliente antes de aprobar el trabajo (estado
 * "Esperando Aprobación de Presupuesto"), con espacio de firma de
 * aceptación y una nota de validez de la cotización.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$fila = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$fila) {
    die("<div style='padding:2rem;font-family:sans-serif;'>Orden no encontrada.</div>");
}

$fechaCotizacion = date('Y-m-d');
$fechaValidez = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización #<?= h($fila['id_orden']) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8; color:#222; margin:0; }
    .container { max-width:760px; margin:0 auto; padding: 26px; }
    .card { background:#fff; border-radius:12px; padding:30px 36px; box-shadow:0 8px 25px rgba(0,0,0,.08); border-top:5px solid #1FA35C; }
    .header { display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #e0e0e0; padding-bottom:14px; margin-bottom:20px; }
    .header img { width:70px; border-radius:8px; }
    .header .titulo-doc { text-align:right; }
    .header .titulo-doc h1 { font-size:1.25em; color:#14472A; margin:0; font-weight:700; }
    .header .titulo-doc .num { font-size:1.5em; font-weight:800; color:#1FA35C; }
    .section-title { font-size:0.95em; font-weight:700; color:#525D73; margin:18px 0 8px; border-bottom:1px solid #e1e5ec; padding-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:4px 24px; }
    .grid2 p { margin:4px 0; font-size:0.95em; }
    .grid2 p strong { color:#525D73; display:block; font-size:0.78em; text-transform:uppercase; letter-spacing:.03em; }
    .falla-box { background:#F5F7FA; border-radius:8px; padding:12px 14px; font-size:0.92em; margin-top:4px; }
    .monto-box { margin-top:18px; background:linear-gradient(120deg, #14472A, #1FA35C); color:#fff; border-radius:10px; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; }
    .monto-box .label { font-size:0.85em; text-transform:uppercase; letter-spacing:.04em; opacity:.9; }
    .monto-box .valor { font-size:1.8em; font-weight:800; }
    .terminos { font-size:0.78em; color:#525D73; line-height:1.5; margin-top:16px; background:#FBFCFE; border:1px solid #e1e5ec; border-radius:8px; padding:12px 14px; }
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
        .monto-box { flex-direction: column; align-items: flex-start; gap: 6px; }
        .monto-box .valor { font-size: 1.5em; }
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
                <h1>COTIZACIÓN DE REPARACIÓN</h1>
                <div class="num">ORDEN #<?= h(str_pad($fila['id_orden'], 5, '0', STR_PAD_LEFT)) ?></div>
            </div>
        </div>

        <div class="section-title">Datos del Cliente y Fecha</div>
        <div class="grid2">
            <p><strong>Cliente</strong> <?= h($fila['cliente']) ?></p>
            <p><strong>Fecha de Cotización</strong> <?= h(date('d/m/Y', strtotime($fechaCotizacion))) ?></p>
        </div>

        <div class="section-title">Equipo</div>
        <div class="grid2">
            <p><strong>Tipo de Equipo</strong> <?= h($fila['tipo_equipo'] ?: '—') ?></p>
            <p><strong>Marca / Modelo</strong> <?= h(trim(($fila['marca'] ?: '') . ' ' . $fila['modelo_impresora'])) ?></p>
        </div>

        <div class="section-title">Falla Reportada / Trabajo a Realizar</div>
        <div class="falla-box"><?= nl2br(h($fila['problema'])) ?></div>

        <div class="monto-box">
            <div class="label">Monto Estimado de la Reparación</div>
            <div class="valor">
                <?= $fila['monto_estimado'] !== null ? 'S/ ' . number_format($fila['monto_estimado'], 2) : 'Por definir' ?>
            </div>
        </div>

        <div class="terminos">
            <strong>Condiciones:</strong> Este monto es una estimación referencial sujeta a confirmación tras el
            diagnóstico técnico completo del equipo; puede variar si se detectan fallas adicionales no visibles al
            momento de la revisión inicial. Cotización válida hasta el <?= h(date('d/m/Y', strtotime($fechaValidez))) ?>.
            El trabajo se iniciará una vez el cliente confirme su aprobación.
        </div>

        <div class="firma-area">
            <div class="firma-box">
                <div class="firma-linea">Firma de Aprobación del Cliente</div>
                <div>DNI/RUC: _______________________</div>
            </div>
            <div class="firma-box">
                <div class="firma-linea">Cotizado por (Eros Tecnología)</div>
                <div>Fecha: <?= h(date('d/m/Y', strtotime($fechaCotizacion))) ?></div>
            </div>
        </div>

        <div class="footer">Eros Tecnología · Jirón Progreso con Av. La Mar, Imperial · Cel. 949 092 352</div>
    </div>
</div>
</body>
</html>
