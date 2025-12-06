<?php
// public/index.php

require_once __DIR__ . '/../includes/init.php';

// Solo usuarios logueados pueden ver la aplicación
requireLogin();

$errores = array();
$libros  = array();

// Cargamos el listado de libros
try {
    $sql = "SELECT IdEjemplar, ISBN, Titulo, Autor, AnioPublicacion,
                   Editorial, Precio, Portada, Estado
            FROM libro
            ORDER BY Titulo";

    $consulta = $conexion->prepare($sql);
    $consulta->execute();

    $libros = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al cargar el listado de libros: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Listado de libros</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Más adelante aquí podremos poner buscador/filtros -->

<?php if (esAdmin()): ?>
    <div class="mb-3">
        <!-- llevará a libros_alta.php para añadir un libro -->
        <a href="libros_alta.php" class="btn btn-success">
            Nuevo libro
        </a>
    </div>
<?php endif; ?>

<?php if (!empty($libros)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Portada</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Año</th>
                    <th>Editorial</th>
                    <th>ISBN</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Operaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td style="width: 90px; text-align: center;">
                            <?php if (!empty($libro["Portada"])): ?>
                                <img
                                    src="<?php echo htmlspecialchars($libro["Portada"]); ?>"
                                    alt="Portada"
                                    class="img-thumbnail"
                                    style="max-width: 70px; max-height: 90px;">
                            <?php else: ?>
                                <span class="text-muted">Sin imagen</span>
                            <?php endif; ?>
                        </td>

                        <td><?php echo htmlspecialchars($libro["Titulo"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["Autor"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["AnioPublicacion"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["Editorial"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["ISBN"]); ?></td>
                        <td>
                            <?php
                            if ($libro["Precio"] !== null) {
                                echo number_format($libro["Precio"], 2, ',', '.') . " €";
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($libro["Estado"] === "disponible"): ?>
                                <span class="badge bg-success">Disponible</span>
                            <?php elseif ($libro["Estado"] === "prestado"): ?>
                                <span class="badge bg-warning text-dark">Prestado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($libro["Estado"]); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <!-- Enlaces de operaciones -->
                            <div class="btn-group" role="group">

                                <?php if (esAdmin()): ?>
                                    <!-- Editar libro -->
                                    <a
                                        href="libros_editar.php?IdEjemplar=<?php echo (int)$libro["IdEjemplar"]; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Editar libro">
                                        Editar
                                    </a>

                                    <!-- Eliminar libro -->
                                    <a
                                        href="libros_eliminar.php?IdEjemplar=<?php echo (int)$libro["IdEjemplar"]; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Eliminar libro">
                                        Eliminar
                                    </a>
                                <?php endif; ?>

                                <!-- Préstamo / Reserva -->
                                <?php if ($libro["Estado"] === "disponible"): ?>
                                    <a
                                        href="#"
                                        class="btn btn-sm btn-outline-success disabled"
                                        title="Prestar libro (pendiente)">
                                        Prestar
                                    </a>
                                <?php elseif ($libro["Estado"] === "prestado"): ?>
                                    <a
                                        href="#"
                                        class="btn btn-sm btn-outline-warning disabled"
                                        title="Reservar libro (pendiente)">
                                        Reservar
                                    </a>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>No hay libros registrados en la base de datos.</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>