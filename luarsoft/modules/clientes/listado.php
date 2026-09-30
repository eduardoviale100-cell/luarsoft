<?php
/**
 * modules/clientes/listado.php
 * Reemplaza a "listado_clientes.php". Misma lógica (buscador +
 * listado con acciones editar/eliminar/imprimir), con la consulta
 * de búsqueda migrada a sentencia preparada.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$buscar = limpiar($_GET['buscar'] ?? '');

if ($buscar !== '') {
    $like = "%$buscar%";
    $stmt = mysqli_prepare($conexion, "SELECT * FROM clientes WHERE nombre LIKE ? OR numero_documento LIKE ? OR ciudad LIKE ? OR region LIKE ? ORDER BY id_cliente ASC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conexion, "SELECT * FROM clientes ORDER BY id_cliente ASC");
}

$page_title = 'Listado de Clientes';
$page_subtitle = 'Clientes · Listado general';
$active_menu = 'clientes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-list-ul"></i> Listado General de Clientes</h1>
        <p>Consulta, edita, elimina o imprime la ficha de cualquier cliente registrado.</p>
    </div>
    <a href="<?= url('modules/clientes/nuevo.php') ?>" class="btn btn-primary no-print"><i class="bi bi-plus-lg"></i> Nuevo Cliente</a>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="search-input-wrap">
                <span class="search-ico"><i class="bi bi-search"></i></span>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, documento, ciudad o región" value="<?= h($buscar) ?>">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="<?= url('modules/clientes/listado.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        </div>
    </form>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="no-print">Foto</th>
                    <th>ID</th><th>Tipo Doc.</th><th>N° Documento</th><th>Nombre</th>
                    <th>Teléfono</th><th>Dirección</th><th>Email</th><th>Ciudad</th><th>Región</th>
                    <th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td class="no-print">
                            <?php if (!empty($fila['foto'])): ?>
                                <img src="<?= url('uploads/clientes/' . $fila['foto']) ?>" alt="<?= h($fila['nombre']) ?>" style="width:44px; height:44px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                            <?php else: ?>
                                <span style="width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--n-100); color:var(--n-400);"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($fila['id_cliente']) ?></td>
                        <td><?= h($fila['tipo_documento']) ?></td>
                        <td><?= h($fila['numero_documento']) ?></td>
                        <td><?= h($fila['nombre']) ?></td>
                        <td><?= h($fila['telefono']) ?></td>
                        <td><?= h($fila['direccion']) ?></td>
                        <td><?= h($fila['email']) ?></td>
                        <td><?= h($fila['ciudad']) ?></td>
                        <td><?= h($fila['region']) ?></td>
                        <td class="no-print text-center">
                            <a href="<?= url('modules/clientes/editar.php?id=' . (int)$fila['id_cliente']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                            <a href="<?= url('modules/clientes/eliminar.php?id=' . (int)$fila['id_cliente']) ?>" class="btn btn-danger btn-sm" data-confirm-delete="¿Eliminar al cliente «<?= h($fila['nombre']) ?>»?"><i class="bi bi-trash3"></i></a>
                            <button type="button" class="btn btn-info btn-sm" onclick="ERP.verDocumento('<?= url('modules/clientes/imprimir.php?id=' . (int)$fila['id_cliente']) ?>', 'Ficha de <?= h($fila['nombre']) ?>', '<?= url('modules/clientes/listado.php') ?>')"><i class="bi bi-printer"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
