<?php
/**
 * modules/ventas/guardar_venta.php — Reemplaza a "guardar_venta.php".
 * Lógica de negocio IDÉNTICA al sistema original (ya usaba
 * sentencias preparadas y transacciones): verifica stock, genera el
 * correlativo, inserta venta + detalle y descuenta stock de forma
 * atómica.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($conexion) || mysqli_connect_errno()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (empty($data) || !isset($data['productos']) || count($data['productos']) === 0) {
    echo json_encode(['success' => false, 'message' => 'Datos de venta o productos faltantes/inválidos.']);
    exit;
}

$tipo_documento = $data['tipo_documento'] ?? 'BOLETA';
$serie_documento = $data['serie_documento'] ?? 'B001';
$nombre_cliente = $data['nombre_cliente'] ?? 'Público General';
$documento_cliente = $data['documento_cliente'] ?? '99999999';
$direccion_cliente = $data['direccion_cliente'] ?? '';
$fecha = date('Y-m-d H:i:s');
$subtotal = floatval($data['subtotal_venta'] ?? 0);
$igv = floatval($data['igv_venta'] ?? 0);
$total_venta = floatval($data['total_venta'] ?? 0);
$metodo_pago = trim($data['metodo_pago'] ?? 'Efectivo');
if ($metodo_pago === '') { $metodo_pago = 'Efectivo'; }
$referencia_pago = trim($data['referencia_pago'] ?? '');
if ($referencia_pago === '') { $referencia_pago = null; }
$productos = $data['productos'];

/**
 * Calcula el siguiente número de una serie de forma atómica.
 * IMPORTANTE: debe llamarse DENTRO de una transacción ya abierta
 * (mysqli_begin_transaction). El UPDATE de la fila de la serie la deja
 * bloqueada hasta el commit/rollback, así que si dos ventas de la misma
 * serie se guardan al mismo tiempo, la segunda espera a que la primera
 * termine — nunca pueden calcular el mismo número (a diferencia del
 * antiguo SELECT MAX()+1, que si era vulnerable a esa carrera).
 */
function obtenerSiguienteCorrelativoAtomico($conexion, $serie)
{
    $stmt = mysqli_prepare($conexion, "INSERT INTO correlativos_documentos (serie, ultimo_numero) VALUES (?, 1)
        ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1");
    if (!$stmt) {
        throw new Exception("No se pudo calcular el número de comprobante (tabla correlativos_documentos). " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, "s", $serie);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($conexion, "SELECT ultimo_numero FROM correlativos_documentos WHERE serie = ?");
    if (!$stmt2) {
        throw new Exception("No se pudo leer el número de comprobante (tabla correlativos_documentos). " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt2, "s", $serie);
    mysqli_stmt_execute($stmt2);
    $fila = mysqli_stmt_get_result($stmt2)->fetch_assoc();
    mysqli_stmt_close($stmt2);

    $siguiente_numero_int = (int)$fila['ultimo_numero'];
    $siguiente_numero_formatted = str_pad($siguiente_numero_int, 5, "0", STR_PAD_LEFT);

    return [
        'siguiente_numero_formatted' => $siguiente_numero_formatted,
        'numero_documento_completo' => $serie . " - " . $siguiente_numero_formatted
    ];
}

/** Solo para el aviso informativo de "próximo número" tras guardar (no reserva nada). */
function obtenerSiguienteCorrelativoBD($conexion, $serie)
{
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_documento, ' - ', -1) AS UNSIGNED)) as max_num
            FROM ventas WHERE serie_documento = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $serie);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    $max_num = (isset($row['max_num']) && $row['max_num'] !== null) ? intval($row['max_num']) : 0;
    $siguiente_numero_int = $max_num + 1;
    $siguiente_numero_formatted = str_pad($siguiente_numero_int, 5, "0", STR_PAD_LEFT);

    return [
        'siguiente_numero_formatted' => $siguiente_numero_formatted,
        'numero_documento_completo' => $serie . " - " . $siguiente_numero_formatted
    ];
}

function verificar_stock_disponible($conexion, $productos)
{
    $sql_stock_check = "SELECT stock, descripcion FROM productos WHERE codigo = ?";
    $stmt = mysqli_prepare($conexion, $sql_stock_check);
    if (!$stmt) {
        return "Error al preparar la verificación de stock: " . mysqli_error($conexion);
    }

    foreach ($productos as $producto) {
        $codigo = $producto['codigo'] ?? '';
        $cant_solicitada = floatval($producto['cantidad'] ?? 0);

        if (empty($codigo) || $cant_solicitada <= 0) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($resultado);

        if (!$row) {
            mysqli_stmt_close($stmt);
            return "El producto con código {$codigo} no existe en el inventario.";
        }

        $stock_actual = floatval($row['stock']);
        $descripcion = $row['descripcion'];

        if ($cant_solicitada > $stock_actual) {
            mysqli_stmt_close($stmt);
            return "Stock insuficiente para '{$descripcion}'. Solicitas {$cant_solicitada}, solo hay {$stock_actual}.";
        }
    }

    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    return false;
}

$error_stock = verificar_stock_disponible($conexion, $productos);
if ($error_stock !== false) {
    echo json_encode(['success' => false, 'message' => $error_stock]);
    exit;
}

$correlativo_info = null;
$numero_documento_completo = null;

mysqli_begin_transaction($conexion);
$id_venta = null;

try {
    // El correlativo se calcula DENTRO de la transacción (bloqueo atómico
    // de la fila de la serie) para que dos ventas simultáneas nunca
    // puedan obtener el mismo número.
    $correlativo_info = obtenerSiguienteCorrelativoAtomico($conexion, $serie_documento);
    $numero_documento_completo = $correlativo_info['numero_documento_completo'];

    $sql_venta = "INSERT INTO ventas (
        tipo_documento, serie_documento, numero_documento,
        nombre_cliente, documento_cliente, direccion_cliente,
        fecha, subtotal, igv, total_venta, estado, metodo_pago, referencia_pago
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'REGISTRADA', ?, ?)";

    if ($stmt = mysqli_prepare($conexion, $sql_venta)) {
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssdddss",
            $tipo_documento,
            $serie_documento,
            $numero_documento_completo,
            $nombre_cliente,
            $documento_cliente,
            $direccion_cliente,
            $fecha,
            $subtotal,
            $igv,
            $total_venta,
            $metodo_pago,
            $referencia_pago
        );

        if (mysqli_stmt_execute($stmt)) {
            $id_venta = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error al insertar la venta principal: " . mysqli_error($conexion));
        }
    } else {
        throw new Exception("Error al preparar la sentencia de venta: " . mysqli_error($conexion));
    }

    if ($id_venta) {
        $sql_detalle = "INSERT INTO detalle_venta (id_venta, producto_codigo, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?, ?)";
        $sql_stock = "UPDATE productos SET stock = stock - ? WHERE codigo = ?";

        if ($stmt_detalle = mysqli_prepare($conexion, $sql_detalle)) {
            if ($stmt_stock = mysqli_prepare($conexion, $sql_stock)) {
                foreach ($productos as $producto) {
                    $codigo = $producto['codigo'] ?? '';
                    $desc = $producto['descripcion'] ?? '';
                    $cant = floatval($producto['cantidad'] ?? 0);
                    $precio_unitario_pvp = floatval($producto['precio_unitario'] ?? 0);
                    $importe = floatval($producto['importe'] ?? 0);

                    mysqli_stmt_bind_param($stmt_detalle, "issddd", $id_venta, $codigo, $desc, $cant, $precio_unitario_pvp, $importe);
                    if (!mysqli_stmt_execute($stmt_detalle)) {
                        throw new Exception("Error al insertar detalle para el producto {$desc}: " . mysqli_error($conexion));
                    }

                    mysqli_stmt_bind_param($stmt_stock, "ds", $cant, $codigo);
                    if (!mysqli_stmt_execute($stmt_stock)) {
                        throw new Exception("Error al actualizar stock para el producto {$desc}. Se cancela la venta.");
                    }
                }
                mysqli_stmt_close($stmt_stock);
            }
            mysqli_stmt_close($stmt_detalle);
        } else {
            throw new Exception("Error al preparar la sentencia de detalle: " . mysqli_error($conexion));
        }
    }

    mysqli_commit($conexion);

    $nueva_info = obtenerSiguienteCorrelativoBD($conexion, $serie_documento);

    echo json_encode([
        'success' => true,
        'id_venta' => $id_venta,
        'message' => 'Venta registrada exitosamente. Documento: ' . $numero_documento_completo,
        'nuevo_numero_documento' => $nueva_info['numero_documento_completo']
    ]);
} catch (\Throwable $e) {
    mysqli_rollback($conexion);
    error_log("Error de registro de venta: " . $e->getMessage());
    http_response_code(200);
    header('Content-Type: application/json');
    $mensaje = $e->getMessage();
    // Mensaje claro para el caso más común: falta correr la actualización
    // de base de datos (database/actualizar_bd.php) una sola vez.
    if (stripos($mensaje, 'correlativos_documentos') !== false || stripos($mensaje, "doesn't exist") !== false) {
        $mensaje = "Falta aplicar la actualización de base de datos. Pídele a un Administrador que entre una vez a database/actualizar_bd.php y presione 'Aplicar actualizaciones pendientes'.";
    }
    echo json_encode(['success' => false, 'message' => $mensaje]);
}

mysqli_close($conexion);
