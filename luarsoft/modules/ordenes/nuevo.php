<?php
/**
 * modules/ordenes/nuevo.php — Reemplaza a "agregar.php".
 * Ficha técnica ampliada para servicio técnico de PC/Laptops:
 * tipo de equipo, marca/modelo, N° de serie, contraseña (opcional),
 * accesorios dejados y observaciones del estado físico.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/_estados.php';

if (isset($_POST['guardar'])) {
    $cliente = limpiar($_POST['cliente']);
    $tipo_equipo = limpiar($_POST['tipo_equipo']);
    $modelo = limpiar($_POST['modelo_impresora']);
    $marca = limpiar($_POST['marca']);
    $numero_serie = limpiar($_POST['numero_serie']);
    $password_equipo = limpiar($_POST['password_equipo']);
    $accesorios = limpiar($_POST['accesorios_dejados']);
    $problema = limpiar($_POST['problema']);
    $observaciones = limpiar($_POST['observaciones_estado']);
    $garantia = (int)($_POST['garantia_dias'] ?? 0);
    $monto_estimado_raw = trim($_POST['monto_estimado'] ?? '');
    $monto_estimado = $monto_estimado_raw !== '' ? (float)$monto_estimado_raw : null;
    $fecha = limpiar($_POST['fecha_ingreso']);

    $stmt = mysqli_prepare($conexion, "INSERT INTO ordenes
        (cliente, tipo_equipo, modelo_impresora, marca, numero_serie, password_equipo, accesorios_dejados, problema, observaciones_estado, estado, garantia_dias, monto_estimado, fecha_ingreso)
        VALUES (?,?,?,?,?,?,?,?,?, 'Ingresado', ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssssssids",
        $cliente, $tipo_equipo, $modelo, $marca, $numero_serie, $password_equipo, $accesorios, $problema, $observaciones, $garantia, $monto_estimado, $fecha
    );

    if (mysqli_stmt_execute($stmt)) {
        $id_nueva = mysqli_insert_id($conexion);
        flash('success', 'Orden registrada correctamente. Puedes imprimir la constancia de recepción desde el listado.');
        header('Location: ' . url('modules/ordenes/listado.php'));
        exit;
    } else {
        flash('error', 'Error al registrar la orden: ' . mysqli_error($conexion));
    }
}

$page_title = 'Nueva Orden de Reparación';
$page_subtitle = 'Órdenes · Ficha técnica de ingreso';
$active_menu = 'ordenes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-plus-lg"></i> Registrar Nueva Orden de Reparación</h1>
    <p>Completa la ficha técnica de ingreso del equipo. Al guardar, la orden inicia en estado "Ingresado".</p>
</div>

<div class="card-erp">
    <div class="card-erp-header"><h3><i class="bi bi-person"></i> Datos del Cliente y Falla Reportada</h3></div>
    <div class="card-erp-body">
        <form method="POST">
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-person"></i> Cliente</label>
                    <input type="text" name="cliente" class="form-control" placeholder="Nombre del cliente" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-calendar-event"></i> Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="field-group">
                <label><i class="bi bi-exclamation-circle"></i> Falla Reportada por el Cliente</label>
                <textarea name="problema" class="form-control" rows="3" placeholder="Describa la falla tal como la reporta el cliente..." required></textarea>
            </div>

            <h3 class="mt-4 mb-3" style="font-size:1rem; color:var(--n-700);"><i class="bi bi-pc-display"></i> Ficha Técnica del Equipo</h3>
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-hdd-stack"></i> Tipo de Equipo</label>
                    <select name="tipo_equipo" class="form-select" required>
                        <?php foreach ($TIPOS_EQUIPO as $t): ?>
                            <option value="<?= h($t) ?>"><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-tag"></i> Marca</label>
                    <input type="text" name="marca" class="form-control" placeholder="Ej: HP, Lenovo, Epson...">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-cpu"></i> Modelo</label>
                    <input type="text" name="modelo_impresora" class="form-control" placeholder="Ej: IdeaPad 3 / DeskJet 2135" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-upc-scan"></i> Número de Serie</label>
                    <input type="text" name="numero_serie" class="form-control" placeholder="Opcional">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-key"></i> Contraseña del Sistema / BIOS</label>
                    <input type="text" name="password_equipo" class="form-control" placeholder="Opcional, solo para pruebas del técnico">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-shield-check"></i> Garantía del Servicio (días)</label>
                    <input type="number" name="garantia_dias" class="form-control" placeholder="Ej: 30" min="0" value="0">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-cash-coin"></i> Monto Estimado (Cotización)</label>
                    <input type="number" name="monto_estimado" class="form-control" placeholder="Opcional, ej: 150.00" min="0" step="0.01">
                </div>
            </div>
            <div class="field-group">
                <label><i class="bi bi-bag-check"></i> Accesorios Dejados</label>
                <input type="text" name="accesorios_dejados" class="form-control" placeholder="Ej: Cargador, mouse, maletín, cables...">
            </div>
            <div class="field-group">
                <label><i class="bi bi-card-checklist"></i> Observaciones del Estado Físico</label>
                <textarea name="observaciones_estado" class="form-control" rows="2" placeholder='Ej: "Tapa rayada", "Pantalla intacta", "Golpe en esquina superior"...'></textarea>
            </div>

            <div class="text-end mt-3">
                <button type="submit" name="guardar" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Orden</button>
                <a href="<?= url('modules/ordenes/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
