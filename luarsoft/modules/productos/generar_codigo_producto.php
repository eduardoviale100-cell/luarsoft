<?php
/**
 * modules/productos/generar_codigo_producto.php — NUEVO.
 * ------------------------------------------------------------------
 * Genera el SKU sugerido para un producto nuevo, con formato
 * CATEGORIA-PRODUCTO-NNN (ej. TEC-CAR-001 para categoría "Tecnologia"
 * y descripción "Cargador Universal..."), garantizando que el número
 * de orden no se repita para esa combinación de prefijos.
 * Se llama vía AJAX desde nuevo.php cada vez que el usuario selecciona
 * la categoría y ya escribió el nombre/descripción del producto.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$categoria   = trim($_GET['categoria'] ?? '');
$descripcion = trim($_GET['descripcion'] ?? '');

if ($categoria === '' || $descripcion === '') {
    echo json_encode(['success' => false, 'message' => 'Falta la categoría o la descripción del producto.']);
    exit;
}

$codigo = generarCodigoProductoSku($conexion, $categoria, $descripcion);

echo json_encode(['success' => true, 'codigo' => $codigo]);
