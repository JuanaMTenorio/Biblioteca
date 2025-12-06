<?php
// public/reservas_alta.php

require_once __DIR__ . '/../includes/init.php';

// Cualquier usuario logueado puede reservar
requireLogin();

$errores   = array();
$mensajeOK = "";

// 1) Validar IdEjemplar por GET
if (!isset($_GET["IdEjemplar"]) || !ctype_digit($_GET["IdEjemplar"])) {
    header("Location: index.php");
    exit();
}

$idEjemplar = (int) $_GET["IdEjemplar"];
$idProf     = $_SESSION["IdProf"];

// 2) Cargar datos del libro y comprobar que está prestado
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
    } elseif ($libro["Estado"] !== "prestado") {
        $errores[] = "Solo se pueden reservar libros que están prestados.";
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos del libro: " . $e->getMessage();
}

// 3) Comprobar si ya hay una reserva pendiente de ESTE profesor para ESTE libro
if ($libro && count($errores) === 0) {
    try {
        $sql = "SELECT IdReserva
                FROM reserva
                WHERE IdEjemplar = :idEjemplar
                  AND IdProf     = :idProf
                  AND Estado IN ('pendiente','avisado')";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":idEjemplar", $idEjemplar, PDO::PARAM_INT);
        $stmt->bindParam(":idProf",     $idProf,     PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $errores[] = "Ya tienes una reserva activa para este libro.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error comprobando reservas previas: " . $e->getMessage();
    }
}

// Si hay errores antes de procesar el POST, mostramos y salimos
if (!$libro || (count($errores) > 0 && $_SERVER["REQUEST_METHOD"] !== "POST")) {
    require_once __DIR__ . '/../includes/header.php';
?>
    <h1 class="mb-4">Reservar libro</h1>
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

require_once __DIR__ . '/../includes/header.php';

// 4) Si llega POST, insertamos la reserva
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["confirmar"]) && $_POST["confirmar"] === "si") {

        try {
            $sqlInsert = "INSERT INTO reserva (IdEjemplar, IdProf)
                          VALUES (:idEjemplar, :idProf)";
            // Fecha y Estado se ponen con valores por defecto:
            // Fecha = CURRENT_TIMESTAMP, Estado = 'pendiente'

            $stmtInsert = $conexion->prepare($sqlInsert);
            $stmtInsert->bindParam(":idEjemplar", $idEjemplar, PDO::PARAM_INT);
            $stmtInsert->bindParam(":idProf",     $idProf,     PDO::PARAM_INT);
            $stmtInsert->execute();

            $mensajeOK = "Reserva registrada correctamente. 
                          Cuando el libro esté disponible, el personal de la biblioteca podrá avisarte.";
        } catch (PDOException $e) {
            $errores[] = "Error al registrar la reserva: " . $e->getMessage();
        }
    } else {
        // Si no confirma, volvemos al listado
        header("Location: index.php");
        exit();
    }
}
?>

<h1 class="mb-4">Reservar libro</h1>

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
        <?php echo nl2br(htmlspecialchars($mensajeOK)); ?>
    </div>

    <a href="index.php" class="btn btn-secondary mt-3">
        Volver al listado de libros
    </a>
    <a href="reservas_listado.php" class="btn btn-primary mt-3">
        Ver mis reservas
    </a>

<?php else: ?>

    <p class="mb-3">
        Vas a registrar una reserva para el siguiente libro:
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

    <form action="reservas_alta.php?IdEjemplar=<?php echo $idEjemplar; ?>" method="post">
        <input type="hidden" name="confirmar" value="si">

        <button type="submit" class="btn btn-warning">
            Confirmar reserva
        </button>

        <a href="index.php" class="btn btn-secondary">
            Cancelar
        </a>
    </form>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>