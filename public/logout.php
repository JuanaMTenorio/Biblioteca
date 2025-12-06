<?php
// public/logout.php

require_once __DIR__ . '/../includes/init.php';

// Eliminamos todas las variables de sesión
session_unset();

// Destruimos la sesión
session_destroy();

// Redirigimos al login con un mensaje
header("Location: login.php?logout=1");
exit();
