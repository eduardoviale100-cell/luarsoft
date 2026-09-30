<?php
/**
 * modules/clientes/buscar_doc.php — Reemplaza a "buscar_cliente_doc.php".
 * Endpoint JSON usado por el POS para autocompletar datos del cliente
 * a partir de su número de documento. Migrado a sentencia preparada.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$documento = isset($_GET['documento']) ? trim($_GET['documento']) : '';

if ($documento === '' || $documento === '99999999' || $documento === '0') {
    echo json_encode([
        'error' => false,
        'nombre' => 'Público General',
        'numero_documento' => '99999999',
        'direccion' => ''
    ]);
    exit;
}

$stmt = mysqli_prepare($conexion, "SELECT nombre, numero_documento, direccion FROM clientes WHERE numero_documento = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $documento);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $cliente = mysqli_fetch_assoc($resultado);
    $respuesta = [
        'error' => false,
        'nombre' => $cliente['nombre'],
        'numero_documento' => $cliente['numero_documento'],
        'direccion' => $cliente['direccion']
    ];
} else {
    $respuesta = ['error' => true, 'mensaje' => 'Cliente no encontrado'];
}

echo json_encode($respuesta);
mysqli_close($conexion);
