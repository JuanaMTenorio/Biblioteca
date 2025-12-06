<?php
// public/log_listado.php

require_once __DIR__ . '/../includes/init.php';

// Solo administrador
requireAdmin();

$errores = array();
$logs    = array();

// Paginación sencilla
$registrosPorPagina = 20;
$paginaActual       = isset($_GET["pagina"]) && ctype_digit($_GET["pagina"])
    ? (int) $_GET["pagina"]
    : 1;
if ($paginaActual < 1) {
    $paginaActual = 1;
}
$offset = ($paginaActual - 1) * $registrosPorPagina;

// 1) Contar total de registros
try {
    $sqlTotal   = "SELECT COUNT(*) AS total FROM log_actividad";
    $stmtTotal  = $conexion->query($sqlTotal);
    $filaTotal  = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalLogs  = (int)$filaTotal["total"];
    $totalPaginas = ($totalLogs > 0)
        ? ceil($totalLogs / $registrosPorPagina)
        : 1;
} catch (PDOException $e) {
    $errores[] = "Error al contar registros de log: " . $e->getMessage();
    $totalLogs = 0;
    $totalPaginas = 1;
}

// 2) Obtener la página actual de registros
try {
    $sql = "SELECT l.IdLog, l.FechaHora, l.TipoActividad, l.TablaAfectada,
                   l.IdRegistro, l.Detalles,
                   p.Nombre, p.Apellido1, p.Apellido2
            FROM log_actividad l
            LEFT JOIN profesor p ON l.IdProf = p.IdProf
            ORDER BY l.FechaHora DESC, l.IdLog DESC
            LIMIT :limite OFFSET :offset";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":limite", $registrosPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al cargar el log de actividad: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Log de actividad</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($logs)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha y hora</th>
                    <th>Tipo</th>
                    <th>Tabla</th>
                    <th>Id registro</th>
                    <th>Usuario</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo (int)$log["IdLog"]; ?></td>
                        <td><?php echo htmlspecialchars($log["FechaHora"]); ?></td>
                        <td><?php echo htmlspecialchars($log["TipoActividad"]); ?></td>
                        <td><?php echo htmlspecialchars($log["TablaAfectada"]); ?></td>
                        <td>
                            <?php
                            echo ($log["IdRegistro"] !== null)
                                ? (int)$log["IdRegistro"]
                                : "-";
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($log["Nombre"] !== null) {
                                echo htmlspecialchars(
                                    $log["Apellido1"] . " " . $log["Apellido2"] . ", " . $log["Nombre"]
                                );
                            } else {
                                echo "<span class=\"text-muted\">(sin usuario)</span>";
                            }
                            ?>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($log["Detalles"])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <nav aria-label="Paginación del log">
        <ul class="pagination">
            <li class="page-item <?php if ($paginaActual <= 1) echo 'disabled'; ?>">
                <a class="page-link"
                    href="log_listado.php?pagina=<?php echo max(1, $paginaActual - 1); ?>">
                    Anterior
                </a>
            </li>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?php if ($i == $paginaActual) echo 'active'; ?>">
                    <a class="page-link"
                        href="log_listado.php?pagina=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php if ($paginaActual >= $totalPaginas) echo 'disabled'; ?>">
                <a class="page-link"
                    href="log_listado.php?pagina=<?php echo min($totalPaginas, $paginaActual + 1); ?>">
                    Siguiente
                </a>
            </li>
        </ul>
    </nav>

<?php else: ?>
    <p>No hay registros de actividad.</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>