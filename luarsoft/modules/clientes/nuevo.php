<?php
/**
 * modules/clientes/nuevo.php
 * Reemplaza a "clientes.php" (formulario de registro de clientes).
 * Misma lógica de negocio (verificación de duplicados + inserción),
 * migrada a sentencias preparadas para evitar inyección SQL.
 */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isset($_POST['guardar'])) {
    $nombre           = limpiar($_POST['nombre']);
    $telefono         = limpiar($_POST['telefono']);
    $direccion        = limpiar($_POST['direccion']);
    $email            = limpiar($_POST['email']);
    $tipo_documento   = limpiar($_POST['tipo_documento']);
    $numero_documento = limpiar($_POST['numero_documento']);
    $ciudad           = limpiar($_POST['ciudad']);
    $region           = limpiar($_POST['region']);

    $stmt = mysqli_prepare($conexion, "SELECT id_cliente FROM clientes WHERE nombre = ? AND numero_documento = ?");
    mysqli_stmt_bind_param($stmt, "ss", $nombre, $numero_documento);
    mysqli_stmt_execute($stmt);
    $existe = mysqli_stmt_get_result($stmt);

    if ($existe && mysqli_num_rows($existe) > 0) {
        flash('warning', 'El cliente ya está registrado.');
    } else {
        $foto = null;
        try {
            $foto = subirImagenReferencia($_FILES['foto'] ?? [], 'clientes', $numero_documento ?: $nombre);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            header('Location: ' . url('modules/clientes/nuevo.php'));
            exit;
        }

        $ins = mysqli_prepare($conexion, "INSERT INTO clientes (nombre, telefono, direccion, email, tipo_documento, numero_documento, ciudad, region, foto) VALUES (?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($ins, "sssssssss", $nombre, $telefono, $direccion, $email, $tipo_documento, $numero_documento, $ciudad, $region, $foto);
        if (mysqli_stmt_execute($ins)) {
            flash('success', 'Cliente registrado exitosamente.');
        } else {
            flash('error', 'Error al registrar el cliente: ' . mysqli_error($conexion));
        }
    }
    header('Location: ' . url('modules/clientes/nuevo.php'));
    exit;
}

$page_title = 'Registrar Cliente';
$page_subtitle = 'Clientes · Nuevo registro';
$active_menu = 'clientes';
include __DIR__ . '/../../includes/layout_top.php';
?>

<div class="page-heading">
    <h1><i class="bi bi-plus-lg"></i> Registrar Nuevo Cliente</h1>
    <p>Completa los datos del cliente para agregarlo a la base de datos.</p>
</div>

<div class="card-erp">
    <div class="card-erp-header"><h3>Datos del cliente</h3></div>
    <div class="card-erp-body">
        <form method="POST" action="" id="formCliente" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Tipo de documento</label>
                    <select name="tipo_documento" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option value="DNI">DNI</option>
                        <option value="C.E.">C.E.</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Número de documento</label>
                    <input type="text" name="numero_documento" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Ciudad / Pueblo</label>
                    <input type="text" name="ciudad" class="form-control" required>
                </div>
                <div class="field-group">
                    <label>Región</label>
                    <input type="text" name="region" class="form-control" required>
                </div>
                <div class="field-group">
                    <label><i class="bi bi-image"></i> Foto de Referencia (opcional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                    <small class="text-muted">JPG, PNG, WEBP o HEIC (fotos de iPhone). Máximo 5 MB.</small>
                </div>
            </div>

            <div class="text-end mt-2">
                <button type="submit" name="guardar" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" id="btnLimpiar"><i class="bi bi-eraser"></i> Limpiar</button>
                <a href="<?= url('modules/clientes/listado.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Ver listado</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("btnLimpiar").addEventListener("click", () => {
    document.getElementById("formCliente").reset();
});

// Dictado Guiado por Voz: si se llegó aquí diciendo "nuevo cliente por voz",
// la URL trae ?dictado=1 y se arranca el asistente automáticamente. Se
// verifica dentro de "load" porque voz.js se carga después de este script.
window.addEventListener('load', function () {
    if (new URLSearchParams(window.location.search).get('dictado') !== '1' || !window.ERPDictado) return;

    ERPDictado.iniciar({
        titulo: "nuevo cliente",
        pasos: [
            { campo: "nombre", pregunta: "¿Cuál es el nombre completo del cliente?", tipo: "texto" },
            { campo: "telefono", pregunta: "¿Cuál es su número de teléfono?", tipo: "numero" },
            { campo: "direccion", pregunta: "¿Cuál es su dirección?", tipo: "texto" },
            { campo: "tipo_documento", pregunta: "¿Qué tipo de documento tiene? Di DNI, Carnet de Extranjería, Pasaporte, u Otro.", tipo: "opcion",
              opciones: [
                  { valor: "DNI", frases: ["dni"] },
                  { valor: "C.E.", frases: ["carnet de extranjeria", "cedula de extranjeria", "ce"] },
                  { valor: "Pasaporte", frases: ["pasaporte"] },
                  { valor: "Otro", frases: ["otro"] },
              ] },
            { campo: "numero_documento", pregunta: "¿Cuál es el número de ese documento?", tipo: "numero" },
            { campo: "ciudad", pregunta: "¿En qué ciudad o pueblo vive?", tipo: "texto" },
            { campo: "region", pregunta: "¿En qué región?", tipo: "texto" },
        ],
        onCompletar: function (r) {
            document.querySelector('[name="nombre"]').value = r.nombre || '';
            document.querySelector('[name="telefono"]').value = r.telefono || '';
            document.querySelector('[name="direccion"]').value = r.direccion || '';
            document.querySelector('[name="tipo_documento"]').value = r.tipo_documento || '';
            document.querySelector('[name="numero_documento"]').value = r.numero_documento || '';
            document.querySelector('[name="ciudad"]').value = r.ciudad || '';
            document.querySelector('[name="region"]').value = r.region || '';
            // El correo no se dicta (es poco confiable por voz); si el
            // formulario lo exige, el navegador enfocará ese campo solo
            // al intentar enviar, para completarlo a mano en 2 segundos.
            document.getElementById('formCliente').requestSubmit();
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/layout_bottom.php'; ?>
