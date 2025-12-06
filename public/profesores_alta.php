<?php
// public/profesores_alta.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador
requireAdmin();

$errores   = array();
$mensajeOK = "";

$nombre     = "";
$apellido1  = "";
$apellido2  = "";
$email      = "";
$password1  = "";
$password2  = "";
$tipo       = "0"; // por defecto profesor normal
$estado     = "1"; // por defecto activo

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recogemos y saneamos
    $nombre     = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido1  = isset($_POST["apellido1"]) ? trim($_POST["apellido1"]) : "";
    $apellido2  = isset($_POST["apellido2"]) ? trim($_POST["apellido2"]) : "";
    $email      = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password1  = isset($_POST["password1"]) ? trim($_POST["password1"]) : "";
    $password2  = isset($_POST["password2"]) ? trim($_POST["password2"]) : "";
    $tipo       = isset($_POST["tipo"]) ? $_POST["tipo"] : "0";
    $estado     = isset($_POST["estado"]) ? $_POST["estado"] : "1";

    // Validaciones básicas
    if ($nombre === "") {
        $errores[] = "El nombre es obligatorio.";
    }

    if ($apellido1 === "") {
        $errores[] = "El primer apellido es obligatorio.";
    }

    if ($email === "") {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido.";
    }

    if ($password1 === "" || $password2 === "") {
        $errores[] = "La contraseña y su confirmación son obligatorias.";
    } elseif ($password1 !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    // Validamos el rol y estado
    if (!in_array($tipo, array("0", "1"))) {
        $errores[] = "El tipo de usuario no es válido.";
    }

    if (!in_array($estado, array("0", "1"))) {
        $errores[] = "El estado no es válido.";
    }

    // Comprobar si el email ya existe
    if (count($errores) === 0) {
        try {
            $sql = "SELECT IdProf FROM profesor WHERE Email = :email";
            $consulta = $conexion->prepare($sql);
            $consulta->bindParam(":email", $email);
            $consulta->execute();

            if ($consulta->rowCount() > 0) {
                $errores[] = "Ya existe un usuario registrado con ese email.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error comprobando el email: " . $e->getMessage();
        }
    }

    // Si sigue todo ok, hacemos el INSERT
    if (count($errores) === 0) {
        try {
            // IMPORTANTE: de momento guardamos la contraseña en texto plano
            // igual que espera tu login actual. Más adelante se puede cambiar a password_hash.
            $sql = "INSERT INTO profesor
                    (Apellido1, Apellido2, Nombre, Email, Password, TipoUsuario, Estado)
                    VALUES
                    (:apellido1, :apellido2, :nombre, :email, :password, :tipo, :estado)";

            $consulta = $conexion->prepare($sql);
            $consulta->bindParam(":apellido1", $apellido1);
            $consulta->bindParam(":apellido2", $apellido2);
            $consulta->bindParam(":nombre", $nombre);
            $consulta->bindParam(":email", $email);
            $consulta->bindParam(":password", $password1);
            $consulta->bindParam(":tipo", $tipo, PDO::PARAM_INT);
            $consulta->bindParam(":estado", $estado, PDO::PARAM_INT);

            $consulta->execute();

            $mensajeOK = "Profesor registrado correctamente.";

            // Limpiamos campos del formulario
            $nombre     = "";
            $apellido1  = "";
            $apellido2  = "";
            $email      = "";
            $password1  = "";
            $password2  = "";
            $tipo       = "0";
            $estado     = "1";
        } catch (PDOException $e) {
            $errores[] = "Error al insertar el profesor: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Nuevo profesor</h1>

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
    <div class="col-md-6">
        <form action="profesores_alta.php" method="post">

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    class="form-control"
                    value="<?php echo htmlspecialchars($nombre); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="apellido1" class="form-label">Primer apellido</label>
                <input
                    type="text"
                    name="apellido1"
                    id="apellido1"
                    class="form-control"
                    value="<?php echo htmlspecialchars($apellido1); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="apellido2" class="form-label">Segundo apellido</label>
                <input
                    type="text"
                    name="apellido2"
                    id="apellido2"
                    class="form-control"
                    value="<?php echo htmlspecialchars($apellido2); ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="password1" class="form-label">Contraseña</label>
                <input
                    type="password"
                    name="password1"
                    id="password1"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label for="password2" class="form-label">Confirmar contraseña</label>
                <input
                    type="password"
                    name="password2"
                    id="password2"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label for="tipo" class="form-label">Rol</label>
                <select name="tipo" id="tipo" class="form-select">
                    <option value="0" <?php if ($tipo == "0") echo "selected"; ?>>
                        Profesor
                    </option>
                    <option value="1" <?php if ($tipo == "1") echo "selected"; ?>>
                        Administrador
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="1" <?php if ($estado == "1") echo "selected"; ?>>
                        Activo
                    </option>
                    <option value="0" <?php if ($estado == "0") echo "selected"; ?>>
                        Desactivado
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Guardar profesor
            </button>

            <a href="profesores_listado.php" class="btn btn-secondary">
                Volver al listado
            </a>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>