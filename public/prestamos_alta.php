<?php
// public/prestamos_alta.php

require_once __DIR__ . '/../includes/init.php';

// Cualquier usuario logueado puede pedir préstamo
requireLogin();

$errores   = array();
$mensajeOK = "";

// Comprobamos el IdEjemplar
if (!isset($_GET["IdEjemplar"]) || !ctype_digit($_GET["IdEjemplar"])) {
    header("Location: index.php");
    exit();
}

$idEjemplar = (int) $_GET["IdEjemplar"];
$idProf     = $_SESSION["IdProf"]; // profesor que hace el préstamo

// 1) Cargamos datos del libro y comprobamos que está disponible
try {
    $sql = "SELECT IdEjemplar, ISBN, Titulo, Autor, AnioPublicacion,
                   Editorial, Estado
            FROM libro
            WHERE IdEjemplar = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->bindParam(":id", $idEjemplar, PDO::PARAM_INT);
    $consulta->execute();

    $libro = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        $errores[] = "No se ha encontrado el libro seleccionado.";
    } elseif ($libro["Estado"] !== "disponible") {
        $errores[] = "El libro no está disponible para préstamo.";
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos del libro: " . $e->getMessage();
}

// Si ya de entrada hay error gordo, mostramos y salimos
if (!$libro || count($errores) > 0 && $_SERVER["REQUEST_METHOD"] !== "POST") {
    require_once __DIR__ . '/../includes/header.php';
?>
    <h1 class="mb-4">Nuevo préstamo</h1>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err) {
            echo htmlspecialchars($err) . "<br>";
        } ?>
    </div>
    <a href="index.php" class="btn btn-secondary">Volver al listado</a>
<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Observaciones del formulario
$observaciones = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $observaciones = isset($_POST["observaciones"]) ? trim($_POST["observaciones"]) : "";

    // Solo intentamos el préstamo si no hay errores previos
    if (count($errores) === 0) {

        try {
            // Iniciamos una transacción: préstamo + cambio de estado del libro
            $conexion->beginTransaction();

            // Fecha de inicio = hoy
            $fechaInicio = date("Y-m-d");

            // Insertamos en la tabla prestamo
            $sqlInsert = "INSERT INTO prestamo
                          (IdEjemplar, IdProf, Fecha_inicio, Fecha_fin, Observaciones)
                          VALUES
                          (:idEjemplar, :idProf, :fecha_inicio, NULL, :observaciones)";

            $stmtInsert = $conexion->prepare($sqlInsert);
            $stmtInsert->bindParam(":idEjemplar",    $idEjemplar,    PDO::PARAM_INT);
            $stmtInsert->bindParam(":idProf",        $idProf,        PDO::PARAM_INT);
            $stmtInsert->bindParam(":fecha_inicio",  $fechaInicio);
            $stmtInsert->bindParam(":observaciones", $observaciones);
            $stmtInsert->execute();

            // Actualizamos el estado del libro a 'prestado'
            $sqlUpdate = "UPDATE libro
                          SET Estado = 'prestado'
                          WHERE IdEjemplar = :idEjemplar
                            AND Estado = 'disponible'";

            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bindParam(":idEjemplar", $idEjemplar, PDO::PARAM_INT);
            $stmtUpdate->execute();

            // Comprobamos que realmente se ha cambiado el estado (por si alguien lo cogió a la vez)
            if ($stmtUpdate->rowCount() === 0) {
                // No se ha actualizado, algo raro ha pasado
                $conexion->rollBack();
                $errores[] = "No se ha podido completar el préstamo. Es posible que el libro ya no esté disponible.";
            } else {
                // Todo OK
                $conexion->commit();
                $mensajeOK = "Préstamo registrado correctamente.";
            }
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $errores[] = "Error al registrar el préstamo: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Nuevo préstamo</h1>

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
        Volver al listado de libros
    </a>
    <a href="prestamos_listado.php" class="btn btn-primary mt-3">
        Ver mis préstamos
    </a>

<?php else: ?>

    <p class="mb-3">
        Vas a registrar un préstamo del siguiente libro:
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
                <strong>Estado actual:</strong> <?php echo htmlspecialchars($libro["Estado"]); ?>
            </p>
        </div>
    </div>

    <form action="prestamos_alta.php?IdEjemplar=<?php echo $idEjemplar; ?>" method="post">
        <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones (opcional)</label>
            <textarea
                name="observaciones"
                id="observaciones"
                rows="3"
                class="form-control"><?php echo htmlspecialchars($observaciones); ?></textarea>
        </div>

        <button type="submit" class="btn btn-success">
            Confirmar préstamo
        </button>

        <a href="index.php" class="btn btn-secondary">
            Cancelar
        </a>
    </form>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>