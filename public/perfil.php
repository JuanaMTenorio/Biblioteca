<?php
// public/perfil.php

require_once __DIR__ . '/../includes/init.php';

// Solo usuarios logueados
requireLogin();

// Id del usuario actual
$idProf = $_SESSION["IdProf"];

$errores   = array();
$mensajeOK = "";

// Si han enviado el formulario (POST), procesamos actualización
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre     = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido1  = isset($_POST["apellido1"]) ? trim($_POST["apellido1"]) : "";
    $apellido2  = isset($_POST["apellido2"]) ? trim($_POST["apellido2"]) : "";

    $password1  = isset($_POST["password1"]) ? trim($_POST["password1"]) : "";
    $password2  = isset($_POST["password2"]) ? trim($_POST["password2"]) : "";

    // Validaciones sencillas (estilo Tema 5)
    if ($nombre === "") {
        $errores[] = "El nombre no puede estar vacío.";
    }

    if ($apellido1 === "") {
        $errores[] = "El primer apellido no puede estar vacío.";
    }

    // Si han rellenado la contraseña, comprobamos confirmación
    $cambiarPassword = false;
    if ($password1 !== "" || $password2 !== "") {
        if ($password1 !== $password2) {
            $errores[] = "Las contraseñas no coinciden.";
        } else {
            $cambiarPassword = true;
        }
    }

    // Si no hay errores, actualizamos en BD
    if (count($errores) === 0) {
        try {
            if ($cambiarPassword) {
                // (de momento sin hash, igual que en tu login actual)
                $sql = "UPDATE profesor
                        SET Nombre = :nombre,
                            Apellido1 = :apellido1,
                            Apellido2 = :apellido2,
                            Password = :password
                        WHERE IdProf = :id";
            } else {
                $sql = "UPDATE profesor
                        SET Nombre = :nombre,
                            Apellido1 = :apellido1,
                            Apellido2 = :apellido2
                        WHERE IdProf = :id";
            }

            $consulta = $conexion->prepare($sql);
            $consulta->bindParam(":nombre", $nombre);
            $consulta->bindParam(":apellido1", $apellido1);
            $consulta->bindParam(":apellido2", $apellido2);
            $consulta->bindParam(":id", $idProf, PDO::PARAM_INT);

            if ($cambiarPassword) {
                $consulta->bindParam(":password", $password1);
            }

            $consulta->execute();

            $mensajeOK = "Datos actualizados correctamente.";

            // Actualizamos también los datos de sesión que mostramos en el navbar
            $_SESSION["Nombre"]    = $nombre;
            $_SESSION["Apellido1"] = $apellido1;
        } catch (PDOException $e) {
            $errores[] = "Error al actualizar los datos: " . $e->getMessage();
        }
    }
}

// Cargamos SIEMPRE los datos actuales de BD para mostrar en el formulario
try {
    $sql = "SELECT Nombre, Apellido1, Apellido2, Email
            FROM profesor
            WHERE IdProf = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->bindParam(":id", $idProf, PDO::PARAM_INT);
    $consulta->execute();

    $profesor = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$profesor) {
        // Algo raro: el usuario no existe
        $errores[] = "No se han encontrado los datos del usuario.";
    }
} catch (PDOException $e) {
    $errores[] = "Error al cargar los datos: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Mi perfil</h1>

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

<?php if ($profesor): ?>
    <div class="row">
        <div class="col-md-6">
            <form action="perfil.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input
                        type="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars($profesor["Email"]); ?>"
                        readonly>
                    <div class="form-text">
                        El correo no se puede modificar desde aquí.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        value="<?php echo htmlspecialchars($profesor["Nombre"]); ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="apellido1" class="form-label">Primer apellido</label>
                    <input
                        type="text"
                        name="apellido1"
                        id="apellido1"
                        class="form-control"
                        value="<?php echo htmlspecialchars($profesor["Apellido1"]); ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="apellido2" class="form-label">Segundo apellido</label>
                    <input
                        type="text"
                        name="apellido2"
                        id="apellido2"
                        class="form-control"
                        value="<?php echo htmlspecialchars($profesor["Apellido2"]); ?>">
                </div>

                <hr>

                <div class="mb-3">
                    <label for="password1" class="form-label">Nueva contraseña</label>
                    <input
                        type="password"
                        name="password1"
                        id="password1"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label for="password2" class="form-label">Confirmar contraseña</label>
                    <input
                        type="password"
                        name="password2"
                        id="password2"
                        class="form-control">
                    <div class="form-text">
                        Deja ambos campos en blanco si no quieres cambiar la contraseña.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>