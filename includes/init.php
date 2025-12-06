<?php
// includes/init.php

// Inicia la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga la conexión (siguiendo el estilo Tema 5)
require_once __DIR__ . '/../config/config.php';


// FUNCIONES DE SEGURIDAD Y ROLES
/**
 * Devuelve true si hay un usuario logueado.
 */
function estaLogueado()
{
    return isset($_SESSION["IdProf"]);
}

/**
 * Obliga a estar logueado para continuar.
 * Si no hay usuario en sesión, redirige a login.php
 */
function requireLogin()
{
    if (!estaLogueado()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Devuelve true si el usuario logueado es administrador.
 * En nuestra BD: TipoUsuario = 1 → Admin
 *                TipoUsuario = 0 → Profesor normal
 */
function esAdmin()
{
    return isset($_SESSION["TipoUsuario"]) && $_SESSION["TipoUsuario"] == 1;
}

/**
 * Obliga a ser administrador para continuar.
 * Si no lo es, redirige a index.php
 */
function requireAdmin()
{
    if (!esAdmin()) {
        header("Location: index.php");
        exit();
    }
}


// LOG DE ACTIVIDAD (usa procedimiento almacenado)
/**
 * Registra una actividad en la tabla log_actividad
 * usando el procedimiento almacenado sp_insertar_log.
 *
 * @param string      $tipo       'visualizacion', 'alta', 'baja', 'actualizacion', 'login'
 * @param string      $tabla      Nombre de la tabla afectada (ej: 'libro', 'prestamo', 'profesor')
 * @param int|null    $idRegistro Id del registro afectado (nullable)
 * @param string|null $detalles   Texto adicional (nullable)
 */
function registrarLog($tipo, $tabla, $idRegistro = null, $detalles = null)
{
    // Usamos la conexión global creada en config.php
    global $conexion;

    // Si hay usuario en sesión, lo usamos. Si no, NULL (por ejemplo en un fallo de login)
    $idProf = isset($_SESSION["IdProf"]) ? $_SESSION["IdProf"] : null;

    try {
        $sql = "CALL sp_insertar_log(:tipo, :tabla, :idRegistro, :idProf, :detalles)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":tipo",       $tipo);
        $stmt->bindParam(":tabla",      $tabla);
        $stmt->bindParam(":idRegistro", $idRegistro, PDO::PARAM_INT);
        $stmt->bindParam(":idProf",     $idProf,     PDO::PARAM_INT);
        $stmt->bindParam(":detalles",   $detalles);
        $stmt->execute();
    } catch (PDOException $e) {
        // En un proyecto real podríamos registrar este error en un fichero.
        // Aquí, para la práctica, simplemente lo ignoramos para no romper la app.
        // echo "Error al registrar log: " . $e->getMessage();
    }
}
