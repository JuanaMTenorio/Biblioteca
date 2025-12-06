<?php
// Datos de conexión
$servidor   = "localhost";
$basedatos  = "bdbiblioteca";
$usuario    = "root";
$password   = "";   // En WAMP normalmente vacío

try {
    // Creamos la conexión PDO
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$basedatos;charset=utf8mb4",
        $usuario,
        $password
    );

    // Activamos los errores mediante excepciones
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Mensaje en caso de error
    echo "Error de conexión: " . $e->getMessage();
    exit();
}

// CONSTANTES DE RUTA UTILIZADAS EN LA APP
define("BASE_URL", "/biblioteca/public/");
