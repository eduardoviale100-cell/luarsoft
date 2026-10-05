<?php
/**
 * includes/funciones.php
 * ------------------------------------------------------------------
 * Funciones auxiliares utilizadas en todo el sistema.
 */

/** Genera una URL absoluta a partir de la raíz del sistema (BASE_URL) preservando la empresa activa. */
function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    $fullUrl = $base . '/' . $path;

    // Preservar la empresa activa en TODOS los enlaces y redirecciones del ERP
    $slug = defined('TENANT_SLUG') ? TENANT_SLUG : ($_SESSION['tenant_slug'] ?? ($_SESSION['current_tenant_slug'] ?? 'eros'));
    if (!empty($slug) && $slug !== 'eros') {
        if (strpos($fullUrl, 'empresa=') === false) {
            $sep = (strpos($fullUrl, '?') !== false) ? '&' : '?';
            $fullUrl .= $sep . 'empresa=' . urlencode($slug);
        }
    }

    return $fullUrl;
}

/** Escapa una cadena para salida segura en HTML. */
function h($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Formatea un número como moneda en Soles (S/). */
function moneda($valor): string
{
    return 'S/ ' . number_format((float)$valor, 2);
}

/** Sanitiza una cadena de texto simple (trim + escape para BD ya se hace con prepared statements). */
function limpiar($valor): string
{
    return trim((string)($valor ?? ''));
}

/**
 * Guarda un mensaje flash en sesión para mostrarlo como notificación
 * (toast) en la siguiente carga de página, típicamente tras un
 * redirect (patrón Post-Redirect-Get).
 */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Recupera y limpia el mensaje flash pendiente (si existe). */
function obtenerFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Devuelve el nombre de usuario en sesión (o "Invitado" si no existe). */
function usuarioActual(): string
{
    return $_SESSION['usuario'] ?? 'Invitado';
}

/**
 * Sube una imagen de referencia (Productos, Clientes, Órdenes) a la
 * carpeta uploads/<subcarpeta>/ con validaciones de formato y tamaño.
 * Devuelve el nombre de archivo generado (para guardar en BD), o null
 * si no se envió ningún archivo. Lanza una excepción con un mensaje
 * legible si el archivo enviado no es válido, para que el módulo que
 * llama la muestre como error sin romper el registro/edición.
 */
function subirImagenReferencia(array $archivo, string $subcarpeta, string $prefijoNombre): ?string
{
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ocurrió un error al subir la imagen.');
    }

    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensionesHeic = ['heic', 'heif'];
    $tamanoMaximo = 5 * 1024 * 1024; // 5 MB

    if ($archivo['size'] > $tamanoMaximo) {
        throw new RuntimeException('La imagen supera el tamaño máximo permitido (5 MB).');
    }
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    $carpetaDestino = __DIR__ . '/../uploads/' . $subcarpeta . '/';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0755, true);
    }
    $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefijoNombre) . '_' . time();

    // Fotos tomadas con iPhone (formato HEIC/HEIF): se intenta convertir
    // automáticamente a JPG con Imagick, si el servidor la tiene disponible
    // con soporte HEIC. Si no se puede, se avisa con una solución concreta
    // en vez de un error críptico.
    if (in_array($extension, $extensionesHeic, true)) {
        $puedeConvertir = class_exists('Imagick') && in_array('HEIC', \Imagick::queryFormats('HEIC'), true);
        if ($puedeConvertir) {
            try {
                $img = new \Imagick($archivo['tmp_name']);
                $img->setImageFormat('jpeg');
                $img->setImageCompressionQuality(88);
                $nombreArchivo = $nombreBase . '.jpg';
                $img->writeImage($carpetaDestino . $nombreArchivo);
                $img->clear();
                return $nombreArchivo;
            } catch (\Throwable $e) {
                throw new RuntimeException('No se pudo convertir la foto HEIC de tu iPhone. Ve a Ajustes > Cámara > Formatos y elige "Más compatible", o compártela primero por WhatsApp (eso la convierte a JPG) y sube esa versión.');
            }
        }
        throw new RuntimeException('Esta foto está en formato HEIC (el que usa tu iPhone por defecto) y este servidor no puede convertirla. Ve a Ajustes > Cámara > Formatos en tu iPhone y elige "Más compatible", o comparte la foto por WhatsApp a ti mismo primero (eso la convierte a JPG automáticamente) y luego sube esa versión.');
    }

    if (!in_array($extension, $extensionesPermitidas, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
    }

    $nombreArchivo = $nombreBase . '.' . $extension;
    if (!move_uploaded_file($archivo['tmp_name'], $carpetaDestino . $nombreArchivo)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }

    return $nombreArchivo;
}

/** Elimina (si existe) una imagen previamente subida a uploads/<subcarpeta>/. */
function eliminarImagenReferencia(?string $nombreArchivo, string $subcarpeta): void
{
    if (!$nombreArchivo) { return; }
    $ruta = __DIR__ . '/../uploads/' . $subcarpeta . '/' . basename($nombreArchivo);
    if (is_file($ruta)) { @unlink($ruta); }
}

/* ==========================================================================
 * GENERADOR DE CÓDIGO DE PRODUCTO (SKU) — CATEGORIA-PRODUCTO-NNN
 * Usado por modules/productos/generar_codigo_producto.php (autorelleno en
 * el formulario de "Nuevo Producto") y por database/migrar_skus.php
 * (recodificación masiva de los productos ya existentes).
 * ========================================================================== */

/** Quita tildes/diéresis de una cadena, dejando solo caracteres base (para siglas ASCII limpias). */
function quitarTildes(string $texto): string
{
    $con = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü'];
    $sin = ['a','e','i','o','u','A','E','I','O','U','n','N','u','U'];
    return str_replace($con, $sin, $texto);
}

/** Palabras sin valor descriptivo, que se ignoran al elegir la palabra clave del producto. */
function palabrasVaciasSku(): array
{
    return ['de','del','la','el','los','las','un','una','unos','unas','y','o','u','en','con',
        'para','por','sin','al','a','the','of'];
}

/**
 * Abrevia una categoría a 3 letras mayúsculas para el prefijo del SKU.
 * Usa un mapa fijo para las categorías estándar del sistema y cae a las
 * primeras 3 letras (sin tildes) para cualquier categoría nueva/distinta.
 */
function abreviarCategoriaSku(string $categoria): string
{
    $mapa = [
        'Tecnologia' => 'TEC',
        'Repuestos'  => 'REP',
        'Accesorios' => 'ACC',
        'Servicios'  => 'SER',
        'Otros'      => 'OTR',
    ];
    if (isset($mapa[$categoria])) {
        return $mapa[$categoria];
    }
    $limpio = preg_replace('/[^A-Za-z0-9]/', '', quitarTildes($categoria));
    $limpio = strtoupper($limpio !== '' ? $limpio : 'GEN');
    return str_pad(substr($limpio, 0, 3), 3, 'X');
}

/**
 * Abrevia la descripción de un producto a 3 letras mayúsculas, tomando la
 * primera palabra con contenido real (ignora artículos/preposiciones). Ej:
 * "Cargador Universal para Laptop 65W" -> "CAR".
 */
function abreviarProductoSku(string $descripcion): string
{
    $vacias = palabrasVaciasSku();
    $palabras = preg_split('/\s+/', trim($descripcion)) ?: [];
    foreach ($palabras as $palabra) {
        $limpia = preg_replace('/[^A-Za-z0-9]/', '', quitarTildes($palabra));
        if ($limpia === '' || in_array(strtolower($limpia), $vacias, true)) {
            continue;
        }
        return str_pad(strtoupper(substr($limpia, 0, 3)), 3, 'X');
    }
    return 'GEN';
}

/**
 * Genera el siguiente SKU disponible con formato PREFIJO-PREFIJO-NNN
 * (ej. TEC-CAR-001) para la combinación categoría + descripción dada,
 * consultando la tabla `productos` para no repetir el número de orden.
 * $excluirCodigo permite ignorar el propio producto al recodificar uno
 * ya existente (migración).
 */
function generarCodigoProductoSku($conexion, string $categoria, string $descripcion, string $excluirCodigo = ''): string
{
    $prefijoCategoria = abreviarCategoriaSku($categoria);
    $prefijoProducto  = abreviarProductoSku($descripcion);
    $base = $prefijoCategoria . '-' . $prefijoProducto . '-';

    $sql = "SELECT codigo FROM productos WHERE codigo LIKE ?" . ($excluirCodigo !== '' ? " AND codigo <> ?" : '');
    $like = $base . '%';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($excluirCodigo !== '') {
        mysqli_stmt_bind_param($stmt, 'ss', $like, $excluirCodigo);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $like);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $maxNumero = 0;
    while ($fila = mysqli_fetch_assoc($resultado)) {
        if (preg_match('/-(\d+)$/', $fila['codigo'], $m)) {
            $maxNumero = max($maxNumero, (int)$m[1]);
        }
    }

    return $base . str_pad((string)($maxNumero + 1), 3, '0', STR_PAD_LEFT);
}
