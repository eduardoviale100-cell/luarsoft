<?php
/**
 * modules/clientes/imprimir.php — Reemplaza a "imprimir_cliente.php".
 * Página independiente (ventana de impresión), con el mismo formato
 * de ficha del sistema original pero con la nueva identidad visual.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("<div style='padding:2rem;font-family:sans-serif;'>ID de cliente no válido.</div>");
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    die("<div style='padding:2rem;font-family:sans-serif;'>Cliente no encontrado.</div>");
}
$cliente = mysqli_fetch_assoc($resultado);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha Cliente - <?= h($cliente['nombre']) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8; color:#333; }
    .container { max-width:800px; margin:40px auto; }
    .card { background:#fff; border-radius:12px; padding:30px 40px; box-shadow:0 8px 25px rgba(0,0,0,.1); border-top:5px solid #1FA35C; }
    .header { display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #e0e0e0; padding-bottom:15px; margin-bottom:25px; }
    .header img { width:90px; border-radius:8px; }
    .header h1 { font-size:1.6em; color:#14472A; margin:0; font-weight:700; }
    .section-title { font-size:1.05em; font-weight:700; color:#555; margin:20px 0 10px; border-bottom:1px solid #ddd; padding-bottom:5px; }
    .info p { margin:6px 0; font-size:1.02em; }
    .info p strong { width:180px; display:inline-block; color:#555; }
    .footer { text-align:center; font-size:.85em; color:#777; margin-top:30px; border-top:1px solid #ddd; padding-top:10px; }
    @media print { body{background:#fff;} .card{box-shadow:none;border-top:none;} }
    @media (max-width: 520px) {
        .container { margin: 16px auto; padding: 0 12px; }
        .card { padding: 22px 18px; }
        .header { flex-wrap: wrap; justify-content: center; text-align: center; gap: 10px; }
        .header h1 { width: 100%; order: -1; font-size: 1.3em; }
        .info p strong { width: 100%; display: block; }
    }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <img src="<?= url('assets/img/eros.jpg') ?>" alt="Eros">
            <h1>Ficha de Cliente</h1>
            <?php if (!empty($cliente['foto'])): ?>
                <img src="<?= url('uploads/clientes/' . $cliente['foto']) ?>" alt="Foto de <?= h($cliente['nombre']) ?>" style="width:85px; height:85px; object-fit:cover; border-radius:50%; border:2px solid #1FA35C;">
            <?php endif; ?>
        </div>

        <div class="section-title">Información General</div>
        <div class="info">
            <p><strong>ID Cliente:</strong> <?= h($cliente['id_cliente']) ?></p>
            <p><strong>Tipo de Documento:</strong> <?= h($cliente['tipo_documento']) ?></p>
            <p><strong>Número de Documento:</strong> <?= h($cliente['numero_documento']) ?></p>
            <p><strong>Nombre:</strong> <?= h($cliente['nombre']) ?></p>
            <p><strong>Teléfono:</strong> <?= h($cliente['telefono']) ?></p>
            <p><strong>Correo Electrónico:</strong> <?= h($cliente['email']) ?></p>
        </div>

        <div class="section-title">Ubicación</div>
        <div class="info">
            <p><strong>Dirección:</strong> <?= h($cliente['direccion']) ?></p>
            <p><strong>Ciudad / Pueblo:</strong> <?= h($cliente['ciudad']) ?></p>
            <p><strong>Región:</strong> <?= h($cliente['region']) ?></p>
        </div>

        <div class="footer">Sistema de Gestión de Clientes · LuarSoft - Eros Tecnología</div>
    </div>
</div>
</body>
</html>
