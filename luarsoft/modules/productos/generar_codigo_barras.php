<?php
/**
 * modules/productos/generar_codigo_barras.php — NUEVO.
 * ------------------------------------------------------------------
 * Genera un código de barras INTERNO (formato EAN-13, prefijo 200 —
 * el rango reservado por el estándar GS1 para uso interno/no-retail)
 * para productos que no traen uno de fábrica (ej. servicios, productos
 * genéricos). Devuelve un código que aún no existe en la tabla
 * `productos`, con dígito verificador válido.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

/** Calcula el dígito verificador EAN-13 de los primeros 12 dígitos. */
function digitoVerificadorEan13(string $doce): int
{
    $suma = 0;
    for ($i = 0; $i < 12; $i++) {
        $suma += (int)$doce[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (10 - ($suma % 10)) % 10;
}

$intentos = 0;
$codigoFinal = null;

while ($intentos < 20) {
    $intentos++;
    // Prefijo 200 (rango interno GS1) + 9 dígitos derivados del tiempo actual.
    $doce = '200' . str_pad((string)(time() % 1000000000) + $intentos, 9, '0', STR_PAD_LEFT);
    $doce = substr($doce, 0, 12);
    $candidato = $doce . digitoVerificadorEan13($doce);

    $stmt = mysqli_prepare($conexion, "SELECT 1 FROM productos WHERE codigo_barras = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $candidato);
    mysqli_stmt_execute($stmt);
    if (!mysqli_stmt_get_result($stmt)->fetch_assoc()) {
        $codigoFinal = $candidato;
        break;
    }
    usleep(1000); // pequeño respiro para que "time()" pueda variar en el próximo intento
}

if ($codigoFinal) {
    echo json_encode(['success' => true, 'codigo' => $codigoFinal]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo generar un código único, intenta nuevamente.']);
}
