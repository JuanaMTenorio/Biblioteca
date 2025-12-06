<?php
// public/prestamos_devolver.php

require_once __DIR__ . '/../includes/init.php';

// Cualquier usuario logueado puede devolver sus préstamos
requireLogin();

$errores   = array();
$mensajeOK = "";

// 1) Comprobar parámetro IdPrestamo
if (!isset($_GET["IdPrestamo"]) || !ctype_digit($_GET["IdPrestamo"])) {
    header("Location: prestamos_listado.php");
    exit();
}

$idPrestamo = (int) $_GET["IdPrestamo"];
$idUsuario  = $_SESSION["IdProf"];

// 2) Cargar datos del préstamo + libro + profesor
try {
    $sql = "SELECT p.IdPrestamo, p.IdEjemplar, p.IdProf, p.Fecha_inicio, p.Fecha_fin, p.Observaciones,
                   l.Titulo, l.Autor, l.ISBN, l.Estado AS EstadoLibro,
                   pr.Nombre, pr.Apellido1, pr.Apellido2
            FROM prestamo p
            INNER JOIN libro l    ON p.IdEjemplar = l.IdEjemplar
            INNER JOIN profesor pr ON p.IdProf = pr.IdProf
            WHERE p.IdPrestamo = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->bindParam(":id", $idPrestamo, PDO::PARAM_INT);
    $consulta->execute();

    $prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        $errores[] = "No se ha encontrado el préstamo solicitado.";
    } else {
        // Comprobar que el préstamo no está ya devuelto
        if ($prestamo["Fecha_fin"] !== null) {
            $errores[] = "Este préstamo ya está cerrado (devuelto).";
        }

        // Comprobar permisos: admin o dueño del préstamo
        if (!esAdmin() && $prestamo["IdProf"] != $idUsuario) {
            $errores[] = "No tienes permisos para devolver este préstamo.";
        }
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos del préstamo: " . $e->getMessage();
}

// Si hay errores gordos antes de procesar POST, mostramos y salimos
if (!$prestamo || (count($errores) > 0 && $_SERVER["REQUEST_METHOD"] !== "POST")) {
    require_once __DIR__ . '/../includes/header.php';
?>
    <h1 class="mb-4">Devolver libro</h1>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err) {
            echo htmlspecialchars($err) . "<br>";
        } ?>
    </div>
    <a href="prestamos_listado.php" class="btn btn-secondary">Volver al listado de préstamos</a>
<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// 3) Si llega POST, realizar la devolución
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["confirmar"]) && $_POST["confirmar"] === "si") {

        try {
            // Iniciamos transacción: actualizar préstamo + libro
            $conexion->beginTransaction();

            $fechaFin = date("Y-m-d");

            // Actualizar el préstamo: poner fecha_fin
            $sqlUpdatePrestamo = "UPDATE prestamo
                                  SET Fecha_fin = :fechaFin
                                  WHERE IdPrestamo = :idPrestamo
                                    AND Fecha_fin IS NULL";

            $stmtPrestamo = $conexion->prepare($sqlUpdatePrestamo);
            $stmtPrestamo->bindParam(":fechaFin",    $fechaFin);
            $stmtPrestamo->bindParam(":idPrestamo", $idPrestamo, PDO::PARAM_INT);
            $stmtPrestamo->execute();

            if ($stmtPrestamo->rowCount() === 0) {
                // No se ha actualizado nada: ya estaba devuelto o ha habido problema
                $conexion->rollBack();
                $errores[] = "No se ha podido registrar la devolución. Es posible que el préstamo ya estuviera devuelto.";
            } else {
                // Actualizar el estado del libro a 'disponible'
                $sqlUpdateLibro = "UPDATE libro
                                   SET Estado = 'disponible'
                                   WHERE IdEjemplar = :idEjemplar";

                $stmtLibro = $conexion->prepare($sqlUpdateLibro);
                $stmtLibro->bindParam(":idEjemplar", $prestamo["IdEjemplar"], PDO::PARAM_INT);
                $stmtLibro->execute();

                $conexion->commit();
                $mensajeOK = "Devolución registrada correctamente.";
            }
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $errores[] = "Error al registrar la devolución: " . $e->getMessage();
        }
    } else {
        // Si no confirma, volvemos al listado
        header("Location: prestamos_listado.php");
        exit();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Devolver libro</h1>

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

    <a href="prestamos_listado.php" class="btn btn-primary mt-3">
        Volver al listado de préstamos
    </a>
    <a href="index.php" class="btn btn-secondary mt-3">
        Volver al listado de libros
    </a>

<?php else: ?>

    <p class="mb-3">
        Vas a registrar la devolución del siguiente préstamo:
    </p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <?php echo htmlspecialchars($prestamo["Titulo"]); ?>
            </h5>
            <h6 class="card-subtitle mb-2 text-muted">
                <?php echo htmlspecialchars($prestamo["Autor"]); ?>
            </h6>
            <p class="card-text">
                <strong>Profesor:</strong>
                <?php
                echo htmlspecialchars(
                    $prestamo["Apellido1"] . " " . $prestamo["Apellido2"] . ", " . $prestamo["Nombre"]
                );
                ?><br>
                <strong>Fecha inicio:</strong> <?php echo htmlspecialchars($prestamo["Fecha_inicio"]); ?><br>
                <strong>Estado del libro:</strong> <?php echo htmlspecialchars($prestamo["EstadoLibro"]); ?>
            </p>
        </div>
    </div>

    <form action="prestamos_devolver.php?IdPrestamo=<?php echo $idPrestamo; ?>" method="post">
        <input type="hidden" name="confirmar" value="si">

        <button type="submit" class="btn btn-success">
            Confirmar devolución
        </button>

        <a href="prestamos_listado.php" class="btn btn-secondary">
            Cancelar
        </a>
    </form>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>