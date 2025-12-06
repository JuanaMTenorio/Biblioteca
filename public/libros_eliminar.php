<?php
// public/libros_eliminar.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador
requireAdmin();

$errores   = array();
$mensajeOK = "";

// 1) Comprobamos el parámetro IdEjemplar
if (!isset($_GET["IdEjemplar"]) || !ctype_digit($_GET["IdEjemplar"])) {
    header("Location: index.php");
    exit();
}

$idEjemplar = (int) $_GET["IdEjemplar"];

// 2) Cargamos los datos del libro
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
        $errores[] = "No se ha encontrado el libro seleccionado.";
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos del libro: " . $e->getMessage();
}

// Si no existe el libro, mostramos error simple y salimos
if (!$libro) {
    require_once __DIR__ . '/../includes/header.php';
?>
    <h1 class="mb-4">Eliminar libro</h1>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($errores[0]); ?>
    </div>
    <a href="index.php" class="btn btn-secondary">Volver al listado</a>
<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// 3) Si llega POST, intentamos eliminar
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["confirmar"]) && $_POST["confirmar"] === "si") {

        try {
            $sql = "DELETE FROM libro WHERE IdEjemplar = :id";

            $consulta = $conexion->prepare($sql);
            $consulta->bindParam(":id", $idEjemplar, PDO::PARAM_INT);
            $consulta->execute();

            $mensajeOK = "Libro eliminado correctamente.";

            // Opcional: podríamos intentar eliminar la portada del disco
            // if (!empty($libro["Portada"])) { ... unlink(...) ... }

        } catch (PDOException $e) {
            // Si hay préstamos o reservas y la FK no deja borrar, caerá aquí
            $errores[] = "No se ha podido eliminar el libro. Es posible que tenga préstamos o reservas asociadas. Detalle técnico: " . $e->getMessage();
        }
    } else {
        // Si no confirma, volvemos al listado
        header("Location: index.php");
        exit();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Eliminar libro</h1>

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

    <a href="index.php" class="btn btn-secondary mt-3">
        Volver al listado
    </a>

<?php else: ?>

    <p class="mb-3">
        Vas a eliminar el siguiente libro. Esta operación no se puede deshacer.
        Confirma que deseas continuar.
    </p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <?php echo htmlspecialchars($libro["Titulo"]); ?>
            </h5>
            <h6 class="card-subtitle mb-2 text-muted">
                <?php echo htmlspecialchars($libro["Autor"]); ?>
            </h6>

            <p class="card-text">
                <strong>ISBN:</strong> <?php echo htmlspecialchars($libro["ISBN"]); ?><br>
                <strong>Año:</strong> <?php echo htmlspecialchars($libro["AnioPublicacion"]); ?><br>
                <strong>Editorial:</strong> <?php echo htmlspecialchars($libro["Editorial"]); ?><br>
                <strong>Estado:</strong> <?php echo htmlspecialchars($libro["Estado"]); ?><br>
                <strong>Precio:</strong>
                <?php
                if ($libro["Precio"] !== null) {
                    echo number_format($libro["Precio"], 2, ',', '.') . " €";
                } else {
                    echo "-";
                }
                ?>
            </p>

            <?php if (!empty($libro["Portada"])): ?>
                <img
                    src="<?php echo htmlspecialchars($libro["Portada"]); ?>"
                    alt="Portada"
                    class="img-thumbnail"
                    style="max-width: 150px; max-height: 200px;">
            <?php endif; ?>
        </div>
    </div>

    <form action="libros_eliminar.php?IdEjemplar=<?php echo $idEjemplar; ?>" method="post">
        <input type="hidden" name="confirmar" value="si">

        <button type="submit" class="btn btn-danger">
            Sí, eliminar este libro
        </button>

        <a href="index.php" class="btn btn-secondary">
            Cancelar y volver al listado
        </a>
    </form>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>