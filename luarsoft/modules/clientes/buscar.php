<?php
/**
 * modules/clientes/buscar.php
 * Reemplaza a "buscar_clientes.php". Buscador multi-campo de clientes.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$busqueda = limpiar($_GET['busqueda'] ?? '');

if ($busqueda !== '') {
    $like = "%$busqueda%";
    $stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE nombre LIKE ? OR telefono LIKE ? OR email LIKE ? OR tipo_documento LIKE ? OR numero_documento LIKE ? OR ciudad LIKE ? OR region LIKE ? ORDER BY id_cliente ASC");
    mysqli_stmt_bind_param($stmt, "sssssss", $like, $like, $like, $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conexion, "SELECT * FROM clientes ORDER BY id_cliente ASC");
}

$page_title = 'Buscar Clientes';
$page_subtitle = 'Clientes · Búsqueda avanzada';
$active_menu = 'clientes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-search"></i> Buscar Clientes</h1>
    <p>Encuentra rápidamente un cliente por cualquiera de sus datos registrados.</p>
</div>

<div class="search-toolbar">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="search-input-wrap">
                <span class="search-ico"><i class="bi bi-search"></i></span>
                <input type="text" name="busqueda" class="form-control" placeholder="Escribe nombre, documento, ciudad, etc." value="<?= h($busqueda) ?>">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a href="<?= url('modules/clientes/buscar.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th><th>Tipo Doc.</th><th>N° Documento</th><th>Nombre</th>
                    <th>Teléfono</th><th>Dirección</th><th>Email</th><th>Ciudad</th><th>Región</th><th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?= h($fila['id_cliente']) ?></td>
                        <td><?= h($fila['tipo_documento']) ?></td>
                        <td><?= h($fila['numero_documento']) ?></td>
                        <td><?= h($fila['nombre']) ?></td>
                        <td><?= h($fila['telefono']) ?></td>
                        <td><?= h($fila['direccion']) ?></td>
                        <td><?= h($fila['email']) ?></td>
                        <td><?= h($fila['ciudad']) ?></td>
                        <td><?= h($fila['region']) ?></td>
                        <td class="text-center">
                            <a href="<?= url('modules/clientes/editar.php?id=' . (int)$fila['id_cliente']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                            <a href="<?= url('modules/clientes/eliminar.php?id=' . (int)$fila['id_cliente']) ?>" class="btn btn-danger btn-sm" data-confirm-delete="¿Eliminar al cliente «<?= h($fila['nombre']) ?>»?"><i class="bi bi-trash3"></i></a>
                            <button type="button" class="btn btn-info btn-sm" onclick="ERP.verDocumento('<?= url('modules/clientes/imprimir.php?id=' . (int)$fila['id_cliente']) ?>', 'Ficha de <?= h($fila['nombre']) ?>', '<?= url('modules/clientes/buscar.php') ?>')"><i class="bi bi-printer"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No se encontraron clientes.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
