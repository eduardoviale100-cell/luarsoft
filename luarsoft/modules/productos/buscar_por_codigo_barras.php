<?php
/**
 * modules/productos/buscar_por_codigo_barras.php — NUEVO.
 * ------------------------------------------------------------------
 * Búsqueda EXACTA (no parcial) de un producto por su código de barras
 * o, si no lo encuentra ahí, por su código interno (SKU) — así también
 * funciona si alguien "escanea" tecleando el código interno a mano.
 * Usado por el POS al escanear con la cámara o con un lector físico
 * USB, para agregar el producto directo al carrito sin tener que
 * buscarlo por nombre.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Código vacío.']);
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT codigo, descripcion, precio_venta, stock FROM productos WHERE codigo_barras = ? OR codigo = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ss", $codigo, $codigo);
mysqli_stmt_execute($stmt);
$producto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$producto) {
    echo json_encode(['success' => false, 'message' => "No se encontró ningún producto con el código \"$codigo\"."]);
    exit;
}
if ((int)$producto['stock'] <= 0) {
    echo json_encode(['success' => false, 'message' => $producto['descripcion'] . ' no tiene stock disponible.']);
    exit;
}

echo json_encode([
    'success' => true,
    'codigo' => $producto['codigo'],
    'descripcion' => $producto['descripcion'],
    'precio' => (float)$producto['precio_venta'],
    'stock' => (int)$producto['stock'],
]);
