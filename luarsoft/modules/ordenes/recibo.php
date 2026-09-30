<?php
/**
 * modules/ordenes/recibo.php
 * ------------------------------------------------------------------
 * NUEVO: Constancia / Ticket de Recepción de Equipo. Se entrega al
 * cliente al momento de dejar el equipo en el taller: detalla la
 * falla reportada, el estado físico observado, los accesorios
 * dejados y los términos de revisión, con una línea para la firma
 * del cliente. Documento independiente (sin sidebar), pensado para
 * mostrarse dentro del modal de documentos o en pantalla completa.
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Constancia de Recepción #<?= h($fila['id_orden']) ?></title>
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
    .falla-box, .obs-box { background:#F5F7FA; border-radius:8px; padding:12px 14px; font-size:0.92em; margin-top:4px; }
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
                <h1>CONSTANCIA DE RECEPCIÓN DE EQUIPO</h1>
                <div class="num">ORDEN #<?= h(str_pad($fila['id_orden'], 5, '0', STR_PAD_LEFT)) ?></div>
            </div>
        </div>

        <div class="section-title">Datos del Cliente y Fecha</div>
        <div class="grid2">
            <p><strong>Cliente</strong> <?= h($fila['cliente']) ?></p>
            <p><strong>Fecha de Ingreso</strong> <?= h(date('d/m/Y', strtotime($fila['fecha_ingreso']))) ?></p>
        </div>

        <div class="section-title">Ficha Técnica del Equipo</div>
        <div class="grid2">
            <p><strong>Tipo de Equipo</strong> <?= h($fila['tipo_equipo'] ?: '—') ?></p>
            <p><strong>Marca</strong> <?= h($fila['marca'] ?: '—') ?></p>
            <p><strong>Modelo</strong> <?= h($fila['modelo_impresora']) ?></p>
            <p><strong>Número de Serie</strong> <?= h($fila['numero_serie'] ?: 'No proporcionado') ?></p>
        </div>
        <p style="margin-top:10px;"><strong style="color:#525D73; display:block; font-size:0.78em; text-transform:uppercase;">Accesorios Dejados</strong> <?= h($fila['accesorios_dejados'] ?: 'Ninguno') ?></p>

        <div class="section-title">Falla Reportada por el Cliente</div>
        <div class="falla-box"><?= nl2br(h($fila['problema'])) ?></div>

        <div class="section-title">Observaciones del Estado Físico al Ingreso</div>
        <div class="obs-box"><?= nl2br(h($fila['observaciones_estado'] ?: 'Sin observaciones adicionales.')) ?></div>

        <div class="terminos">
            <strong>Términos de Revisión:</strong> El equipo descrito queda en calidad de depósito para diagnóstico
            y/o reparación en Eros Tecnología. El cliente declara haber sido informado del estado físico y la falla
            reportada al momento de la entrega. El diagnóstico técnico es referencial y puede variar durante la
            revisión; cualquier costo adicional será comunicado antes de proceder. Equipos no recogidos después de
            30 días de notificada la reparación quedarán sujetos a la política de almacenamiento de la empresa.
        </div>

        <div class="firma-area">
            <div class="firma-box">
                <div class="firma-linea">Firma del Cliente</div>
                <div>DNI/RUC: _______________________</div>
            </div>
            <div class="firma-box">
                <div class="firma-linea">Recibido por (Eros Tecnología)</div>
                <div>Fecha: <?= h(date('d/m/Y', strtotime($fila['fecha_ingreso']))) ?></div>
            </div>
        </div>

        <div class="footer">Eros Tecnología · Jirón Progreso con Av. La Mar, Imperial · Cel. 949 092 352</div>
    </div>
</div>
</body>
</html>
