<?php
// public/login.php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Acceso a la Biblioteca</h1>

<?php
// Mostrar mensajes de error o información
if (isset($_GET['error']) && $_GET['error'] == 1) {
    echo '<div class="alert alert-danger">Usuario o contraseña incorrectos.</div>';
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    echo '<div class="alert alert-info">Has cerrado la sesión correctamente.</div>';
}
?>

<div class="row">
    <div class="col-md-4">
        <form action="procesar_login.php" method="post">

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-control"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-control"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Entrar
            </button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
