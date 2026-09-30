<?php
/**
 * modules/ordenes/eliminar_repuesto.php
 * ------------------------------------------------------------------
 * Endpoint AJAX: quita un repuesto previamente asignado a una orden
 * y devuelve la cantidad correspondiente al stock del producto.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$id_detalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_detalle <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de repuesto no válido.']);
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT producto_codigo, cantidad FROM orden_repuestos WHERE id_detalle = ?");
mysqli_stmt_bind_param($stmt, "i", $id_detalle);
mysqli_stmt_execute($stmt);
$fila = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$fila) {
    echo json_encode(['success' => false, 'message' => 'El repuesto indicado no existe.']);
    exit;
}

mysqli_begin_transaction($conexion);
try {
    $del = mysqli_prepare($conexion, "DELETE FROM orden_repuestos WHERE id_detalle = ?");
    mysqli_stmt_bind_param($del, "i", $id_detalle);
    if (!mysqli_stmt_execute($del)) {
        throw new Exception('No se pudo quitar el repuesto de la orden.');
    }

    $upd = mysqli_prepare($conexion, "UPDATE productos SET stock = stock + ? WHERE codigo = ?");
    mysqli_stmt_bind_param($upd, "ds", $fila['cantidad'], $fila['producto_codigo']);
    mysqli_stmt_execute($upd);

    mysqli_commit($conexion);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
