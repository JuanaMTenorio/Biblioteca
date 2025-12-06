<?php
// public/reservas_listado.php

require_once __DIR__ . '/../includes/init.php';

// Cualquier usuario logueado puede ver reservas
requireLogin();

$errores  = array();
$reservas = array();

try {
    if (esAdmin()) {
        // Admin: todas las reservas
        $sql = "SELECT r.IdReserva, r.Fecha, r.Estado,
                       l.Titulo, l.Autor, l.ISBN,
                       p.Nombre, p.Apellido1, p.Apellido2
                FROM reserva r
                INNER JOIN libro    l ON r.IdEjemplar = l.IdEjemplar
                INNER JOIN profesor p ON r.IdProf     = p.IdProf
                ORDER BY r.Fecha DESC, r.IdReserva DESC";
        $consulta = $conexion->prepare($sql);
    } else {
        // Profesor: solo sus reservas
        $sql = "SELECT r.IdReserva, r.Fecha, r.Estado,
                       l.Titulo, l.Autor, l.ISBN,
                       p.Nombre, p.Apellido1, p.Apellido2
                FROM reserva r
                INNER JOIN libro    l ON r.IdEjemplar = l.IdEjemplar
                INNER JOIN profesor p ON r.IdProf     = p.IdProf
                WHERE r.IdProf = :idProf
                ORDER BY r.Fecha DESC, r.IdReserva DESC";
        $consulta = $conexion->prepare($sql);
        $consulta->bindParam(":idProf", $_SESSION["IdProf"], PDO::PARAM_INT);
    }

    $consulta->execute();
    $reservas = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al cargar el listado de reservas: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Listado de reservas</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($reservas)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>Profesor</th>
                    <th>Fecha reserva</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><?php echo (int)$r["IdReserva"]; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r["Titulo"]); ?></strong><br>
                            <span class="text-muted">
                                <?php echo htmlspecialchars($r["Autor"]); ?> (ISBN: <?php echo htmlspecialchars($r["ISBN"]); ?>)
                            </span>
                        </td>
                        <td>
                            <?php
                            echo htmlspecialchars(
                                $r["Apellido1"] . " " . $r["Apellido2"] . ", " . $r["Nombre"]
                            );
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($r["Fecha"]); ?></td>
                        <td>
                            <?php if ($r["Estado"] === "pendiente"): ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php elseif ($r["Estado"] === "avisado"): ?>
                                <span class="badge bg-info text-dark">Avisado</span>
                            <?php elseif ($r["Estado"] === "cancelada"): ?>
                                <span class="badge bg-secondary">Cancelada</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($r["Estado"]); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>No hay reservas registradas.</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>