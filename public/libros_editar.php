<?php
// public/libros_editar.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador
requireAdmin();

$errores   = array();
$mensajeOK = "";

// Comprobamos que nos llega el IdEjemplar por GET
if (!isset($_GET["IdEjemplar"]) || !ctype_digit($_GET["IdEjemplar"])) {
    // Id inválido → volvemos al listado
    header("Location: index.php");
    exit();
}

$idEjemplar = (int) $_GET["IdEjemplar"];

// 1) Cargamos los datos del libro de la BD
try {
    $sql = "SELECT IdEjemplar, ISBN, Titulo, Autor, AnioPublicacion,
                   Editorial, Descripcion, Precio, Portada, Estado
            FROM libro
            WHERE IdEjemplar = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->bindParam(":id", $idEjemplar, PDO::PARAM_INT);
    $consulta->execute();

    $libro = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        // No existe un libro con ese ID
        $errores[] = "No se ha encontrado el libro solicitado.";
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos del libro: " . $e->getMessage();
}

// Si no se encontró libro, mostramos error y no procesamos POST
if (!$libro) {
    require_once __DIR__ . '/../includes/header.php';
?>
    <h1 class="mb-4">Editar libro</h1>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($errores[0]); ?>
    </div>
    <a href="index.php" class="btn btn-secondary">Volver al listado</a>
<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Variables para rellenar el formulario (usamos los datos de BD inicialmente)
$isbn        = $libro["ISBN"];
$titulo      = $libro["Titulo"];
$autor       = $libro["Autor"];
$anio        = $libro["AnioPublicacion"];
$editorial   = $libro["Editorial"];
$precio      = $libro["Precio"];
$descripcion = $libro["Descripcion"];
$estado      = $libro["Estado"];
$portadaBD   = $libro["Portada"]; // ruta actual de la portada

// 2) Si llega POST, procesamos la actualización
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recogemos los nuevos valores enviados por el formulario
    $isbn        = isset($_POST["isbn"]) ? trim($_POST["isbn"]) : "";
    $titulo      = isset($_POST["titulo"]) ? trim($_POST["titulo"]) : "";
    $autor       = isset($_POST["autor"]) ? trim($_POST["autor"]) : "";
    $anio        = isset($_POST["anio"]) ? trim($_POST["anio"]) : "";
    $editorial   = isset($_POST["editorial"]) ? trim($_POST["editorial"]) : "";
    $precio      = isset($_POST["precio"]) ? trim($_POST["precio"]) : "";
    $descripcion = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";
    $estado      = isset($_POST["estado"]) ? $_POST["estado"] : "disponible";

    // VALIDACIONES básicas (muy similares a alta)
    if ($isbn === "") {
        $errores[] = "El ISBN es obligatorio.";
    }

    if ($titulo === "") {
        $errores[] = "El título es obligatorio.";
    }

    if ($autor === "") {
        $errores[] = "El autor es obligatorio.";
    }

    if ($anio !== "") {
        if (!ctype_digit($anio) || strlen($anio) != 4) {
            $errores[] = "El año de publicación debe ser un número de 4 dígitos.";
        }
    }

    if ($precio !== "") {
        $precio = str_replace(",", ".", $precio);
        if (!is_numeric($precio)) {
            $errores[] = "El precio debe ser un número válido.";
        }
    }

    if (!in_array($estado, array("disponible", "prestado"))) {
        $errores[] = "El estado no es válido.";
    }

    // GESTIÓN de la portada (si se sube una nueva)
    $rutaPortada = $portadaBD; // Por defecto mantenemos la que ya tenía

    if (isset($_FILES["portada"]) && $_FILES["portada"]["error"]) {

        if ($_FILES["portada"]["error"] === UPLOAD_ERR_OK) {

            $nombreTmp  = $_FILES["portada"]["tmp_name"];
            $nombreOrig = $_FILES["portada"]["name"];

            $carpetaDestino = "uploads/portadas/";

            $nombreLimpio = preg_replace("/[^A-Za-z0-9_\.-]/", "_", $nombreOrig);
            $nombreFinal  = uniqid("portada_") . "_" . $nombreLimpio;

            $rutaRelativa = $carpetaDestino . $nombreFinal;
            $rutaFisica   = __DIR__ . "/" . $rutaRelativa;

            if (!is_dir(__DIR__ . "/uploads")) {
                mkdir(__DIR__ . "/uploads");
            }
            if (!is_dir(__DIR__ . "/uploads/portadas")) {
                mkdir(__DIR__ . "/uploads/portadas");
            }

            if (move_uploaded_file($nombreTmp, $rutaFisica)) {
                $rutaPortada = $rutaRelativa;
                // (Opcional: podríamos borrar la portada anterior del disco si existe)
            } else {
                $errores[] = "No se pudo guardar la nueva imagen de la portada.";
            }
        } else {
            $errores[] = "Error al subir la portada (código " . $_FILES["portada"]["error"] . ").";
        }
    }

    // Si no hay errores, hacemos el UPDATE
    if (count($errores) === 0) {
        try {
            $sql = "UPDATE libro
                    SET ISBN = :isbn,
                        Titulo = :titulo,
                        Autor = :autor,
                        AnioPublicacion = :anio,
                        Editorial = :editorial,
                        Descripcion = :descripcion,
                        Precio = :precio,
                        Portada = :portada,
                        Estado = :estado
                    WHERE IdEjemplar = :id";

            $consulta = $conexion->prepare($sql);

            $anioParam   = ($anio === "") ? null : $anio;
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
            $consulta->bindParam(":id",          $idEjemplar, PDO::PARAM_INT);

            $consulta->execute();

            $mensajeOK = "Datos del libro actualizados correctamente.";

            // Actualizamos la variable que usamos para mostrar la portada actual
            $portadaBD = $rutaPortada;
        } catch (PDOException $e) {
            $errores[] = "Error al actualizar el libro: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Editar libro</h1>

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

        <form action="libros_editar.php?IdEjemplar=<?php echo $idEjemplar; ?>" method="post" enctype="multipart/form-data">

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
                    value="<?php echo htmlspecialchars($precio); ?>">
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
                <label class="form-label">Portada actual</label><br>
                <?php if (!empty($portadaBD)): ?>
                    <img
                        src="<?php echo htmlspecialchars($portadaBD); ?>"
                        alt="Portada actual"
                        class="img-thumbnail mb-2"
                        style="max-width: 120px; max-height: 160px;">
                <?php else: ?>
                    <span class="text-muted">Sin portada</span>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="portada" class="form-label">Nueva portada (opcional)</label>
                <input
                    type="file"
                    name="portada"
                    id="portada"
                    class="form-control"
                    accept="image/*">
                <div class="form-text">
                    Si eliges una nueva imagen, se sustituirá la portada actual.
                    Si dejas este campo vacío, se mantiene la portada existente.
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
                Guardar cambios
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