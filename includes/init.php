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
