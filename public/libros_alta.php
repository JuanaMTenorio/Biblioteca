<?php
// public/libros_alta.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador puede dar de alta libros
requireAdmin();

$errores   = array();
$mensajeOK = "";

// Valores por defecto para “recordar” el formulario
$isbn            = "";
$titulo          = "";
$autor           = "";
$anio            = "";
$editorial       = "";
$precio          = "";
$descripcion     = "";
$estado          = "disponible"; // por defecto disponible

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recogemos datos del formulario
    $isbn        = isset($_POST["isbn"]) ? trim($_POST["isbn"]) : "";
    $titulo      = isset($_POST["titulo"]) ? trim($_POST["titulo"]) : "";
    $autor       = isset($_POST["autor"]) ? trim($_POST["autor"]) : "";
    $anio        = isset($_POST["anio"]) ? trim($_POST["anio"]) : "";
    $editorial   = isset($_POST["editorial"]) ? trim($_POST["editorial"]) : "";
    $precio      = isset($_POST["precio"]) ? trim($_POST["precio"]) : "";
    $descripcion = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";
    $estado      = isset($_POST["estado"]) ? $_POST["estado"] : "disponible";

    // ===========================
    // VALIDACIONES BÁSICAS
    // ===========================

    if ($isbn === "") {
        $errores[] = "El ISBN es obligatorio.";
    }

    if ($titulo === "") {
        $errores[] = "El título es obligatorio.";
    }

    if ($autor === "") {
        $errores[] = "El autor es obligatorio.";
    }

    // Año: opcional, pero si viene, comprobamos que es numérico de 4 dígitos
    if ($anio !== "") {
        if (!ctype_digit($anio) || strlen($anio) != 4) {
            $errores[] = "El año de publicación debe ser un número de 4 dígitos (por ejemplo, 2024).";
        }
    }

    // Precio: opcional, pero si viene, que sea número
    if ($precio !== "") {
        // Reemplazamos la coma por punto por si lo mete como 12,50
        $precio = str_replace(",", ".", $precio);
        if (!is_numeric($precio)) {
            $errores[] = "El precio debe ser un número válido.";
        }
    }

    // Estado: debe ser 'disponible' o 'prestado' (aunque normalmente crearás como disponible)
    if (!in_array($estado, array("disponible", "prestado"))) {
        $errores[] = "El estado no es válido.";
    }

    // ===========================
    // GESTIÓN DE LA PORTADA (UPLOAD)
    // ===========================

    $rutaPortada = null; // Por defecto, sin portada

    if (isset($_FILES["portada"]) && $_FILES["portada"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["portada"]["error"] === UPLOAD_ERR_OK) {

            $nombreTmp  = $_FILES["portada"]["tmp_name"];
            $nombreOrig = $_FILES["portada"]["name"];

            // Carpeta de destino (desde public/)
            $carpetaDestino = "uploads/portadas/";

            // Creamos nombre único para evitar colisiones
            $nombreLimpio = preg_replace("/[^A-Za-z0-9_\.-]/", "_", $nombreOrig);
            $nombreFinal  = uniqid("portada_") . "_" . $nombreLimpio;

            $rutaRelativa = $carpetaDestino . $nombreFinal;       // para guardar en BD
            $rutaFisica   = __DIR__ . "/" . $rutaRelativa;        // ruta real en disco

            // Intentamos mover el archivo subido
            if (!is_dir(__DIR__ . "/uploads")) {
                mkdir(__DIR__ . "/uploads");
            }
            if (!is_dir(__DIR__ . "/uploads/portadas")) {
                mkdir(__DIR__ . "/uploads/portadas");
            }

            if (move_uploaded_file($nombreTmp, $rutaFisica)) {
                $rutaPortada = $rutaRelativa;
            } else {
                $errores[] = "No se pudo guardar la imagen de la portada.";
            }
        } else {
            $errores[] = "Error al subir la portada (código " . $_FILES["portada"]["error"] . ").";
        }
    }

    // ===========================
    // INSERT EN BD SI NO HAY ERRORES
    // ===========================

    if (count($errores) === 0) {
        try {
            $sql = "INSERT INTO libro
                    (ISBN, Titulo, Autor, AnioPublicacion,
                     Editorial, Descripcion, Precio, Portada, Estado)
                    VALUES
                    (:isbn, :titulo, :autor, :anio,
                     :editorial, :descripcion, :precio, :portada, :estado)";

            $consulta = $conexion->prepare($sql);

            // Tratamos el año: si está vacío, pasamos NULL
            $anioParam = ($anio === "") ? null : $anio;

            // Precio: si está vacío, guardamos NULL
            $precioParam = ($precio === "") ? null : $precio;

            $consulta->bindParam(":isbn",        $isbn);
            $consulta->bindParam(":titulo",      $titulo);
            $consulta->bindParam(":autor",       $autor);
            $consulta->bindParam(":anio",        $anioParam, PDO::PARAM_INT);
            $consulta->bindParam(":editorial",   $editorial);
            $consulta->bindParam(":descripcion", $descripcion);
            $consulta->bindParam(":precio",      $precioParam);
            $consulta->bindParam(":portada",     $rutaPortada);
            $consulta->bindParam(":estado",      $estado);

            $consulta->execute();

            $mensajeOK = "Libro registrado correctamente.";

            // Limpiamos valores del formulario
            $isbn        = "";
            $titulo      = "";
            $autor       = "";
            $anio        = "";
            $editorial   = "";
            $precio      = "";
            $descripcion = "";
            $estado      = "disponible";
        } catch (PDOException $e) {
            $errores[] = "Error al insertar el libro: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Nuevo libro</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($mensajeOK !== ""): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($mensajeOK); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- enctype obligatorio para subir archivos -->
        <form action="libros_alta.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="isbn" class="form-label">ISBN</label>
                <input
                    type="text"
                    name="isbn"
                    id="isbn"
                    class="form-control"
                    value="<?php echo htmlspecialchars($isbn); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input
                    type="text"
                    name="titulo"
                    id="titulo"
                    class="form-control"
                    value="<?php echo htmlspecialchars($titulo); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="autor" class="form-label">Autor</label>
                <input
                    type="text"
                    name="autor"
                    id="autor"
                    class="form-control"
                    value="<?php echo htmlspecialchars($autor); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="anio" class="form-label">Año de publicación</label>
                <input
                    type="text"
                    name="anio"
                    id="anio"
                    class="form-control"
                    value="<?php echo htmlspecialchars($anio); ?>"
                    placeholder="Ej: 2024">
            </div>

            <div class="mb-3">
                <label for="editorial" class="form-label">Editorial</label>
                <input
                    type="text"
                    name="editorial"
                    id="editorial"
                    class="form-control"
                    value="<?php echo htmlspecialchars($editorial); ?>">
            </div>

            <div class="mb-3">
                <label for="precio" class="form-label">Precio (€)</label>
                <input
                    type="text"
                    name="precio"
                    id="precio"
                    class="form-control"
                    value="<?php echo htmlspecialchars($precio); ?>"
                    placeholder="Ej: 12.50">
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea
                    name="descripcion"
                    id="descripcion"
                    rows="4"
                    class="form-control"><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="portada" class="form-label">Portada (imagen)</label>
                <input
                    type="file"
                    name="portada"
                    id="portada"
                    class="form-control"
                    accept="image/*">
                <div class="form-text">
                    Opcional. Se guardará en la carpeta <code>uploads/portadas/</code>.
                </div>
            </div>

            <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="disponible" <?php if ($estado === "disponible") echo "selected"; ?>>
                        Disponible
                    </option>
                    <option value="prestado" <?php if ($estado === "prestado") echo "selected"; ?>>
                        Prestado
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Guardar libro
            </button>

            <a href="index.php" class="btn btn-secondary">
                Volver al listado
            </a>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>