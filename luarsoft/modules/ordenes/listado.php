<?php
/**
 * modules/ordenes/listado.php — Reemplaza a "mostrar_articulo.php".
 * Ahora muestra tipo de equipo/marca/modelo, badges del nuevo flujo
 * de estados, y abre recibo/ticket en el modal de documentos (sin
 * salir de la pestaña).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/_estados.php';

$buscar = limpiar($_GET['buscar'] ?? '');
if ($buscar !== '') {
    $like = "%$buscar%";
    $stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE cliente LIKE ? OR modelo_impresora LIKE ? OR marca LIKE ? OR estado LIKE ? ORDER BY id_orden DESC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conexion, "SELECT * FROM ordenes ORDER BY id_orden DESC");
}

$page_title = 'Órdenes de Reparación';
$page_subtitle = 'Órdenes · Listado general';
$active_menu = 'ordenes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-laptop"></i> Órdenes de Reparación</h1>
        <p>Seguimiento técnico de laptops, PC, equipos AIO e impresoras ingresados a taller.</p>
    </div>
    <a href="<?= url('modules/ordenes/nuevo.php') ?>" class="btn btn-primary no-print"><i class="bi bi-plus-lg"></i> Nueva Orden</a>
</div>

<div class="search-toolbar no-print">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="search-input-wrap">
                <span class="search-ico"><i class="bi bi-search"></i></span>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por cliente, marca, modelo o estado..." value="<?= h($buscar) ?>">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="<?= url('modules/ordenes/listado.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="table-erp-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="no-print">Foto</th>
                    <th>ID</th><th>Cliente</th><th>Equipo</th><th>Falla Reportada</th>
                    <th>Estado</th><th>Ingreso</th><th class="no-print text-center">Evidencia</th><th class="no-print text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td class="no-print">
                            <?php if (!empty($fila['foto_equipo'])): ?>
                                <img src="<?= url('uploads/ordenes/' . $fila['foto_equipo']) ?>" alt="Foto del equipo" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--n-200);">
                            <?php else: ?>
                                <span style="width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:var(--n-100); color:var(--n-400);"><i class="bi bi-camera"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>#<?= h($fila['id_orden']) ?></td>
                        <td><?= h($fila['cliente']) ?></td>
                        <td>
                            <?= h($fila['tipo_equipo'] ?: 'Impresora') ?><br>
                            <small class="text-muted"><?= h(trim(($fila['marca'] ?? '') . ' ' . $fila['modelo_impresora'])) ?></small>
                        </td>
                        <td style="max-width:260px;"><?= nl2br(h($fila['problema'])) ?></td>
                        <td><?= badgeEstadoOrden($fila['estado'], $ESTADOS_TODOS) ?></td>
                        <td><?= h(date('d/m/Y', strtotime($fila['fecha_ingreso']))) ?></td>
                        <td class="no-print text-center">
                            <button type="button" class="btn btn-success btn-sm btn-icon-only" title="Ver / Cargar Evidencia"
                                onclick="abrirModalEvidencia(<?= (int)$fila['id_orden'] ?>)">
                                <i class="bi bi-camera-reels"></i>
                            </button>
                        </td>
                        <td class="no-print text-center text-nowrap">
                            <a href="<?= url('modules/ordenes/editar.php?id=' . (int)$fila['id_orden']) ?>" class="btn btn-warning btn-sm btn-icon-only" title="Editar / gestionar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm btn-icon-only" title="Constancia de recepción"
                                onclick="ERP.verDocumento('<?= url('modules/ordenes/recibo.php?id=' . (int)$fila['id_orden']) ?>', 'Constancia de Recepción #<?= (int)$fila['id_orden'] ?>', '<?= url('modules/ordenes/listado.php') ?>')">
                                <i class="bi bi-receipt"></i>
                            </button>
                            <button type="button" class="btn btn-success btn-sm btn-icon-only" title="Cotización"
                                onclick="ERP.verDocumento('<?= url('modules/ordenes/cotizacion.php?id=' . (int)$fila['id_orden']) ?>', 'Cotización #<?= (int)$fila['id_orden'] ?>', '<?= url('modules/ordenes/listado.php') ?>')">
                                <i class="bi bi-cash-coin"></i>
                            </button>
                            <button type="button" class="btn btn-info btn-sm btn-icon-only" title="Ticket de entrega"
                                onclick="ERP.verDocumento('<?= url('modules/ordenes/imprimir.php?id=' . (int)$fila['id_orden']) ?>', 'Ticket de Entrega #<?= (int)$fila['id_orden'] ?>', '<?= url('modules/ordenes/listado.php') ?>')">
                                <i class="bi bi-printer"></i>
                            </button>
                            <a href="<?= url('modules/ordenes/eliminar.php?id=' . (int)$fila['id_orden']) ?>" class="btn btn-danger btn-sm btn-icon-only" title="Eliminar" data-confirm-delete="¿Eliminar esta orden de reparación? Se quitarán también los repuestos asignados (el stock no se restituye automáticamente).">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay órdenes registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ==================================================================
     MODAL DE EVIDENCIA EN VIDEO (Mejora 1)
     Reproductor de video + ficha de resumen + envío por WhatsApp,
     sin salir de la pantalla de Órdenes de Reparación.
=================================================================== -->
<div class="modal fade" id="modalEvidencia" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-camera-reels"></i>
          Evidencia de Reparación — Orden #<span id="evOrdenId">-</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="evVideoWrap" class="text-center mb-2">
          <video id="evVideoPlayer" controls style="width:100%; max-height:320px; background:#000; border-radius:8px; display:none;"></video>
          <p id="evSinVideo" class="text-muted small mb-0 py-4" style="border:1.5px dashed var(--n-300); border-radius:8px;">
            <i class="bi bi-film"></i> Aún no se ha cargado un video de prueba para esta orden.
          </p>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold"><i class="bi bi-upload"></i> Cargar / Reemplazar Video de Prueba</label>
          <input type="file" id="evVideoInput" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime,.mov">
          <small class="text-muted">Formatos permitidos: MP4, WEBM, OGG, MOV. Máximo 100 MB.</small>
        </div>

        <hr>

        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <label class="form-label fw-bold"><i class="bi bi-person"></i> Cliente</label>
            <input type="text" id="evCliente" class="form-control" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold"><i class="bi bi-pc-display"></i> Equipo / Modelo</label>
            <input type="text" id="evEquipo" class="form-control" readonly>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label fw-bold"><i class="bi bi-exclamation-circle"></i> Falla Reportada</label>
          <textarea id="evFalla" class="form-control" rows="2" readonly></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label fw-bold"><i class="bi bi-check2-circle"></i> Solución Aplicada</label>
          <textarea id="evSolucion" class="form-control" rows="2" placeholder="Describe la solución aplicada a la reparación..."></textarea>
        </div>
        <div class="mb-1">
          <label class="form-label fw-bold"><i class="bi bi-whatsapp"></i> Teléfono del Cliente (WhatsApp)</label>
          <input type="text" id="evTelefono" class="form-control" placeholder="Ej: 949092352">
          <small class="text-muted">Se completa automáticamente si el cliente está registrado. La API de WhatsApp no permite adjuntar el archivo de video de forma automática, así que el mensaje incluye el enlace directo al video.</small>
        </div>
      </div>
      <div class="modal-footer flex-wrap">
        <button type="button" class="btn btn-primary btn-sm" onclick="guardarEvidencia()">
          <i class="bi bi-save"></i> Guardar
        </button>
        <button type="button" class="btn btn-sm" style="background:#25D366; color:#fff; font-weight:600;" onclick="enviarEvidenciaWhatsapp()">
          <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bi bi-x-lg"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const ENDPOINT_EVIDENCIA = "<?= url('modules/ordenes/evidencia.php') ?>";
let evOrdenActualId = null;

async function abrirModalEvidencia(idOrden) {
    evOrdenActualId = idOrden;
    document.getElementById('evOrdenId').textContent = idOrden;
    document.getElementById('evVideoInput').value = '';

    try {
        const res = await fetch(`${ENDPOINT_EVIDENCIA}?accion=obtener&id=${idOrden}`);
        const data = await res.json();
        if (!data.success) {
            ERP.toast('error', data.message || 'No se pudo cargar la evidencia de la orden.');
            return;
        }
        pintarEvidencia(data);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvidencia'));
        modal.show();
    } catch (e) {
        console.error(e);
        ERP.toast('error', 'Error de conexión al abrir la evidencia.');
    }
}

function pintarEvidencia(data) {
    document.getElementById('evCliente').value = data.cliente || '';
    document.getElementById('evEquipo').value = data.equipo || '';
    document.getElementById('evFalla').value = data.falla || '';
    document.getElementById('evSolucion').value = data.solucion_aplicada || '';
    document.getElementById('evTelefono').value = data.telefono || '';

    const player = document.getElementById('evVideoPlayer');
    const sinVideo = document.getElementById('evSinVideo');
    if (data.evidencia_video_url) {
        player.onerror = function () {
            player.style.display = 'none';
            sinVideo.innerHTML = '<i class="bi bi-camera-video-off"></i> Este video no está disponible en este equipo (revisa que la carpeta uploads/ se haya copiado junto con el sistema).';
            sinVideo.style.display = 'block';
        };
        player.src = data.evidencia_video_url;
        player.style.display = 'block';
        sinVideo.style.display = 'none';
    } else {
        player.removeAttribute('src');
        player.style.display = 'none';
        sinVideo.innerHTML = '<i class="bi bi-film"></i> Aún no se ha cargado un video de prueba para esta orden.';
        sinVideo.style.display = 'block';
    }
}

async function guardarEvidencia() {
    if (!evOrdenActualId) return;

    const formData = new FormData();
    formData.append('accion', 'guardar');
    formData.append('id_orden', evOrdenActualId);
    formData.append('solucion_aplicada', document.getElementById('evSolucion').value);
    formData.append('telefono', document.getElementById('evTelefono').value);

    const archivo = document.getElementById('evVideoInput').files[0];
    if (archivo) {
        formData.append('video', archivo);
    }

    try {
        const res = await fetch(ENDPOINT_EVIDENCIA, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            ERP.toast('success', data.message || 'Evidencia guardada correctamente.');
            document.getElementById('evVideoInput').value = '';
            pintarEvidencia(data);
        } else {
            ERP.toast('error', data.message || 'No se pudo guardar la evidencia.');
        }
    } catch (e) {
        console.error(e);
        ERP.toast('error', 'Error de conexión al guardar la evidencia.');
    }
}

function enviarEvidenciaWhatsapp() {
    const cliente = document.getElementById('evCliente').value.trim();
    const equipo = document.getElementById('evEquipo').value.trim();
    const falla = document.getElementById('evFalla').value.trim();
    const solucion = document.getElementById('evSolucion').value.trim();
    const telefonoRaw = document.getElementById('evTelefono').value.trim();
    const videoUrl = document.getElementById('evVideoPlayer').getAttribute('src') || '';

    if (!telefonoRaw) {
        ERP.toast('warning', 'Ingresa el teléfono del cliente para poder enviarle el WhatsApp.');
        return;
    }
    if (!videoUrl) {
        ERP.toast('warning', 'Primero carga y guarda el video de evidencia antes de enviarlo.');
        return;
    }

    // Normaliza el teléfono al formato de WhatsApp (con código de país).
    let telefono = telefonoRaw.replace(/[^0-9]/g, '');
    if (telefono.length === 9) {
        telefono = '51' + telefono; // Celular peruano de 9 dígitos -> agrega código de país (Perú).
    }

    let mensaje = `Hola ${cliente}, te saludamos de Eros - LuarSoft. 🛠️\n`;
    mensaje += `Adjuntamos el video con la prueba de reparación de tu equipo ${equipo}.\n`;
    mensaje += `🎥 Video: ${videoUrl}\n`;
    mensaje += `📌 Falla Reportada: ${falla}\n`;
    mensaje += `✅ Solución Aplicada: ${solucion}\n`;
    mensaje += `📋 Estado: Reparación completada con éxito.\n`;
    mensaje += `¡Muchas gracias por confiar en nuestros servicios! Quedamos a tu disposición.`;

    const urlWhatsapp = `https://api.whatsapp.com/send?phone=${telefono}&text=${encodeURIComponent(mensaje)}`;
    window.open(urlWhatsapp, '_blank');
}
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
