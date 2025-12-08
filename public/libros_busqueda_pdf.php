<?php
// public/libros_busqueda_pdf.php

require_once __DIR__ . '/../includes/init.php';
requireLogin();

// Ruta a FPDF
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

$tituloFiltro    = isset($_GET["titulo"])    ? trim($_GET["titulo"])    : "";
$autorFiltro     = isset($_GET["autor"])     ? trim($_GET["autor"])     : "";
$isbnFiltro      = isset($_GET["isbn"])      ? trim($_GET["isbn"])      : "";
$editorialFiltro = isset($_GET["editorial"]) ? trim($_GET["editorial"]) : "";

// 1. Misma consulta que en libros_busqueda.php
try {
    $sql = "SELECT ISBN, Titulo, Autor, AnioPublicacion, Editorial, Estado
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
    // OJO: no mostramos HTML ni warnings, solo cortamos si hay un error grave
    die("Error al generar el PDF");
}

// 2. Descripcion de la consulta (sin acentos para evitar problemas)
$descripcionConsulta = "Listado de libros";
$filtrosTexto = array();

if ($tituloFiltro !== "") {
    $filtrosTexto[] = "titulo contiene '{$tituloFiltro}'";
}
if ($autorFiltro !== "") {
    $filtrosTexto[] = "autor contiene '{$autorFiltro}'";
}
if ($isbnFiltro !== "") {
    $filtrosTexto[] = "ISBN contiene '{$isbnFiltro}'";
}
if ($editorialFiltro !== "") {
    $filtrosTexto[] = "editorial contiene '{$editorialFiltro}'";
}

if (!empty($filtrosTexto)) {
    $descripcionConsulta .= " (filtros: " . implode("; ", $filtrosTexto) . ")";
} else {
    $descripcionConsulta .= " (sin filtros: todos los libros)";
}

// 3. Generar PDF
$pdf = new FPDF();
$pdf->AddPage();

// Cabecera
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Biblioteca DWES - Resultado de busqueda', 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, $descripcionConsulta);
$pdf->Ln(5);

// Cabecera de tabla (sin tildes para evitar lios de codificacion)
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 7, 'Titulo', 1);
$pdf->Cell(40, 7, 'Autor', 1);
$pdf->Cell(25, 7, 'ISBN', 1);
$pdf->Cell(15, 7, 'Anio', 1);
$pdf->Cell(30, 7, 'Editorial', 1);
$pdf->Cell(20, 7, 'Estado', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 8);

// Filas
foreach ($libros as $libro) {
    $titulo    = $libro["Titulo"];
    $autor     = $libro["Autor"];
    $isbn      = $libro["ISBN"];
    $anio      = $libro["AnioPublicacion"];
    $editorial = $libro["Editorial"];
    $estado    = ($libro["Estado"] === 'disponible') ? 'Disponible' : 'Prestado';

    // Cortamos textos largos para que no se desborden
    if (strlen($titulo) > 40) {
        $titulo = substr($titulo, 0, 37) . '...';
    }
    if (strlen($autor) > 25) {
        $autor = substr($autor, 0, 22) . '...';
    }
    if (strlen($editorial) > 20) {
        $editorial = substr($editorial, 0, 17) . '...';
    }

    $pdf->Cell(60, 6, $titulo, 1);
    $pdf->Cell(40, 6, $autor, 1);
    $pdf->Cell(25, 6, $isbn, 1);
    $pdf->Cell(15, 6, $anio, 1);
    $pdf->Cell(30, 6, $editorial, 1);
    $pdf->Cell(20, 6, $estado, 1);
    $pdf->Ln();
}

// 4. Descargar PDF (sin haber enviado nada antes al navegador)
$pdf->Output('D', 'resultado_busqueda_libros.pdf');
