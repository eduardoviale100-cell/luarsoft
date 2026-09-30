<?php
/**
 * modules/productos/buscar_autocomplete.php
 * Reemplaza a "buscar_productos_autocomplete.php". Usado por el POS.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($query === '') {
    echo json_encode([]);
    exit;
}

$patron = "%$query%";
$stmt = mysqli_prepare($conexion, "SELECT codigo, descripcion, precio_venta, stock FROM productos WHERE descripcion LIKE ? AND stock > 0 LIMIT 10");
mysqli_stmt_bind_param($stmt, "s", $patron);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$productos = [];
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $productos[] = [
            'codigo' => $fila['codigo'],
            'descripcion' => $fila['descripcion'],
            'precio' => (float)$fila['precio_venta'],
            'stock' => (int)$fila['stock'],
        ];
    }
}

echo json_encode($productos);
mysqli_close($conexion);
