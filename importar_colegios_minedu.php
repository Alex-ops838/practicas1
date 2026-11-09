<?php
/**
 * EMPRENDE CUSCO
 * IMPORTADOR DEL PADRÓN DE INSTITUCIONES EDUCATIVAS - MINEDU
 *
 * Uso:
 * 1. Descarga el CSV "Listado de Servicios Educativos escolarizados"
 *    desde la Plataforma Nacional de Datos Abiertos.
 * 2. Coloca el archivo como: minedu_colegios.csv
 * 3. Coloca este archivo en la misma carpeta que registro.php.
 * 4. Abre: http://localhost/emprende_cusco_db/emprende%20cusco/emprende%20cusco/importar_colegios_minedu.php
 *
 * IMPORTANTE:
 * - Haz una copia de seguridad de la BD antes de importar.
 * - El importador NO crea "Otro / No figura en la lista".
 * - El padrón oficial puede cambiar de columnas; por eso se detectan
 *   varios nombres comunes de encabezados.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
require_once 'config/conexion.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalizar($v) {
    $v = trim((string)$v);
    $v = mb_strtolower($v, 'UTF-8');
    $v = strtr($v, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
        'ü'=>'u','ñ'=>'n'
    ]);
    $v = preg_replace('/[^a-z0-9]+/u', '_', $v);
    return trim($v, '_');
}

function buscarColumna(array $headers, array $candidatos) {
    foreach ($candidatos as $c) {
        $c = normalizar($c);
        if (isset($headers[$c])) return $headers[$c];
    }
    return null;
}

function detectarDelimitador($linea) {
    $candidatos = [',' => substr_count($linea, ','), ';' => substr_count($linea, ';'), "\t" => substr_count($linea, "\t")];
    arsort($candidatos);
    return (string)array_key_first($candidatos);
}

$archivo = __DIR__ . DIRECTORY_SEPARATOR . 'minedu_colegios.csv';
$mensaje = '';
$error = '';
$insertados = 0;
$actualizados = 0;
$omitidos = 0;

if (!file_exists($archivo)) {
    $error = 'No se encontró minedu_colegios.csv. Descarga el CSV oficial del MINEDU y colócalo junto a este archivo.';
}

if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn->set_charset('utf8mb4');

    // Crear/ajustar tabla sin borrar usuarios existentes.
    $sqlTabla = "
        CREATE TABLE IF NOT EXISTS colegios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_modular VARCHAR(30) NULL,
            nombre VARCHAR(255) NOT NULL,
            departamento VARCHAR(100) NOT NULL,
            provincia VARCHAR(120) NOT NULL,
            distrito VARCHAR(120) NOT NULL,
            nivel VARCHAR(150) NULL,
            gestion VARCHAR(100) NULL,
            fuente VARCHAR(100) NULL DEFAULT 'MINEDU',
            fecha_importacion DATE NULL,
            PRIMARY KEY (id),
            INDEX idx_ubicacion (departamento, provincia, distrito),
            INDEX idx_nombre (nombre),
            INDEX idx_codigo_modular (codigo_modular)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if (!$conn->query($sqlTabla)) {
        $error = 'No se pudo preparar la tabla colegios: ' . $conn->error;
    }

    // Agregar columnas que podrían faltar en una tabla antigua.
    if (empty($error)) {
        $columnas = [];
        $res = $conn->query("SHOW COLUMNS FROM colegios");
        while ($r = $res->fetch_assoc()) $columnas[$r['Field']] = true;

        $alter = [];
        if (!isset($columnas['codigo_modular'])) $alter[] = "ADD COLUMN codigo_modular VARCHAR(30) NULL AFTER id";
        if (!isset($columnas['nivel'])) $alter[] = "ADD COLUMN nivel VARCHAR(150) NULL";
        if (!isset($columnas['gestion'])) $alter[] = "ADD COLUMN gestion VARCHAR(100) NULL";
        if (!isset($columnas['fuente'])) $alter[] = "ADD COLUMN fuente VARCHAR(100) NULL DEFAULT 'MINEDU'";
        if (!isset($columnas['fecha_importacion'])) $alter[] = "ADD COLUMN fecha_importacion DATE NULL";

        if ($alter && !$conn->query("ALTER TABLE colegios " . implode(', ', $alter))) {
            $error = 'No se pudo actualizar la estructura de colegios: ' . $conn->error;
        }
    }

    if (empty($error)) {
        $fp = fopen($archivo, 'rb');

        if (!$fp) {
            $error = 'No se pudo abrir el CSV.';
        } else {
            $firstLine = fgets($fp);
            if ($firstLine === false) {
                $error = 'El CSV está vacío.';
            } else {
                $delimitador = detectarDelimitador($firstLine);
                rewind($fp);

                $headersRaw = fgetcsv($fp, 0, $delimitador);
                if (!$headersRaw) {
                    $error = 'No se pudo leer la cabecera del CSV.';
                } else {
                    $headers = [];
                    foreach ($headersRaw as $i => $h) {
                        $h = preg_replace('/^\xEF\xBB\xBF/', '', (string)$h);
                        $headers[normalizar($h)] = $i;
                    }

                    $colCodigo = buscarColumna($headers, [
                        'codigo_modular','codigo_modular_','cod_mod','cod_modular',
                        'codigo_modular_ie','codigo'
                    ]);
                    $colNombre = buscarColumna($headers, [
                        'nombre','nombre_ie','nombre_institucion_educativa',
                        'nombre_del_servicio_educativo','servicio_educativo',
                        'nombre_del_servicio'
                    ]);
                    $colDpto = buscarColumna($headers, [
                        'departamento','departamento_','nom_departamento',
                        'nombre_departamento','dpto'
                    ]);
                    $colProv = buscarColumna($headers, [
                        'provincia','nom_provincia','nombre_provincia'
                    ]);
                    $colDist = buscarColumna($headers, [
                        'distrito','nom_distrito','nombre_distrito'
                    ]);
                    $colNivel = buscarColumna($headers, [
                        'nivel','nivel_educativo','nivel_modalidad',
                        'modalidad','forma'
                    ]);
                    $colGestion = buscarColumna($headers, [
                        'gestion','tipo_gestion','tipo_de_gestion',
                        'dependencia','gestion_dependencia'
                    ]);

                    if ($colNombre === null || $colDpto === null || $colProv === null || $colDist === null) {
                        $error = 'No se pudieron identificar las columnas obligatorias. Se necesitan nombre, departamento, provincia y distrito. Revisa el encabezado del CSV MINEDU.';
                    } else {
                        $sql = "
                            INSERT INTO colegios
                            (codigo_modular, nombre, departamento, provincia, distrito, nivel, gestion, fuente, fecha_importacion)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'MINEDU', CURDATE())
                            ON DUPLICATE KEY UPDATE
                                nombre = VALUES(nombre),
                                departamento = VALUES(departamento),
                                provincia = VALUES(provincia),
                                distrito = VALUES(distrito),
                                nivel = VALUES(nivel),
                                gestion = VALUES(gestion),
                                fuente = 'MINEDU',
                                fecha_importacion = CURDATE()
                        ";

                        // Para que ON DUPLICATE funcione bien por código modular,
                        // se crea un índice único solamente si hay códigos.
                        $idx = $conn->query("SHOW INDEX FROM colegios WHERE Key_name='uq_colegio_minedu'");
                        if (!$idx || $idx->num_rows === 0) {
                            @$conn->query("ALTER TABLE colegios ADD UNIQUE KEY uq_colegio_minedu (codigo_modular)");
                        }

                        $stmt = $conn->prepare($sql);

                        if (!$stmt) {
                            $error = 'No se pudo preparar la importación: ' . $conn->error;
                        } else {
                            while (($row = fgetcsv($fp, 0, $delimitador)) !== false) {
                                $nombre = trim((string)($row[$colNombre] ?? ''));
                                $dpto   = trim((string)($row[$colDpto] ?? ''));
                                $prov   = trim((string)($row[$colProv] ?? ''));
                                $dist   = trim((string)($row[$colDist] ?? ''));

                                if ($nombre === '' || $dpto === '' || $prov === '' || $dist === '') {
                                    $omitidos++;
                                    continue;
                                }

                                $codigo = $colCodigo !== null ? trim((string)($row[$colCodigo] ?? '')) : '';
                                $nivel = $colNivel !== null ? trim((string)($row[$colNivel] ?? '')) : '';
                                $gestion = $colGestion !== null ? trim((string)($row[$colGestion] ?? '')) : '';

                                // Evitar nombres extremadamente largos.
                                $nombre = mb_substr($nombre, 0, 255, 'UTF-8');
                                $dpto = mb_substr($dpto, 0, 100, 'UTF-8');
                                $prov = mb_substr($prov, 0, 120, 'UTF-8');
                                $dist = mb_substr($dist, 0, 120, 'UTF-8');
                                $nivel = mb_substr($nivel, 0, 150, 'UTF-8');
                                $gestion = mb_substr($gestion, 0, 100, 'UTF-8');

                                $stmt->bind_param(
                                    'sssssss',
                                    $codigo, $nombre, $dpto, $prov, $dist, $nivel, $gestion
                                );

                                if ($stmt->execute()) {
                                    if ($stmt->affected_rows === 1) $insertados++;
                                    else $actualizados++;
                                } else {
                                    $omitidos++;
                                }
                            }

                            $stmt->close();
                        }
                    }
                }
            }

            fclose($fp);
        }
    }

    if (empty($error)) {
        $mensaje = "Importación terminada. Nuevos/registrados: {$insertados}. Actualizados: {$actualizados}. Omitidos: {$omitidos}.";
    }
}

$total = 0;
if (empty($error)) {
    $r = $conn->query("SELECT COUNT(*) total FROM colegios");
    if ($r) $total = (int)$r->fetch_assoc()['total'];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Importar colegios MINEDU | Emprende Cusco</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fa;margin:0;padding:30px;color:#172033}
.card{max-width:850px;margin:auto;background:#fff;border-radius:20px;padding:30px;box-shadow:0 15px 45px rgba(0,0,0,.08)}
h1{color:#8B0000}
.info,.ok,.error{padding:15px;border-radius:12px;margin:15px 0;line-height:1.55}
.info{background:#fff7ed;border:1px solid #fed7aa}
.ok{background:#ecfdf3;border:1px solid #bbf7d0;color:#166534}
.error{background:#fff1f2;border:1px solid #fecaca;color:#991b1b}
button{background:#8B0000;color:#fff;border:0;border-radius:12px;padding:14px 20px;font-weight:700;cursor:pointer}
code{background:#f1f5f9;padding:2px 5px;border-radius:5px}
</style>
</head>
<body>
<div class="card">
<h1>🏫 Importador de colegios MINEDU</h1>

<div class="info">
<strong>Fuente:</strong> Listado de Servicios Educativos escolarizados del Ministerio de Educación.
<br>
Descarga el CSV oficial desde la Plataforma Nacional de Datos Abiertos y guárdalo con el nombre
<code>minedu_colegios.csv</code> en esta misma carpeta.
</div>

<?php if ($error): ?>
<div class="error">⚠️ <?=h($error)?></div>
<?php endif; ?>

<?php if ($mensaje): ?>
<div class="ok">✅ <?=h($mensaje)?></div>
<?php endif; ?>

<p><strong>Registros actualmente en la tabla:</strong> <?=number_format($total)?></p>

<?php if (file_exists($archivo)): ?>
<p>📄 Archivo detectado: <strong>minedu_colegios.csv</strong></p>
<form method="post">
<button type="submit">🚀 Importar / actualizar padrón MINEDU</button>
</form>
<?php else: ?>
<p>❌ Primero coloca <code>minedu_colegios.csv</code> junto a este archivo.</p>
<?php endif; ?>

<hr>
<p><strong>Después de importar:</strong> vuelve a <code>registro.php</code>, selecciona departamento, provincia y distrito y el selector mostrará las instituciones educativas de esa ubicación.</p>
</div>
</body>
</html>
