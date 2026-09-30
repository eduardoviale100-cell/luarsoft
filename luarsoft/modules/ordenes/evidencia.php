<?php
/**
 * modules/ordenes/evidencia.php — NUEVO (Mejora 1).
 * ------------------------------------------------------------------
 * Endpoint AJAX para el Modal de Evidencia en Video de una Orden de
 * Reparación:
 *   - accion=obtener (GET):  devuelve los datos de la orden (cliente,
 *     equipo/modelo, falla reportada, solución aplicada, video de
 *     evidencia ya cargado y teléfono para WhatsApp).
 *   - accion=guardar (POST): guarda la "Solución Aplicada" y, si se
 *     envía, sube un nuevo video de evidencia (reemplazando al
 *     anterior). Devuelve los mismos datos ya actualizados.
 *
 * No modifica ninguna otra tabla ni lógica del sistema: solo utiliza
 * las columnas nuevas agregadas de forma NO destructiva a `ordenes`
 * (ver database/migracion_v2.2_evidencia_video.sql).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

const CARPETA_EVIDENCIAS = __DIR__ . '/../../uploads/evidencias/';
const EXTENSIONES_VIDEO_PERMITIDAS = ['mp4', 'webm', 'ogg', 'mov', 'm4v'];
const TAMANO_MAXIMO_VIDEO = 100 * 1024 * 1024; // 100 MB

/** Busca el teléfono del cliente por coincidencia de nombre en la tabla `clientes`. */
function buscarTelefonoClientePorNombre($conexion, string $nombre): string
{
    if ($nombre === '') return '';
    $stmt = mysqli_prepare($conexion, "SELECT telefono FROM clientes WHERE nombre = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $nombre);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && ($fila = mysqli_fetch_assoc($res)) && !empty($fila['telefono'])) {
        return $fila['telefono'];
    }
    return '';
}

/** Arma la respuesta JSON estándar con los datos de una orden para el modal de evidencia. */
function respuestaOrden($conexion, array $orden): array
{
    $telefono = trim($orden['telefono_cliente'] ?? '');
    if ($telefono === '') {
        $telefono = buscarTelefonoClientePorNombre($conexion, $orden['cliente'] ?? '');
    }
    $videoUrl = !empty($orden['evidencia_video']) ? url('uploads/evidencias/' . $orden['evidencia_video']) : '';

    return [
        'success' => true,
        'id_orden' => (int)$orden['id_orden'],
        'cliente' => $orden['cliente'],
        'telefono' => $telefono,
        'equipo' => trim(($orden['marca'] ?? '') . ' ' . ($orden['modelo_impresora'] ?? '')),
        'falla' => $orden['problema'],
        'solucion_aplicada' => $orden['solucion_aplicada'] ?? '',
        'evidencia_video_url' => $videoUrl,
        'evidencia_video_nombre' => $orden['evidencia_video'] ?? '',
    ];
}

$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'obtener';

// ------------------------------------------------------------------
// OBTENER datos de la orden para poblar el modal
// ------------------------------------------------------------------
if ($accion === 'obtener') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido.']);
        exit;
    }

    $stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $orden = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$orden) {
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada.']);
        exit;
    }

    echo json_encode(respuestaOrden($conexion, $orden));
    exit;
}

// ------------------------------------------------------------------
// GUARDAR solución aplicada / teléfono / subir video de evidencia
// ------------------------------------------------------------------
if ($accion === 'guardar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $id = isset($_POST['id_orden']) ? (int)$_POST['id_orden'] : 0;
    $solucion = limpiar($_POST['solucion_aplicada'] ?? '');
    $telefono = limpiar($_POST['telefono'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido.']);
        exit;
    }

    $stmt = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $orden = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if (!$orden) {
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada.']);
        exit;
    }

    $nombreArchivoFinal = $orden['evidencia_video'] ?? null;

    // Subida de un nuevo video (opcional; si no se envía, se conserva el que ya existía)
    if (isset($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $archivo = $_FILES['video'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error al subir el video.']);
            exit;
        }
        if ($archivo['size'] > TAMANO_MAXIMO_VIDEO) {
            echo json_encode(['success' => false, 'message' => 'El video supera el tamaño máximo permitido (100 MB).']);
            exit;
        }
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, EXTENSIONES_VIDEO_PERMITIDAS, true)) {
            echo json_encode(['success' => false, 'message' => 'Formato de video no permitido. Usa MP4, WEBM, OGG o MOV.']);
            exit;
        }

        if (!is_dir(CARPETA_EVIDENCIAS)) {
            mkdir(CARPETA_EVIDENCIAS, 0755, true);
        }

        $nuevoNombre = 'orden_' . $id . '_' . time() . '.' . $extension;
        $rutaDestino = CARPETA_EVIDENCIAS . $nuevoNombre;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar el video en el servidor.']);
            exit;
        }

        // Borra el video anterior de esta orden (si existía) para no acumular archivos huérfanos
        if (!empty($orden['evidencia_video'])) {
            $anterior = CARPETA_EVIDENCIAS . basename($orden['evidencia_video']);
            if (is_file($anterior)) { @unlink($anterior); }
        }

        $nombreArchivoFinal = $nuevoNombre;
    }

    $upd = mysqli_prepare($conexion, "UPDATE ordenes SET solucion_aplicada = ?, telefono_cliente = ?, evidencia_video = ? WHERE id_orden = ?");
    mysqli_stmt_bind_param($upd, "sssi", $solucion, $telefono, $nombreArchivoFinal, $id);
    if (!mysqli_stmt_execute($upd)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la evidencia en la base de datos.']);
        exit;
    }

    // Releer la orden actualizada para responder con datos consistentes
    $stmt2 = mysqli_prepare($conexion, "SELECT * FROM ordenes WHERE id_orden = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    $ordenActualizada = mysqli_stmt_get_result($stmt2)->fetch_assoc();

    $respuesta = respuestaOrden($conexion, $ordenActualizada);
    $respuesta['message'] = 'Evidencia guardada correctamente.';
    echo json_encode($respuesta);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
