<?php
/**
 * includes/layout_top.php
 * ------------------------------------------------------------------
 * Abre el documento HTML, carga los estilos, y pinta el topbar +
 * sidebar comunes a toda la aplicación. Cada página define, ANTES
 * de incluir este archivo:
 *   $page_title     (obligatorio) — título mostrado en topbar y <title>
 *   $page_subtitle  (opcional)    — texto secundario / breadcrumb
 *   $active_menu    (opcional)   — clave del ítem de menú activo
 */
$page_title = $page_title ?? 'LuarSoft';
$flash = obtenerFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> · LuarSoft - Eros Tecnología</title>
    <link rel="icon" href="<?= url('assets/img/eros.jpg') ?>">
    <script>
        // Aplica el tema guardado ANTES de pintar la página, para evitar
        // un parpadeo (ver claro y luego cambiar a oscuro de golpe).
        (function () {
            try {
                const tema = localStorage.getItem('luarsoft_tema');
                if (tema === 'dark') { document.documentElement.setAttribute('data-bs-theme', 'dark'); }
            } catch (e) { /* localStorage no disponible: se queda en modo claro */ }
        })();
    </script>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($flash): ?>
    <div id="flash-data" data-tipo="<?= h($flash['tipo']) ?>" data-mensaje="<?= h($flash['mensaje']) ?>" style="display:none;"></div>
<?php endif; ?>

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main-wrapper">
    <div class="page-content">
