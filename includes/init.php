<?php
// includes/init.php

// Inicia la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga la conexión (siguiendo el estilo Tema 5)
require_once __DIR__ . '/../config/config.php';
