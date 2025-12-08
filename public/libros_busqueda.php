<?php
// public/libros_busqueda.php

require_once __DIR__ . '/../includes/init.php';
requireLogin();

$errores = array();
$libros  = array();

// Filtros (por GET)
$tituloFiltro    = isset($_GET["titulo"])    ? trim($_GET["titulo"])    : "";
$autorFiltro     = isset($_GET["autor"])     ? trim($_GET["autor"])     : "";
$isbnFiltro      = isset($_GET["isbn"])      ? trim($_GET["isbn"])      : "";
$editorialFiltro = isset($_GET["editorial"]) ? trim($_GET["editorial"]) : "";

// 1. Construimos la consulta con filtros dinámicos
try {
    $sql = "SELECT IdEjemplar, ISBN, Titulo, Autor, AnioPublicacion,
                   Editorial, Precio, Portada, Estado
            FROM libro";

    $condiciones = array();
    $parametros  = array();

    if ($tituloFiltro !== "") {
        $condiciones[]         = "Titulo LIKE :titulo";
        $parametros[":titulo"] = "%" . $tituloFiltro . "%";
    }

    if ($autorFiltro !== "") {
        $condiciones[]        = "Autor LIKE :autor";
        $parametros[":autor"] = "%" . $autorFiltro . "%";
    }

    if ($isbnFiltro !== "") {
        $condiciones[]       = "ISBN LIKE :isbn";
        $parametros[":isbn"] = "%" . $isbnFiltro . "%";
    }

    if ($editorialFiltro !== "") {
        $condiciones[]             = "Editorial LIKE :editorial";
        $parametros[":editorial"]  = "%" . $editorialFiltro . "%";
    }

    if (count($condiciones) > 0) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }

    $sql .= " ORDER BY Titulo";

    $consulta = $conexion->prepare($sql);

    foreach ($parametros as $nombre => $valor) {
        $consulta->bindValue($nombre, $valor);
    }

    $consulta->execute();
    $libros = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al realizar la consulta: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Búsqueda de libros</h1>

<?php if (count($errores) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- FORMULARIO DE BÚSQUEDA -->
<div class="card mb-4">
    <div class="card-body">
        <form action="libros_busqueda.php" method="get" class="row g-3">

            <div class="col-md-3">
                <label for="titulo" class="form-label">Título</label>
                <input
                    type="text"
                    name="titulo"
                    id="titulo"
                    class="form-control"
                    value="<?php echo htmlspecialchars($tituloFiltro); ?>"
                    placeholder="Título contiene...">
            </div>

            <div class="col-md-3">
                <label for="autor" class="form-label">Autor</label>
                <input
                    type="text"
                    name="autor"
                    id="autor"
                    class="form-control"
                    value="<?php echo htmlspecialchars($autorFiltro); ?>"
                    placeholder="Autor contiene...">
            </div>

            <div class="col-md-3">
                <label for="isbn" class="form-label">ISBN</label>
                <input
                    type="text"
                    name="isbn"
                    id="isbn"
                    class="form-control"
                    value="<?php echo htmlspecialchars($isbnFiltro); ?>"
                    placeholder="ISBN contiene...">
            </div>

            <div class="col-md-3">
                <label for="editorial" class="form-label">Editorial</label>
                <input
                    type="text"
                    name="editorial"
                    id="editorial"
                    class="form-control"
                    value="<?php echo htmlspecialchars($editorialFiltro); ?>"
                    placeholder="Editorial contiene...">
            </div>

            <div class="col-12 d-flex justify-content-between mt-3">
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        Buscar
                    </button>
                    <a href="libros_busqueda.php" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>

                <?php if (!empty($libros)): ?>
                    <!-- OJO: este form va FUERA del otro, por eso está vacío aquí -->
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($libros)): ?>

    <!-- BOTÓN PDF (formulario separado, NO anidado dentro del anterior) -->
    <div class="mb-3 text-end">
        <form action="libros_busqueda_pdf.php" method="get" target="_blank" class="d-inline">
            <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($tituloFiltro); ?>">
            <input type="hidden" name="autor" value="<?php echo htmlspecialchars($autorFiltro); ?>">
            <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($isbnFiltro); ?>">
            <input type="hidden" name="editorial" value="<?php echo htmlspecialchars($editorialFiltro); ?>">

            <button type="submit" class="btn btn-outline-dark">
                Generar PDF de esta búsqueda
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>ISBN</th>
                    <th>Año</th>
                    <th>Editorial</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($libro["Titulo"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["Autor"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["ISBN"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["AnioPublicacion"]); ?></td>
                        <td><?php echo htmlspecialchars($libro["Editorial"]); ?></td>
                        <td>
                            <?php if ($libro["Estado"] === "disponible"): ?>
                                <span class="badge bg-success">Disponible</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Prestado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($_GET): ?>
    <p>No se han encontrado libros con esos criterios.</p>
<?php else: ?>
    <p>Introduce algún criterio de búsqueda y pulsa "Buscar".</p>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>