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

            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                Biblioteca DWES
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                            Inicio
                        </a>
                    </li>

                    <!-- Más adelante activamos enlaces reales -->
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Libros
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Préstamos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Reservas
                        </a>
                    </li>
                </ul>

                <!-- Parte derecha del menú (login o usuario) -->
                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
                            Login
                        </a>
                    </li>

                </ul>
            </div>

        </div>
    </nav>

    <!-- Container principal de la aplicación -->
    <div class="container">