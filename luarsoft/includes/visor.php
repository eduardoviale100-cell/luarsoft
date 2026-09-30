<?php
/**
 * includes/visor.php
 * ------------------------------------------------------------------
 * Vista alternativa de "pantalla completa" para documentos
 * imprimibles (boletas, facturas, tickets, recibos de orden). A
 * diferencia del modal (ver document_modal.php), esta vista se abre
 * en la MISMA pestaña, conserva el Header/Sidebar del sistema, y
 * muestra una flecha "Volver" para regresar al listado de origen
 * con un clic. Parámetros por GET:
 *   src    -> ruta relativa (dentro del sistema) del documento a mostrar
 *   titulo -> título a mostrar en la cabecera de la página
 *   volver -> ruta relativa a la que debe volver el botón "Volver"
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';

$src = $_GET['src'] ?? '';
$titulo = $_GET['titulo'] ?? 'Documento';
$volver = $_GET['volver'] ?? 'index.php';

// Seguridad: solo se permite mostrar documentos dentro del propio dominio del sistema (rutas relativas propias)
$src = ltrim($src, '/');
if ($src === '' || strpos($src, '://') !== false || strpos($src, '..') !== false) {
    $src = '';
}

$page_title = $titulo;
$page_subtitle = 'Vista de documento en pantalla completa';
include __DIR__ . '/layout_top.php';
?>

<a href="<?= url($volver) ?>" class="btn-volver no-print">
    <i class="bi bi-arrow-left"></i> Volver al listado
</a>

<div class="page-heading">
    <h1><i class="bi bi-file-earmark-text"></i> <?= h($titulo) ?></h1>
</div>

<?php if ($src === ''): ?>
    <div class="alert alert-danger">No se especificó un documento válido para mostrar.</div>
<?php else: ?>
    <div class="card-erp">
        <div class="card-erp-body" style="display:flex; justify-content:center; padding: 26px;">
            <iframe src="<?= url($src) ?>" style="width:100%; max-width:520px; min-height:80vh; border:none; box-shadow: var(--shadow-md); border-radius:8px; background:#fff;" title="<?= h($titulo) ?>"></iframe>
        </div>
    </div>
    <div class="text-center mt-3 no-print">
        <button class="btn btn-primary" onclick="document.querySelector('iframe').contentWindow.print()">
            <i class="bi bi-printer"></i> Imprimir / Descargar PDF
        </button>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
