
<?php
// EMPRENDE CUSCO - IMPORTADOR UBIGEO PERU
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = new mysqli("localhost", "root", "", "emprende_cusco");
$conexion->set_charset("utf8mb4");

$carpeta = __DIR__ . "/csv/";

$archivos = [
    "departamentos" => $carpeta . "1_ubigeo_departamentos.csv",
    "provincias"    => $carpeta . "2_ubigeo_provincias.csv",
    "distritos"     => $carpeta . "3_ubigeo_distritos.csv"
];

// LOS 24 DEPARTAMENTOS Y LA PROVINCIA CONSTITUCIONAL DEL CALLAO
$departamentosPeru = [
    "Amazonas",
    "Áncash",
    "Apurímac",
    "Arequipa",
    "Ayacucho",
    "Cajamarca",
    "Callao",
    "Cusco",
    "Huancavelica",
    "Huánuco",
    "Ica",
    "Junín",
    "La Libertad",
    "Lambayeque",
    "Lima",
    "Loreto",
    "Madre de Dios",
    "Moquegua",
    "Pasco",
    "Piura",
    "Puno",
    "San Martín",
    "Tacna",
    "Tumbes",
    "Ucayali"
];

function abrirCSV($archivo) {
    if (!file_exists($archivo)) {
        throw new Exception(
            "No se encuentra el archivo " . basename($archivo)
        );
    }

    $f = fopen($archivo, "r");

    if (!$f) {
        throw new Exception("No se pudo abrir el archivo CSV.");
    }

    $cabecera = fgetcsv($f);

    if (!$cabecera) {
        fclose($f);
        throw new Exception("El archivo CSV está vacío.");
    }

    // Eliminar BOM de UTF-8
    $cabecera[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecera[0]);

    $cabecera = array_map(
        fn($v) => strtolower(trim($v)),
        $cabecera
    );

    return [$f, $cabecera];
}

function valorCSV($fila, $cabecera, $columna) {
    $posicion = array_search($columna, $cabecera, true);

    if ($posicion === false || !isset($fila[$posicion])) {
        throw new Exception(
            "Falta la columna '$columna' en el CSV."
        );
    }

    return trim($fila[$posicion]);
}

function normalizar($texto) {
    $texto = trim($texto);
    $texto = mb_strtolower($texto, "UTF-8");

    return strtr($texto, [
        "á" => "a",
        "é" => "e",
        "í" => "i",
        "ó" => "o",
        "ú" => "u",
        "ü" => "u"
    ]);
}

try {
    // VERIFICAR QUE EXISTAN LOS TRES CSV
    foreach ($archivos as $archivo) {
        if (!file_exists($archivo)) {
            throw new Exception(
                "Falta el archivo " . basename($archivo) .
                ". Coloca los tres CSV dentro de la carpeta csv."
            );
        }
    }

    // LEER Y VALIDAR ANTES DE BORRAR DATOS
    [$f, $cabecera] = abrirCSV($archivos["departamentos"]);

    foreach (["id", "departamento"] as $columna) {
        if (!in_array($columna, $cabecera, true)) {
            fclose($f);
            throw new Exception(
                "El CSV de departamentos debe contener: id, departamento."
            );
        }
    }

    $departamentosCSV = [];

    while (($fila = fgetcsv($f)) !== false) {
        if (count($fila) < count($cabecera)) continue;

        $id = valorCSV($fila, $cabecera, "id");
        $nombre = valorCSV($fila, $cabecera, "departamento");

        $departamentosCSV[$id] = $nombre;
    }

    fclose($f);

    $nombresCSV = array_map("normalizar", array_values($departamentosCSV));

    foreach ($departamentosPeru as $nombre) {
        if (!in_array(normalizar($nombre), $nombresCSV, true)) {
            throw new Exception(
                "El CSV no contiene el departamento esperado: " . $nombre .
                ". No se modificó la base de datos."
            );
        }
    }

    // LEER Y VALIDAR COLUMNAS DE PROVINCIAS
    [$f, $cabecera] = abrirCSV($archivos["provincias"]);

    foreach (["id", "departamento_id", "provincia"] as $columna) {
        if (!in_array($columna, $cabecera, true)) {
            fclose($f);
            throw new Exception(
                "El CSV de provincias debe contener: id, departamento_id, provincia."
            );
        }
    }

    fclose($f);

    // LEER Y VALIDAR COLUMNAS DE DISTRITOS
    [$f, $cabecera] = abrirCSV($archivos["distritos"]);

    foreach (["provincia_id", "distrito"] as $columna) {
        if (!in_array($columna, $cabecera, true)) {
            fclose($f);
            throw new Exception(
                "El CSV de distritos debe contener: provincia_id, distrito."
            );
        }
    }

    fclose($f);

    // INICIAR IMPORTACIÓN
    $conexion->begin_transaction();

    // Solo se eliminan ubicaciones, nunca los usuarios.
    $conexion->query("DELETE FROM ubigeo_distritos");
    $conexion->query("DELETE FROM ubigeo_provincias");
    $conexion->query("DELETE FROM ubigeo_departamentos");

    $mapaDepartamentos = [];
    $mapaProvincias = [];

    // 1. IMPORTAR DEPARTAMENTOS
    $insertar = $conexion->prepare(
        "INSERT INTO ubigeo_departamentos (departamento)
         VALUES (?)"
    );

    foreach ($departamentosCSV as $idOrigen => $nombre) {
        $insertar->bind_param("s", $nombre);
        $insertar->execute();

        $mapaDepartamentos[$idOrigen] = $conexion->insert_id;
    }

    // 2. IMPORTAR PROVINCIAS
    [$f, $cabecera] = abrirCSV($archivos["provincias"]);

    $insertar = $conexion->prepare(
        "INSERT INTO ubigeo_provincias (departamento_id, provincia)
         VALUES (?, ?)"
    );

    while (($fila = fgetcsv($f)) !== false) {
        if (count($fila) < count($cabecera)) continue;

        $idOrigen = valorCSV($fila, $cabecera, "id");
        $departamentoOrigen = valorCSV(
            $fila, $cabecera, "departamento_id"
        );
        $nombre = valorCSV($fila, $cabecera, "provincia");

        if (!isset($mapaDepartamentos[$departamentoOrigen])) {
            throw new Exception(
                "No existe el departamento relacionado con la provincia $nombre."
            );
        }

        $departamentoId = $mapaDepartamentos[$departamentoOrigen];

        $insertar->bind_param("is", $departamentoId, $nombre);
        $insertar->execute();

        $mapaProvincias[$idOrigen] = $conexion->insert_id;
    }

    fclose($f);

    // 3. IMPORTAR DISTRITOS
    [$f, $cabecera] = abrirCSV($archivos["distritos"]);

    $insertar = $conexion->prepare(
        "INSERT INTO ubigeo_distritos (provincia_id, distrito)
         VALUES (?, ?)"
    );

    while (($fila = fgetcsv($f)) !== false) {
        if (count($fila) < count($cabecera)) continue;

        $provinciaOrigen = valorCSV(
            $fila, $cabecera, "provincia_id"
        );
        $nombre = valorCSV($fila, $cabecera, "distrito");

        if (!isset($mapaProvincias[$provinciaOrigen])) {
            throw new Exception(
                "No existe la provincia relacionada con el distrito $nombre."
            );
        }

        $provinciaId = $mapaProvincias[$provinciaOrigen];

        $insertar->bind_param("is", $provinciaId, $nombre);
        $insertar->execute();
    }

    fclose($f);

    // COMPROBAR TOTALES
    $totalDepartamentos = (int)$conexion->query(
        "SELECT COUNT(*) AS total FROM ubigeo_departamentos"
    )->fetch_assoc()["total"];

    $totalProvincias = (int)$conexion->query(
        "SELECT COUNT(*) AS total FROM ubigeo_provincias"
    )->fetch_assoc()["total"];

    $totalDistritos = (int)$conexion->query(
        "SELECT COUNT(*) AS total FROM ubigeo_distritos"
    )->fetch_assoc()["total"];

    if ($totalDepartamentos !== 25) {
        throw new Exception(
            "Se esperaban 25 departamentos y Callao en total; se importaron " .
            $totalDepartamentos . "."
        );
    }

    $conexion->commit();

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>";
    echo "<title>UBIGEO importado</title>";
    echo "<style>
        body{font-family:Arial;background:#f4f5f7;padding:30px}
        .caja{max-width:600px;margin:auto;background:white;padding:25px;
        border-radius:12px;box-shadow:0 3px 12px #ddd}
        h2{color:#7b1725}
        a{color:#7b1725}
    </style></head><body><div class='caja'>";

    echo "<h2>UBIGEO importado correctamente</h2>";
    echo "<p>Departamentos y Callao: $totalDepartamentos</p>";
    echo "<p>Provincias: $totalProvincias</p>";
    echo "<p>Distritos: $totalDistritos</p>";
    echo "<p><a href='registro.php'>Abrir formulario de registro</a></p>";
    echo "</div></body></html>";

} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {}

    http_response_code(500);

    echo "<h2>Error al importar UBIGEO</h2>";
    echo "<p>" . htmlspecialchars(
        $e->getMessage(), ENT_QUOTES, "UTF-8"
    ) . "</p>";
    echo "<p>Verifica las columnas y los datos de los tres CSV.</p>";
}
?>
