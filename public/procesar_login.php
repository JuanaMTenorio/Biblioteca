<?php
// public/procesar_login.php

require_once __DIR__ . '/../includes/init.php';

// Comprobamos que viene por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

// Recogemos datos del formulario
$email    = isset($_POST['email']) ? trim($_POST['email']) : "";
$password = isset($_POST['password']) ? trim($_POST['password']) : "";

// Validación sencilla de campos vacíos
if ($email == "" || $password == "") {
    // Podríamos llevar un código de error distinto para “campos vacíos”
    header("Location: login.php?error=1");
    exit();
}

try {
    // Consulta preparada: buscamos usuario activo con ese email
    $sql = "SELECT IdProf, Nombre, Apellido1, TipoUsuario, Password
            FROM profesor
            WHERE Email = :email
              AND Estado = 1";

    $consulta = $conexion->prepare($sql);
    $consulta->bindParam(":email", $email);
    $consulta->execute();

    // Comprobamos si existe exactamente un usuario con ese email
    if ($consulta->rowCount() == 1) {
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        // Comprobamos la contraseña (texto plano)
        if ($fila["Password"] === $password) {

            // Guardamos datos en la sesión
            $_SESSION["IdProf"]      = $fila["IdProf"];
            $_SESSION["Nombre"]      = $fila["Nombre"];
            $_SESSION["Apellido1"]   = $fila["Apellido1"];
            $_SESSION["TipoUsuario"] = $fila["TipoUsuario"]; // 1 = admin, 0 = profesor

            // Redirigimos a la página principal
            header("Location: index.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // No hay usuario con ese email (o está desactivado)
        header("Location: login.php?error=1");
        exit();
    }
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
    exit();
}
