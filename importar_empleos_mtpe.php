<?php
/**
 * EMPRENDE CUSCO - IMPORTADOR DE VACANTES MTPE
 *
 * Importa el CSV oficial de "Vacantes de la Bolsa de Trabajo"
 * publicado en la Plataforma Nacional de Datos Abiertos.
 *
 * Uso:
 * 1. Copia este archivo como importar_empleos_mtpe.php
 * 2. Coloca el CSV del MTPE en tu PC.
 * 3. Abre este archivo desde XAMPP.
 * 4. Selecciona el CSV y decide si importar solo CUSCO.
 *
 * La página empleos.php lee automáticamente la tabla `empleos`.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/conexion.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$mensaje = '';
$error = '';
$importados = 0;
$omitidos = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Selecciona un archivo CSV válido.';
    } else {
        $tmp = $_FILES['archivo']['tmp_name'];
        $nombre = $_FILES['archivo']['name'];

        $handle = fopen($tmp, 'r');

        if (!$handle) {
            $error = 'No se pudo abrir el CSV.';
        } else {
            /*
             * Detectar delimitador leyendo la primera línea.
             * Se aceptan coma, punto y coma y tabulador.
             */
            $primeraLinea = fgets($handle);
            rewind($handle);

            $delimitador = ',';
            if ($primeraLinea !== false) {
                $conteos = [
                    ',' => substr_count($primeraLinea, ','),
                    ';' => substr_count($primeraLinea, ';'),
                    "\t" => substr_count($primeraLinea, "\t")
                ];
                arsort($conteos);
                $delimitador = array_key_first($conteos);
            }

            $cabeceras = fgetcsv($handle, 0, $delimitador);

            if (!$cabeceras) {
                $error = 'El CSV no contiene cabeceras.';
            } else {
                $cabeceras = array_map(function ($v) {
                    $v = preg_replace('/^\xEF\xBB\xBF/', '', (string)$v);
                    return strtoupper(trim($v));
                }, $cabeceras);

                $indice = [];
                foreach ($cabeceras as $i => $campo) {
                    $indice[$campo] = $i;
                }

                if (!isset($indice['AVISOID']) || !isset($indice['NOMBREAVISO'])) {
                    $error = 'El archivo no parece ser el dataset de Vacantes del MTPE. Debe contener AVISOID y NOMBREAVISO.';
                } else {
                    $soloCusco = isset($_POST['solo_cusco']);

                    $conn->query("
                        CREATE TABLE IF NOT EXISTS `empleos` (
                            `id` VARCHAR(80) NOT NULL,
                            `avisoid` VARCHAR(80) NOT NULL,
                            `titulo` VARCHAR(255) NOT NULL,
                            `vacantes` INT NOT NULL DEFAULT 1,
                            `postulantes` INT NOT NULL DEFAULT 0,
                            `idempresa` VARCHAR(100) DEFAULT NULL,
                            `ubigeo` VARCHAR(20) DEFAULT NULL,
                            `departamento` VARCHAR(100) DEFAULT NULL,
                            `provincia` VARCHAR(100) DEFAULT NULL,
                            `distrito` VARCHAR(100) DEFAULT NULL,
                            `fechainicio` VARCHAR(20) DEFAULT NULL,
                            `fechafin` VARCHAR(20) DEFAULT NULL,
                            `sinexperiencia` VARCHAR(30) DEFAULT NULL,
                            `tiempoexperiencia` VARCHAR(30) DEFAULT NULL,
                            `tipotiempoexperiencia` VARCHAR(30) DEFAULT NULL,
                            `sector` VARCHAR(180) DEFAULT NULL,
                            `esco` TEXT DEFAULT NULL,
                            `espcd` VARCHAR(20) DEFAULT NULL,
                            `fechacreacion` VARCHAR(20) DEFAULT NULL,
                            `activo` VARCHAR(30) DEFAULT NULL,
                            `modalidad` VARCHAR(80) DEFAULT NULL,
                            `fuente` VARCHAR(180) DEFAULT 'MTPE - Datos Abiertos',
                            `importado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `uk_avisoid` (`avisoid`),
                            KEY `idx_departamento` (`departamento`),
                            KEY `idx_activo` (`activo`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");

                    if ($conn->errno) {
                        $error = 'No se pudo preparar la tabla empleos: ' . $conn->error;
                    } else {
                        /*
                         * Upsert: si el aviso ya existe, se actualiza.
                         */
                        $sql = "
                            INSERT INTO `empleos`
                            (`id`,`avisoid`,`titulo`,`vacantes`,`postulantes`,`idempresa`,`ubigeo`,
                             `departamento`,`provincia`,`distrito`,`fechainicio`,`fechafin`,
                             `sinexperiencia`,`tiempoexperiencia`,`tipotiempoexperiencia`,
                             `sector`,`esco`,`espcd`,`fechacreacion`,`activo`,`fuente`)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                            ON DUPLICATE KEY UPDATE
                                titulo=VALUES(titulo),
                                vacantes=VALUES(vacantes),
                                postulantes=VALUES(postulantes),
                                ubigeo=VALUES(ubigeo),
                                departamento=VALUES(departamento),
                                provincia=VALUES(provincia),
                                distrito=VALUES(distrito),
                                fechainicio=VALUES(fechainicio),
                                fechafin=VALUES(fechafin),
                                sinexperiencia=VALUES(sinexperiencia),
                                tiempoexperiencia=VALUES(tiempoexperiencia),
                                tipotiempoexperiencia=VALUES(tipotiempoexperiencia),
                                sector=VALUES(sector),
                                esco=VALUES(esco),
                                espcd=VALUES(espcd),
                                fechacreacion=VALUES(fechacreacion),
                                activo=VALUES(activo),
                                fuente=VALUES(fuente),
                                importado_en=CURRENT_TIMESTAMP
                        ";

                        $stmt = $conn->prepare($sql);

                        if (!$stmt) {
                            $error = 'No se pudo preparar la importación: ' . $conn->error;
                        } else {
                            while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
                                if (count($fila) === 1 && trim((string)$fila[0]) === '') continue;

                                $get = function ($nombre, $default = '') use ($indice, $fila) {
                                    if (!isset($indice[$nombre])) return $default;
                                    return trim((string)($fila[$indice[$nombre]] ?? $default));
                                };

                                $avisoid = $get('AVISOID');
                                $titulo = $get('NOMBREAVISO');

                                if ($avisoid === '' || $titulo === '') {
                                    $omitidos++;
                                    continue;
                                }

                                $departamento = strtoupper($get('DEPARTAMENTO'));

                                if ($soloCusco && $departamento !== 'CUSCO') {
                                    continue;
                                }

                                $vacantes = (int)preg_replace('/\D+/', '', $get('VACANTES', '1'));
                                if ($vacantes < 1) $vacantes = 1;

                                $postulantes = (int)preg_replace('/\D+/', '', $get('POSTULANTES', '0'));

                                $id = 'mtpe-' . $avisoid;

                                $valores = [
                                    $id,
                                    $avisoid,
                                    $titulo,
                                    $vacantes,
                                    $postulantes,
                                    $get('IDEMPRESA'),
                                    $get('UBIGEO'),
                                    $departamento,
                                    $get('PROVINCIA'),
                                    $get('DISTRITO'),
                                    $get('FECHAINICIO'),
                                    $get('FECHAFIN'),
                                    $get('SINEXPERIENCIA'),
                                    $get('TIEMPOEXPERIENCIA'),
                                    $get('TIPOTIEMPOEXPERIENCIA'),
                                    $get('SECTOR'),
                                    $get('ESCO'),
                                    $get('ESPCD'),
                                    $get('FECHACREACION'),
                                    $get('ACTIVO'),
                                    'MTPE - Datos Abiertos'
                                ];

                                $stmt->bind_param(
                                    'sssiissssssssssssssss',
                                    $valores[0], $valores[1], $valores[2], $valores[3], $valores[4],
                                    $valores[5], $valores[6], $valores[7], $valores[8], $valores[9],
                                    $valores[10], $valores[11], $valores[12], $valores[13], $valores[14],
                                    $valores[15], $valores[16], $valores[17], $valores[18], $valores[19],
                                    $valores[20]
                                );

                                if ($stmt->execute()) {
                                    $importados++;
                                } else {
                                    $omitidos++;
                                }
                            }

                            $stmt->close();

                            $mensaje = "Importación terminada. Registros procesados/actualizados: {$importados}. Omitidos: {$omitidos}.";
                        }
                    }
                }
            }

            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Importar empleos MTPE | Emprende Cusco</title>
<style>
body{margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#20242b}
.wrap{width:min(92%,760px);margin:50px auto}
.card{background:#fff;border-radius:20px;padding:32px;box-shadow:0 15px 45px rgba(0,0,0,.09)}
h1{color:#8b0000;margin-top:0}
p{line-height:1.7;color:#667085}
input[type=file]{width:100%;padding:14px;border:1px solid #ddd;border-radius:10px;background:#fafafa}
label{display:block;margin:18px 0;font-weight:bold}
button{background:#8b0000;color:#fff;border:0;border-radius:10px;padding:14px 20px;font-weight:bold;cursor:pointer}
button:hover{background:#5b0000}
.msg{padding:14px;border-radius:10px;background:#e9f8ef;color:#176b42;margin-bottom:18px}
.err{padding:14px;border-radius:10px;background:#fff0f0;color:#9b1c1c;margin-bottom:18px}
.note{padding:15px;border-radius:12px;background:#fff8e1;border:1px solid #eadca7;margin:18px 0}
.back{display:inline-block;margin-top:20px;color:#8b0000;font-weight:bold}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>📥 Importar vacantes del MTPE</h1>
<p>
Carga el CSV público de <strong>Vacantes de la Bolsa de Trabajo del MTPE</strong>.
El importador crea o actualiza la tabla <strong>empleos</strong> de tu base de datos.
</p>

<div class="note">
<strong>Fuente:</strong> Plataforma Nacional de Datos Abiertos del MTPE.<br>
El dataset es una fotografía publicada por el MTPE; no representa una conexión en tiempo real.
Verifica siempre la vigencia de una vacante en el portal oficial antes de postular.
</div>

<?php if ($mensaje): ?><div class="msg"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Archivo CSV del MTPE</label>
    <input type="file" name="archivo" accept=".csv,text/csv" required>

    <label>
        <input type="checkbox" name="solo_cusco" value="1" checked>
        Importar únicamente vacantes del departamento de Cusco
    </label>

    <button type="submit">🚀 Importar a MySQL</button>
</form>

<a class="back" href="empleos.php">← Volver a Bolsa de Empleo</a>
</div>
</div>
</body>
</html>
