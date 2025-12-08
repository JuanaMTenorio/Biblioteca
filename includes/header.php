<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Biblioteca DWES</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS de Bootstrap -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <!-- NAVBAR (estructura simple) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">

            <a class="navbar-brand" href="index.php">
                Biblioteca DWES
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            Inicio
                        </a>
                    </li>

                    <!-- Enlaces -->
                    <li class="nav-item">
                        <a class="nav-link" href="prestamos_listados.php">
                            Préstamos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="reservas_listado.php">
                            Reservas
                        </a>
                    </li>
                    <!-- Botón búsqueda -->
                    <li class="nav-item">
                        <a class="nav-link" href="libros_busqueda.php">Búsqueda</a>
                    </li>
                    <!-- Añado "Usuarios" solo para admin -->
                    <?php if (esAdmin()): ?> <!-- esAdmin está definido en init.php -->
                        <li class="nav-item">
                            <a class="nav-link" href="profesores_listado.php">
                                Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="log_listado.php">Log actividad</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <!-- Parte derecha del menú (login o usuario) -->
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION["IdProf"])): ?>
                        <li class="nav-item d-flex align-items-center">
                            <span class="navbar-text me-2">
                                Hola, <?php echo htmlspecialchars($_SESSION["Nombre"]); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="perfil.php">
                                Mi perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                Cerrar sesión
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                Login
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>

        </div>
    </nav>

    <!-- Container principal de la aplicación -->
    <div class="container">