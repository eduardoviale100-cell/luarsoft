<?php
/**
 * database/actualizar_bd.php — NUEVO, herramienta de uso único (idempotente).
 * ------------------------------------------------------------------
 * Aplica los cambios de base de datos que necesitan las funciones nuevas:
 *
 *  1) Tabla `correlativos_documentos`: guarda el último número emitido por
 *     cada serie (B001, F001...). Antes, el "siguiente número" se calculaba
 *     con SELECT MAX(...)+1, lo que en teoría permitía que dos ventas
 *     simultáneas de la misma serie calcularan el mismo número. Con esta
 *     tabla, el número se obtiene con un UPDATE atómico dentro de la misma
 *     transacción de la venta, así que dos ventas al mismo tiempo quedan
 *     en fila (una espera a la otra) y nunca pueden repetir número.
 *     Se inicializa con el último número que ya tengas emitido, así que
 *     la numeración sigue exactamente donde se quedó (no reinicia nada).
 *
 *  2) Restricción UNIQUE en ventas(serie_documento, numero_documento):
 *     red de seguridad adicional — aunque el punto 1 ya lo evita, esto
 *     hace físicamente imposible guardar dos comprobantes duplicados.
 *
 *  3) Columna `referencia_pago` en `ventas`: para anotar el N° de
 *     operación de Yape (o cualquier otro pago) y que quede guardado con
 *     la venta e impreso en el comprobante.
 *
 * Es seguro volver a ejecutar esta página las veces que sea necesario:
 * cada paso primero revisa si ya está aplicado y solo actúa si falta.
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';

if (!esAdministrador()) {
    flash('error', 'Solo un Administrador puede aplicar actualizaciones de base de datos.');
    header('Location: ' . url('index.php'));
    exit;
}

$reporte = [];

function columnaExiste($conexion, string $tabla, string $columna): bool
{
    $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    mysqli_stmt_bind_param($stmt, "ss", $tabla, $columna);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_stmt_get_result($stmt)->fetch_assoc();
    return ((int)$fila['c']) > 0;
}

function tablaExiste($conexion, string $tabla): bool
{
    $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    mysqli_stmt_bind_param($stmt, "s", $tabla);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_stmt_get_result($stmt)->fetch_assoc();
    return ((int)$fila['c']) > 0;
}

function indiceExiste($conexion, string $tabla, string $indice): bool
{
    $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    mysqli_stmt_bind_param($stmt, "ss", $tabla, $indice);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_stmt_get_result($stmt)->fetch_assoc();
    return ((int)$fila['c']) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar'])) {

    // --- 1) Tabla de correlativos atómicos ---
    if (!tablaExiste($conexion, 'correlativos_documentos')) {
        mysqli_query($conexion, "CREATE TABLE correlativos_documentos (
            serie VARCHAR(5) NOT NULL PRIMARY KEY,
            ultimo_numero INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Se inicializa cada serie con el número más alto que ya tengas
        // emitido, para que la numeración continúe sin saltos ni reinicios.
        $series = mysqli_query($conexion, "SELECT DISTINCT serie_documento FROM ventas");
        while ($fila = mysqli_fetch_assoc($series)) {
            $serie = $fila['serie_documento'];
            $stmtMax = mysqli_prepare($conexion, "SELECT MAX(CAST(SUBSTRING_INDEX(numero_documento, ' - ', -1) AS UNSIGNED)) AS max_num FROM ventas WHERE serie_documento = ?");
            mysqli_stmt_bind_param($stmtMax, "s", $serie);
            mysqli_stmt_execute($stmtMax);
            $maxNum = (int)(mysqli_stmt_get_result($stmtMax)->fetch_assoc()['max_num'] ?? 0);

            $stmtIns = mysqli_prepare($conexion, "INSERT INTO correlativos_documentos (serie, ultimo_numero) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmtIns, "si", $serie, $maxNum);
            mysqli_stmt_execute($stmtIns);
        }
        $reporte[] = "✔ Tabla 'correlativos_documentos' creada e inicializada con tus números actuales.";
    } else {
        $reporte[] = "— La tabla 'correlativos_documentos' ya existía, no se tocó.";
    }

    // --- 2) Restricción UNIQUE contra duplicados ---
    if (!indiceExiste($conexion, 'ventas', 'uq_serie_numero')) {
        $ok = mysqli_query($conexion, "ALTER TABLE ventas ADD UNIQUE KEY uq_serie_numero (serie_documento, numero_documento)");
        $reporte[] = $ok
            ? "✔ Restricción única agregada: ya no se puede guardar un número de boleta/factura repetido."
            : "✘ No se pudo agregar la restricción única: " . mysqli_error($conexion) . " (si el mensaje dice 'Duplicate entry', significa que ya existen números repetidos en tu tabla ventas — avísame para revisarlo antes de continuar).";
    } else {
        $reporte[] = "— La restricción única de numeración ya existía, no se tocó.";
    }

    // --- 3) Columna para el N° de operación (Yape u otro medio de pago) ---
    if (!columnaExiste($conexion, 'ventas', 'referencia_pago')) {
        mysqli_query($conexion, "ALTER TABLE ventas ADD COLUMN referencia_pago VARCHAR(50) NULL AFTER metodo_pago");
        $reporte[] = "✔ Columna 'referencia_pago' agregada a ventas (para el N° de operación de Yape).";
    } else {
        $reporte[] = "— La columna 'referencia_pago' ya existía, no se tocó.";
    }
}

$page_title = 'Actualizar Base de Datos';
$page_subtitle = 'Herramienta única · Numeración a prueba de duplicados + pago con Yape';
include __DIR__ . '/../includes/layout_top.php';
?>
<div class="page-heading">
    <h1><i class="bi bi-database-gear"></i> Actualizar Base de Datos</h1>
    <p>Prepara la base de datos para la numeración de comprobantes a prueba de duplicados y el pago con QR de Yape. Se puede ejecutar más de una vez sin riesgo: solo aplica lo que falte.</p>
</div>

<?php if (!empty($reporte)): ?>
    <div class="card-erp mb-3">
        <div class="card-erp-header"><h2><i class="bi bi-clipboard-check"></i> Resultado</h2></div>
        <div class="card-erp-body">
            <ul class="mb-0">
                <?php foreach ($reporte as $linea): ?>
                    <li><?= h($linea) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card-erp">
    <div class="card-erp-body">
        <form method="POST">
            <button type="submit" name="aplicar" value="1" class="btn btn-primary"><i class="bi bi-play-fill"></i> Aplicar actualizaciones pendientes</button>
            <a href="<?= url('modules/ventas/pos.php') ?>" class="btn btn-secondary">Ir al Punto de Venta</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
