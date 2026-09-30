<?php
/**
 * database/migrar_skus.php — NUEVO, herramienta de uso único.
 * ------------------------------------------------------------------
 * Recodifica los SKU ("codigo") de los productos YA existentes al
 * nuevo formato CATEGORIA-PRODUCTO-N° (ej. TEC-CAR-001), la misma
 * regla que ahora se autorellena en "Nuevo Producto". Los productos
 * que ya tengan ese formato se dejan intactos (script idempotente:
 * se puede volver a abrir sin duplicar trabajo).
 *
 * El campo `codigo` es clave foránea de `compras`, `detalle_venta`,
 * `detalle_nota_venta` y `orden_repuestos`, así que el cambio se
 * aplica en una sola transacción, con las validaciones de llave
 * foránea desactivadas solo durante ese instante (se reactivan
 * siempre al final, incluso si algo falla), para que el historial de
 * ventas/compras/órdenes quede apuntando al nuevo código.
 *
 * Es una pantalla de dos pasos por seguridad:
 *   1) Muestra una VISTA PREVIA (no cambia nada en la base de datos).
 *   2) Solo aplica los cambios si el Administrador confirma con el
 *      botón (POST + casilla de confirmación).
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';

if (!esAdministrador()) {
    flash('error', 'Solo un Administrador puede ejecutar la migración de códigos de producto.');
    header('Location: ' . url('index.php'));
    exit;
}

/** true si un código ya sigue el formato nuevo XXX-XXX-NNN (no se vuelve a tocar). */
function skuYaMigrado(string $codigo): bool
{
    return (bool)preg_match('/^[A-Z0-9]{2,4}-[A-Z0-9]{2,4}-\d{3,}$/', $codigo);
}

// --------------------------------------------------------------
// 1) Calcular el mapeo código_actual -> código_nuevo (en memoria,
//    sin tocar la base de datos todavía).
// --------------------------------------------------------------
$productos = [];
$resultado = mysqli_query($conexion, "SELECT codigo, descripcion, categoria FROM productos ORDER BY fecha_registro ASC, id ASC");
while ($fila = mysqli_fetch_assoc($resultado)) {
    $productos[] = $fila;
}

$contadores = []; // "PREFIJOCAT-PREFIJOPROD" => próximo número disponible
$mapeo = [];       // codigo_actual => codigo_nuevo (solo los que cambian)

foreach ($productos as $p) {
    if (skuYaMigrado($p['codigo'])) {
        continue; // ya tiene el formato nuevo, se respeta tal cual
    }
    $categoria = $p['categoria'] ?: 'Otros';
    $prefijoCat = abreviarCategoriaSku($categoria);
    $prefijoProd = abreviarProductoSku($p['descripcion']);
    $clave = $prefijoCat . '-' . $prefijoProd;

    $numero = $contadores[$clave] ?? 1;
    $contadores[$clave] = $numero + 1;

    $mapeo[$p['codigo']] = $clave . '-' . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
}

// --------------------------------------------------------------
// 2) Si el Administrador confirmó, aplicar los cambios.
// --------------------------------------------------------------
$resultadoAplicacion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_migracion']) && count($mapeo) > 0) {
    mysqli_query($conexion, "SET FOREIGN_KEY_CHECKS=0");
    mysqli_begin_transaction($conexion);

    try {
        $tablasHijas = [
            'compras'            => 'producto_codigo',
            'detalle_venta'      => 'producto_codigo',
            'detalle_nota_venta' => 'codigo_producto',
            'orden_repuestos'    => 'producto_codigo',
        ];

        foreach ($mapeo as $codigoActual => $codigoNuevo) {
            $stmt = mysqli_prepare($conexion, "UPDATE productos SET codigo = ? WHERE codigo = ?");
            mysqli_stmt_bind_param($stmt, "ss", $codigoNuevo, $codigoActual);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo actualizar productos.codigo: ' . mysqli_error($conexion));
            }

            foreach ($tablasHijas as $tabla => $columna) {
                $stmtHijo = mysqli_prepare($conexion, "UPDATE `$tabla` SET `$columna` = ? WHERE `$columna` = ?");
                mysqli_stmt_bind_param($stmtHijo, "ss", $codigoNuevo, $codigoActual);
                if (!mysqli_stmt_execute($stmtHijo)) {
                    throw new RuntimeException("No se pudo actualizar $tabla.$columna: " . mysqli_error($conexion));
                }
            }
        }

        mysqli_commit($conexion);
        $resultadoAplicacion = ['ok' => true, 'total' => count($mapeo)];
    } catch (\Throwable $e) {
        mysqli_rollback($conexion);
        $resultadoAplicacion = ['ok' => false, 'error' => $e->getMessage()];
    } finally {
        mysqli_query($conexion, "SET FOREIGN_KEY_CHECKS=1");
    }

    if ($resultadoAplicacion['ok']) {
        // Recalcular para mostrar la pantalla ya sin pendientes.
        header('Location: ' . url('database/migrar_skus.php?migrado=1'));
        exit;
    }
}

$page_title = 'Migrar códigos de producto (SKU)';
$page_subtitle = 'Herramienta única · Recodificación a CATEGORIA-PRODUCTO-N°';
include __DIR__ . '/../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-arrow-repeat"></i> Migrar Códigos de Producto (SKU)</h1>
    <p>Recodifica los productos existentes al nuevo formato CATEGORÍA-PRODUCTO-N° (ej. <strong>TEC-CAR-001</strong>), igual al que ahora se autorellena al registrar un producto nuevo.</p>
</div>

<?php if (isset($_GET['migrado'])): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Migración aplicada correctamente. Los productos, compras, ventas y órdenes ya reflejan los nuevos códigos.</div>
<?php endif; ?>

<?php if ($resultadoAplicacion && !$resultadoAplicacion['ok']): ?>
    <div class="alert alert-danger"><i class="bi bi-x-circle"></i> No se pudo aplicar la migración: <?= h($resultadoAplicacion['error']) ?>. No se modificó ningún dato (se revirtió todo).</div>
<?php endif; ?>

<div class="card-erp">
    <div class="card-erp-header">
        <h2><i class="bi bi-eye"></i> Vista previa</h2>
    </div>
    <div class="card-erp-body">
        <?php if (count($mapeo) === 0): ?>
            <p class="text-muted mb-0">No hay productos pendientes por recodificar: todos los códigos ya siguen el formato nuevo.</p>
        <?php else: ?>
            <p>Se van a renombrar <strong><?= count($mapeo) ?></strong> código(s). El historial de ventas, compras y órdenes se actualiza junto con el producto para que todo quede consistente.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead><tr><th>Código actual</th><th></th><th>Código nuevo</th></tr></thead>
                    <tbody>
                        <?php foreach ($mapeo as $actual => $nuevo): ?>
                            <tr>
                                <td><code><?= h($actual) ?></code></td>
                                <td class="text-center"><i class="bi bi-arrow-right"></i></td>
                                <td><code><?= h($nuevo) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="" onsubmit="return confirm('¿Aplicar la migración de códigos? Esta acción actualizará productos, compras, ventas y órdenes.');">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="confirmar_migracion" value="1" id="chkConfirmar" required>
                    <label class="form-check-label" for="chkConfirmar">
                        Entiendo que esto va a renombrar los códigos indicados arriba en todo el sistema.
                    </label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Confirmar y aplicar migración</button>
                <a href="<?= url('modules/productos/listado.php') ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
