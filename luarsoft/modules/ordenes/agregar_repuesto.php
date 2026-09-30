<?php
/**
 * modules/ordenes/agregar_repuesto.php
 * ------------------------------------------------------------------
 * Endpoint AJAX: asigna un repuesto del inventario a una orden de
 * reparación, descontando el stock del producto de forma atómica
 * (misma lógica de verificación/transacción usada en guardar_venta.php).
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_orden = isset($data['id_orden']) ? (int)$data['id_orden'] : 0;
$codigo = trim($data['codigo'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$cantidad = isset($data['cantidad']) ? (float)$data['cantidad'] : 0;
$precio_unitario = isset($data['precio_unitario']) ? (float)$data['precio_unitario'] : 0;

if ($id_orden <= 0 || $codigo === '' || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos para asignar el repuesto.']);
    exit;
}

// Verificar que la orden exista
$chk = mysqli_prepare($conexion, "SELECT id_orden FROM ordenes WHERE id_orden = ?");
mysqli_stmt_bind_param($chk, "i", $id_orden);
mysqli_stmt_execute($chk);
if (mysqli_stmt_get_result($chk)->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'La orden indicada no existe.']);
    exit;
}

// Verificar stock disponible
$stmtStock = mysqli_prepare($conexion, "SELECT stock, descripcion FROM productos WHERE codigo = ?");
mysqli_stmt_bind_param($stmtStock, "s", $codigo);
mysqli_stmt_execute($stmtStock);
$prod = mysqli_stmt_get_result($stmtStock)->fetch_assoc();

if (!$prod) {
    echo json_encode(['success' => false, 'message' => 'El producto/repuesto no existe en el inventario.']);
    exit;
}
if ($cantidad > (float)$prod['stock']) {
    echo json_encode(['success' => false, 'message' => "Stock insuficiente de '{$prod['descripcion']}'. Disponible: {$prod['stock']}."]);
    exit;
}
if ($descripcion === '') {
    $descripcion = $prod['descripcion'];
}

$importe = round($cantidad * $precio_unitario, 2);

mysqli_begin_transaction($conexion);
try {
    $ins = mysqli_prepare($conexion, "INSERT INTO orden_repuestos (id_orden, producto_codigo, descripcion, cantidad, precio_unitario, importe) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($ins, "issddd", $id_orden, $codigo, $descripcion, $cantidad, $precio_unitario, $importe);
    if (!mysqli_stmt_execute($ins)) {
        throw new Exception('No se pudo registrar el repuesto en la orden.');
    }

    $upd = mysqli_prepare($conexion, "UPDATE productos SET stock = stock - ? WHERE codigo = ?");
    mysqli_stmt_bind_param($upd, "ds", $cantidad, $codigo);
    if (!mysqli_stmt_execute($upd)) {
        throw new Exception('No se pudo descontar el stock del repuesto.');
    }

    mysqli_commit($conexion);
    echo json_encode(['success' => true, 'message' => 'Repuesto asignado correctamente.']);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
