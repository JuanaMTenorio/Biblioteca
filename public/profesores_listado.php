<?php
// public/profesores_listado.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador
requireAdmin();

$errores = array();

try {
    $sql = "SELECT IdProf, Nombre, Apellido1, Apellido2, Email, TipoUsuario, Estado
            FROM profesor
            ORDER BY Apellido1, Nombre";

    $consulta = $conexion->prepare($sql);
    $consulta->execute();

    $profesores = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al cargar la lista de profesores: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Gestión de usuarios (Profesores)</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mb-3">
    <a href="profesores_alta.php" class="btn btn-success">
        Nuevo profesor
    </a>
</div>

<?php if (!empty($profesores)): ?>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Operaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($profesores as $prof): ?>
                <tr>
                    <td><?php echo (int)$prof["IdProf"]; ?></td>
                    <td>
                        <?php
                        echo htmlspecialchars($prof["Apellido1"] . " " . $prof["Apellido2"] . ", " . $prof["Nombre"]);
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($prof["Email"]); ?></td>
                    <td>
                        <?php echo ($prof["TipoUsuario"] == 1) ? "Administrador" : "Profesor"; ?>
                    </td>
                    <td>
                        <?php echo ($prof["Estado"] == 1) ? "Activo" : "Desactivado"; ?>
                    </td>
                    <td>
                        <!-- Más adelante: editar / desactivar -->
                        <a href="#" class="btn btn-sm btn-primary disabled">Editar</a>
                        <a href="#" class="btn btn-sm btn-warning disabled">Desactivar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay profesores registrados.</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>