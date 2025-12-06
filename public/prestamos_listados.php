<?php
// public/prestamos_listado.php

require_once __DIR__ . '/../includes/init.php';

// Cualquier usuario logueado puede ver esta página
requireLogin();

$errores   = array();
$prestamos = array();

try {
    if (esAdmin()) {
        // Admin: ve todos los préstamos
        $sql = "SELECT p.IdPrestamo, p.Fecha_inicio, p.Fecha_fin, p.Observaciones,
                       l.Titulo, l.Autor, l.ISBN,
                       pr.Nombre, pr.Apellido1, pr.Apellido2
                FROM prestamo p
                INNER JOIN libro l ON p.IdEjemplar = l.IdEjemplar
                INNER JOIN profesor pr ON p.IdProf = pr.IdProf
                ORDER BY p.Fecha_inicio DESC, p.IdPrestamo DESC";
        $consulta = $conexion->prepare($sql);
    } else {
        // Profesor: solo ve sus préstamos
        $sql = "SELECT p.IdPrestamo, p.Fecha_inicio, p.Fecha_fin, p.Observaciones,
                       l.Titulo, l.Autor, l.ISBN,
                       pr.Nombre, pr.Apellido1, pr.Apellido2
                FROM prestamo p
                INNER JOIN libro l ON p.IdEjemplar = l.IdEjemplar
                INNER JOIN profesor pr ON p.IdProf = pr.IdProf
                WHERE p.IdProf = :idProf
                ORDER BY p.Fecha_inicio DESC, p.IdPrestamo DESC";
        $consulta = $conexion->prepare($sql);
        $consulta->bindParam(":idProf", $_SESSION["IdProf"], PDO::PARAM_INT);
    }

    $consulta->execute();
    $prestamos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error al cargar el listado de préstamos: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Listado de préstamos</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($prestamos)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>Profesor</th>
                    <th>Fecha inicio</th>
                    <th>Fecha fin</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td><?php echo (int)$p["IdPrestamo"]; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($p["Titulo"]); ?></strong><br>
                            <span class="text-muted">
                                <?php echo htmlspecialchars($p["Autor"]); ?> (ISBN: <?php echo htmlspecialchars($p["ISBN"]); ?>)
                            </span>
                        </td>
                        <td>
                            <?php
                            echo htmlspecialchars(
                                $p["Apellido1"] . " " . $p["Apellido2"] . ", " . $p["Nombre"]
                            );
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($p["Fecha_inicio"]); ?></td>
                        <td>
                            <?php
                            echo ($p["Fecha_fin"] !== null)
                                ? htmlspecialchars($p["Fecha_fin"])
                                : "<span class=\"badge bg-warning text-dark\">En préstamo</span>";
                            ?>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($p["Observaciones"])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>No hay préstamos registrados.</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
