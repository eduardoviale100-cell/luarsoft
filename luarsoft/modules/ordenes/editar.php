<?php
/**
 * modules/ordenes/editar.php — Reemplaza a "editar.php" (órdenes).
 * Además de editar los datos de la orden, permite:
 *   - Avanzar el estado dentro del flujo de trabajo del taller.
 *   - Asignar repuestos del inventario a la orden (descuenta stock).
 *   - Definir la garantía del servicio.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/_estados.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    flash('error', 'Orden no encontrada.');
    header('Location: ' . url('modules/ordenes/listado.php'));
    exit;
}
$fila = mysqli_fetch_assoc($resultado);

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
    $estado = limpiar($_POST['estado']);
    $garantia = (int)($_POST['garantia_dias'] ?? 0);
    $monto_estimado_raw = trim($_POST['monto_estimado'] ?? '');
    $monto_estimado = $monto_estimado_raw !== '' ? (float)$monto_estimado_raw : null;
    $fecha = limpiar($_POST['fecha_ingreso']);
    $fecha_entrega = limpiar($_POST['fecha_entrega'] ?? '');
    $fecha_entrega_sql = $fecha_entrega !== '' ? $fecha_entrega : null;

    $foto_equipo = $fila['foto_equipo'] ?? null;
    try {
        $nuevaFoto = subirImagenReferencia($_FILES['foto_equipo'] ?? [], 'ordenes', 'orden_' . $id);
        if ($nuevaFoto !== null) {
            eliminarImagenReferencia($fila['foto_equipo'] ?? null, 'ordenes');
            $foto_equipo = $nuevaFoto;
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        header('Location: ' . url('modules/ordenes/editar.php?id=' . $id));
        exit;
    }

    $upd = mysqli_prepare($conexion, "UPDATE ordenes SET
        cliente=?, tipo_equipo=?, modelo_impresora=?, marca=?, numero_serie=?, password_equipo=?, accesorios_dejados=?,
        problema=?, observaciones_estado=?, estado=?, garantia_dias=?, monto_estimado=?, fecha_ingreso=?, fecha_entrega=?, foto_equipo=?
        WHERE id_orden=?");
    mysqli_stmt_bind_param($upd, "ssssssssssidsssi",
        $cliente, $tipo_equipo, $modelo, $marca, $numero_serie, $password_equipo, $accesorios,
        $problema, $observaciones, $estado, $garantia, $monto_estimado, $fecha, $fecha_entrega_sql, $foto_equipo, $id
    );
    mysqli_stmt_execute($upd);

    flash('success', 'Orden actualizada correctamente.');
    header('Location: ' . url('modules/ordenes/editar.php?id=' . $id));
    exit;
}

// Repuestos ya asignados a esta orden
$stmtRep = mysqli_prepare($conexion, "SELECT * FROM orden_repuestos WHERE id_orden = ? ORDER BY id_detalle ASC");
mysqli_stmt_bind_param($stmtRep, "i", $id);
mysqli_stmt_execute($stmtRep);
$repuestos = mysqli_stmt_get_result($stmtRep);
$totalRepuestos = 0;
$listaRepuestos = [];
if ($repuestos) {
    while ($r = mysqli_fetch_assoc($repuestos)) {
        $listaRepuestos[] = $r;
        $totalRepuestos += (float)$r['importe'];
    }
}

$page_title = 'Editar Orden de Reparación';
$page_subtitle = 'Órdenes · #' . $id;
$active_menu = 'ordenes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-pencil-square"></i> Editar Orden de Reparación #<?= $id ?></h1>
        <p>Cliente: <strong><?= h($fila['cliente']) ?></strong> · Equipo: <?= h($fila['marca'] . ' ' . $fila['modelo_impresora']) ?></p>
    </div>
    <div class="no-print">
        <button type="button" class="btn btn-info btn-sm" onclick="ERP.verDocumento('<?= url('modules/ordenes/recibo.php?id=' . $id) ?>', 'Constancia de Recepción #<?= $id ?>', '<?= url('modules/ordenes/editar.php?id=' . $id) ?>')">
            <i class="bi bi-receipt"></i> Constancia de Recepción
        </button>
        <button type="button" class="btn btn-success btn-sm" onclick="ERP.verDocumento('<?= url('modules/ordenes/cotizacion.php?id=' . $id) ?>', 'Cotización #<?= $id ?>', '<?= url('modules/ordenes/editar.php?id=' . $id) ?>')">
            <i class="bi bi-cash-coin"></i> Cotización
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="ERP.verDocumento('<?= url('modules/ordenes/imprimir.php?id=' . $id) ?>', 'Ticket de Entrega #<?= $id ?>', '<?= url('modules/ordenes/editar.php?id=' . $id) ?>')">
            <i class="bi bi-printer"></i> Ticket de Entrega
        </button>
    </div>
</div>

<!-- Línea de tiempo visual del flujo de trabajo -->
<div class="card-erp mb-3">
    <div class="card-erp-body py-3">
        <div class="estado-timeline">
            <?php
            $pasos = array_keys($ESTADOS_FLUJO);
            $estadoActualIdx = array_search($fila['estado'], $pasos);
            foreach ($pasos as $i => $pasoNombre):
                $info = $ESTADOS_FLUJO[$pasoNombre];
                $clase = '';
                if ($estadoActualIdx !== false) {
                    if ($i < $estadoActualIdx) $clase = 'hecho';
                    elseif ($i === $estadoActualIdx) $clase = 'actual';
                }
            ?>
                <div class="paso <?= $clase ?>">
                    <i class="bi <?= $info['icon'] ?>"></i>
                    <?= h($pasoNombre) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($estadoActualIdx === false): ?>
            <p class="text-muted small mb-0 mt-2"><i class="bi bi-info-circle"></i> Estado actual: <?= badgeEstadoOrden($fila['estado'], $ESTADOS_TODOS) ?> (estado heredado del sistema anterior)</p>
        <?php endif; ?>
    </div>
</div>

<div class="card-erp mb-3">
    <div class="card-erp-header"><h3><i class="bi bi-pc-display"></i> Ficha Técnica y Estado</h3></div>
    <div class="card-erp-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label><i class="bi bi-person"></i> Cliente</label>
                    <input type="text" name="cliente" class="form-control" value="<?= h($fila['cliente']) ?>" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-flag"></i> Estado Actual</label>
                    <select name="estado" class="form-select" required>
                        <optgroup label="Flujo de trabajo">
                            <?php foreach ($ESTADOS_FLUJO as $nombre => $info): ?>
                                <option value="<?= h($nombre) ?>" <?= $fila['estado'] === $nombre ? 'selected' : '' ?>><?= h($nombre) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php if (isset($ESTADOS_LEGACY[$fila['estado']])): ?>
                        <optgroup label="Estado heredado">
                            <option value="<?= h($fila['estado']) ?>" selected><?= h($fila['estado']) ?> (heredado)</option>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-hdd-stack"></i> Tipo de Equipo</label>
                    <select name="tipo_equipo" class="form-select">
                        <?php foreach ($TIPOS_EQUIPO as $t): ?>
                            <option value="<?= h($t) ?>" <?= $fila['tipo_equipo'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-tag"></i> Marca</label>
                    <input type="text" name="marca" class="form-control" value="<?= h($fila['marca']) ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-cpu"></i> Modelo</label>
                    <input type="text" name="modelo_impresora" class="form-control" value="<?= h($fila['modelo_impresora']) ?>" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-upc-scan"></i> Número de Serie</label>
                    <input type="text" name="numero_serie" class="form-control" value="<?= h($fila['numero_serie']) ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-key"></i> Contraseña del Sistema / BIOS</label>
                    <input type="text" name="password_equipo" class="form-control" value="<?= h($fila['password_equipo']) ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-shield-check"></i> Garantía del Servicio (días)</label>
                    <input type="number" name="garantia_dias" class="form-control" min="0" value="<?= h($fila['garantia_dias'] ?? 0) ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-cash-coin"></i> Monto Estimado (Cotización)</label>
                    <input type="number" name="monto_estimado" class="form-control" min="0" step="0.01" placeholder="Opcional" value="<?= $fila['monto_estimado'] !== null ? h($fila['monto_estimado']) : '' ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-calendar-event"></i> Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="form-control" value="<?= h($fila['fecha_ingreso']) ?>" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-calendar-check"></i> Fecha de Entrega</label>
                    <input type="date" name="fecha_entrega" class="form-control" value="<?= h($fila['fecha_entrega']) ?>">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-camera"></i> Foto del Equipo</label>
                    <?php if (!empty($fila['foto_equipo'])): ?>
                        <div class="mb-2">
                            <img src="<?= url('uploads/ordenes/' . $fila['foto_equipo']) ?>" alt="Foto del equipo" style="width:110px; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--n-200);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto_equipo" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB. Deja en blanco para conservar la actual.</small>
                </div>
            </div>
            <div class="field-group">
                <label><i class="bi bi-bag-check"></i> Accesorios Dejados</label>
                <input type="text" name="accesorios_dejados" class="form-control" value="<?= h($fila['accesorios_dejados']) ?>">
            </div>
            <div class="field-group">
                <label><i class="bi bi-exclamation-circle"></i> Falla Reportada</label>
                <textarea name="problema" class="form-control" rows="2" required><?= h($fila['problema']) ?></textarea>
            </div>
            <div class="field-group">
                <label><i class="bi bi-card-checklist"></i> Observaciones del Estado Físico</label>
                <textarea name="observaciones_estado" class="form-control" rows="2"><?= h($fila['observaciones_estado']) ?></textarea>
            </div>
            <div class="text-end mt-2">
                <button type="submit" name="guardar" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                <a href="<?= url('modules/ordenes/listado.php') ?>" class="btn btn-secondary">Volver al Listado</a>
            </div>
        </form>
    </div>
</div>

<!-- Asignación de repuestos del inventario -->
<div class="card-erp">
    <div class="card-erp-header"><h3><i class="bi bi-tools"></i> Repuestos Utilizados</h3></div>
    <div class="card-erp-body">
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
                <thead><tr><th>Código</th><th>Descripción</th><th class="text-center">Cant.</th><th class="text-end">P. Unit.</th><th class="text-end">Importe</th><th class="no-print"></th></tr></thead>
                <tbody id="tablaRepuestos">
                    <?php if (empty($listaRepuestos)): ?>
                        <tr id="filaVaciaRepuestos"><td colspan="6" class="text-center text-muted py-3">Aún no se han asignado repuestos a esta orden.</td></tr>
                    <?php else: foreach ($listaRepuestos as $r): ?>
                        <tr id="repuesto-<?= $r['id_detalle'] ?>">
                            <td><?= h($r['producto_codigo']) ?></td>
                            <td><?= h($r['descripcion']) ?></td>
                            <td class="text-center"><?= number_format($r['cantidad'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['precio_unitario'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['importe'], 2) ?></td>
                            <td class="no-print text-center">
                                <button class="btn btn-danger btn-sm btn-icon-only" onclick="eliminarRepuesto(<?= $r['id_detalle'] ?>)" title="Quitar y devolver stock">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-end">Total repuestos:</th><th class="text-end" id="totalRepuestos"><?= number_format($totalRepuestos, 2) ?></th><th class="no-print"></th></tr>
                </tfoot>
            </table>
        </div>

        <div class="row g-2 align-items-end no-print">
            <div class="col-md-5">
                <label><i class="bi bi-search"></i> Buscar Repuesto en Inventario</label>
                <div class="autocomplete-list-container" style="position:relative;">
                    <input type="text" id="buscarRepuesto" class="form-control" placeholder="Escriba el nombre del producto...">
                    <input type="hidden" id="codigoRepuestoSeleccionado">
                </div>
            </div>
            <div class="col-md-2">
                <label>Cantidad</label>
                <input type="number" id="cantidadRepuesto" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-2">
                <label>Precio Unit.</label>
                <input type="number" id="precioRepuesto" class="form-control" step="0.01" readonly>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-primary w-100" onclick="agregarRepuesto()"><i class="bi bi-plus-lg"></i> Asignar a la Orden</button>
            </div>
        </div>
    </div>
</div>

<script>
const ID_ORDEN = <?= $id ?>;
const ENDPOINT_BUSCAR_REPUESTO = "<?= url('modules/productos/buscar_autocomplete.php') ?>";
const ENDPOINT_AGREGAR_REPUESTO = "<?= url('modules/ordenes/agregar_repuesto.php') ?>";
const ENDPOINT_ELIMINAR_REPUESTO = "<?= url('modules/ordenes/eliminar_repuesto.php') ?>";

let repuestoDescripcionSel = '';
let inputTimeoutRep;
document.getElementById('buscarRepuesto').addEventListener('input', function (e) {
    clearTimeout(inputTimeoutRep);
    const query = e.target.value.trim();
    document.getElementById('codigoRepuestoSeleccionado').value = '';
    if (query.length < 3) { hideListaRepuestos(); return; }
    inputTimeoutRep = setTimeout(async () => {
        const res = await fetch(`${ENDPOINT_BUSCAR_REPUESTO}?query=${encodeURIComponent(query)}`);
        const productos = await res.json();
        mostrarListaRepuestos(productos);
    }, 300);
});

function mostrarListaRepuestos(productos) {
    hideListaRepuestos();
    const container = document.getElementById('buscarRepuesto').parentElement;
    const ul = document.createElement('ul');
    ul.className = 'autocomplete-list';
    ul.id = 'listaAutocompleteRepuestos';
    if (productos.length === 0) {
        ul.innerHTML = '<li class="autocomplete-item" style="cursor:default;">Sin coincidencias con stock disponible.</li>';
    } else {
        productos.forEach(p => {
            const li = document.createElement('li');
            li.className = 'autocomplete-item';
            li.innerHTML = `${p.descripcion} <span class="text-muted">(Cód: ${p.codigo} | Stock: ${p.stock})</span>`;
            li.addEventListener('click', () => {
                document.getElementById('buscarRepuesto').value = p.descripcion;
                document.getElementById('codigoRepuestoSeleccionado').value = p.codigo;
                document.getElementById('precioRepuesto').value = p.precio.toFixed(2);
                repuestoDescripcionSel = p.descripcion;
                hideListaRepuestos();
            });
            ul.appendChild(li);
        });
    }
    container.appendChild(ul);
}
function hideListaRepuestos() {
    document.getElementById('listaAutocompleteRepuestos')?.remove();
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.autocomplete-list-container')) hideListaRepuestos();
});

async function agregarRepuesto() {
    const codigo = document.getElementById('codigoRepuestoSeleccionado').value;
    const cantidad = parseFloat(document.getElementById('cantidadRepuesto').value) || 0;
    const precio = parseFloat(document.getElementById('precioRepuesto').value) || 0;

    if (!codigo) { ERP.toast('warning', 'Selecciona un producto de la lista de resultados.'); return; }
    if (cantidad <= 0) { ERP.toast('warning', 'La cantidad debe ser mayor a cero.'); return; }

    const res = await fetch(ENDPOINT_AGREGAR_REPUESTO, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_orden: ID_ORDEN, codigo, descripcion: repuestoDescripcionSel, cantidad, precio_unitario: precio })
    });
    const data = await res.json();
    if (data.success) {
        ERP.toast('success', 'Repuesto asignado y descontado del inventario.');
        setTimeout(() => window.location.reload(), 700);
    } else {
        ERP.toast('error', data.message || 'No se pudo asignar el repuesto.');
    }
}

async function eliminarRepuesto(idDetalle) {
    const ok = await ERP.confirmarEliminar('Se quitará el repuesto de la orden y se devolverá el stock al inventario.');
    if (!ok) return;
    const res = await fetch(`${ENDPOINT_ELIMINAR_REPUESTO}?id=${idDetalle}`);
    const data = await res.json();
    if (data.success) {
        ERP.toast('success', 'Repuesto quitado y stock restituido.');
        setTimeout(() => window.location.reload(), 700);
    } else {
        ERP.toast('error', data.message || 'No se pudo quitar el repuesto.');
    }
}
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
