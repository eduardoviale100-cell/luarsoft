<?php
/**
 * modules/tecnicos/listado.php — Reemplaza a "tecnicos.php".
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

$resultado = mysqli_query($conexion, "SELECT * FROM tecnicos ORDER BY id_tecnico ASC");

$page_title = 'Listado de Técnicos';
$page_subtitle = 'Técnicos · Listado general';
$active_menu = 'tecnicos';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-tools"></i> Gestión de Técnicos</h1>
        <p>Administra el equipo técnico encargado de las reparaciones.</p>
    </div>
    <a href="<?= url('modules/tecnicos/nuevo.php') ?>" class="btn btn-primary no-print"><i class="bi bi-plus-lg"></i> Nuevo Técnico</a>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th class="no-print">Foto</th><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Teléfono</th><th>Email</th><th class="no-print text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td class="no-print">
                            <?php if (!empty($fila['foto'])): ?>
                                <img src="<?= url('uploads/tecnicos/' . $fila['foto']) ?>" alt="<?= h($fila['nombre']) ?>" style="width:44px; height:44px; object-fit:cover; border-radius:50%; border:1px solid var(--n-200);">
                            <?php else: ?>
                                <span style="width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--n-100); color:var(--n-400);"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($fila['id_tecnico']) ?></td>
                        <td><?= h($fila['nombre']) ?></td>
                        <td><?= h($fila['especialidad']) ?></td>
                        <td><?= h($fila['telefono']) ?></td>
                        <td><?= h($fila['email']) ?></td>
                        <td class="no-print text-center">
                            <a href="<?= url('modules/tecnicos/editar.php?id=' . (int)$fila['id_tecnico']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                            <a href="<?= url('modules/tecnicos/eliminar.php?id=' . (int)$fila['id_tecnico']) ?>" class="btn btn-danger btn-sm" data-confirm-delete="¿Eliminar al técnico «<?= h($fila['nombre']) ?>»?"><i class="bi bi-trash3"></i></a>
                            <button type="button" class="btn btn-info btn-sm" title="Imprimir ficha de este técnico"
                                onclick="ERP.verDocumento('<?= url('modules/tecnicos/imprimir.php?id=' . (int)$fila['id_tecnico']) ?>', 'Ficha de <?= h($fila['nombre']) ?>', '<?= url('modules/tecnicos/listado.php') ?>')">
                                <i class="bi bi-printer"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay técnicos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
