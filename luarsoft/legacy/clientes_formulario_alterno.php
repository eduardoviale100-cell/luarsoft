<?php
/**
 * legacy/clientes_formulario_alterno.php
 * ------------------------------------------------------------------
 * Este archivo unifica dos formularios duplicados del sistema
 * original que NUNCA estuvieron enlazados desde el menú principal
 * ni desde ninguna otra página: "agregar_cliente.php" y
 * "nuevo_cliente.php". Ambos hacían exactamente lo mismo que
 * modules/clientes/nuevo.php (insertar un cliente), por lo que se
 * conservan aquí, funcionando, por transparencia y continuidad,
 * pero el formulario recomendado y enlazado en el menú es
 * modules/clientes/nuevo.php.
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $telefono = limpiar($_POST['telefono'] ?? '');
    $direccion = limpiar($_POST['direccion'] ?? '');
    $email = limpiar($_POST['email'] ?? '');

    $stmt = mysqli_prepare($conexion, "INSERT INTO clientes (nombre, telefono, direccion, email) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssss", $nombre, $telefono, $direccion, $email);

    if (mysqli_stmt_execute($stmt)) {
        flash('success', 'Cliente añadido correctamente.');
        header('Location: ' . url('modules/clientes/listado.php'));
        exit;
    } else {
        flash('error', 'Error al añadir cliente.');
    }
}

$page_title = 'Añadir Cliente (formulario simplificado)';
$active_menu = 'clientes';
include __DIR__ . '/../includes/layout_top.php';
?>
<div class="page-heading">
    <h1>Agregar Nuevo Cliente</h1>
    <p>Formulario simplificado heredado del sistema original (solo datos básicos).</p>
</div>
<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST">
            <div class="form-grid">
                <div class="field-group"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                <div class="field-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" required></div>
                <div class="field-group"><label>Dirección</label><input type="text" name="direccion" class="form-control"></div>
                <div class="field-group"><label>Correo electrónico</label><input type="email" name="email" class="form-control"></div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
                <a href="<?= url('modules/clientes/listado.php') ?>" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
