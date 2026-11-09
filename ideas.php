<?php
/**
 * ============================================================
 * EMPRENDE CUSCO - MÓDULO DE IDEAS Y PLAN DE NEGOCIO ESCOLAR
 * Archivo: ideas.php
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación de sesión activa de usuario
if (!isset($_SESSION['usuario_id']) || !is_numeric($_SESSION['usuario_id']) || (int)$_SESSION['usuario_id'] <= 0) {
    header('Location: login.php?error=inicia_sesion');
    exit;
}
$usuarioIdSesion = (int) $_SESSION['usuario_id'];

ini_set('display_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/config/conexion.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Error: No se encontró la conexión MySQL activa en config/conexion.php.');
}

$conn->set_charset('utf8mb4');

// ============================================================
// CATÁLOGO OPCIONAL DESDE MYSQL
// Si la tabla catalogo_ideas_cusco existe, sus ideas aparecen primero
// y se completan con el catálogo real integrado hasta llegar a 10.
// ============================================================
$ideasPorRubroDB = [];
$detallesCatalogoDB = [];
$resultadoCatalogoDB = false;
$tablaCatalogoExiste = false;

try {
    $sqlVerificarTabla = "SELECT COUNT(*) AS total
                         FROM information_schema.tables
                         WHERE table_schema = DATABASE()
                           AND table_name = 'catalogo_ideas_cusco'";
    $resultadoVerificarTabla = $conn->query($sqlVerificarTabla);
    if ($resultadoVerificarTabla instanceof mysqli_result) {
        $filaVerificarTabla = $resultadoVerificarTabla->fetch_assoc();
        $tablaCatalogoExiste = ((int)($filaVerificarTabla['total'] ?? 0) > 0);
        $resultadoVerificarTabla->free();
    }
} catch (Throwable $e) {
    error_log('No se pudo comprobar la tabla catalogo_ideas_cusco: ' . $e->getMessage());
    $tablaCatalogoExiste = false;
}

if ($tablaCatalogoExiste) {
    $sqlCatalogoDB = "SELECT categoria, nombre, descripcion, provincia, distrito,
                             busqueda_imagen, fuente_informacion
                      FROM catalogo_ideas_cusco
                      WHERE estado = 1
                      ORDER BY categoria, id";
    try {
        $resultadoCatalogoDB = $conn->query($sqlCatalogoDB);
    } catch (Throwable $e) {
        error_log('No se pudo cargar catalogo_ideas_cusco: ' . $e->getMessage());
        $resultadoCatalogoDB = false;
    }
}

if ($resultadoCatalogoDB instanceof mysqli_result) {
    while ($filaCatalogoDB = $resultadoCatalogoDB->fetch_assoc()) {
        $categoriaDB = trim((string)($filaCatalogoDB['categoria'] ?? ''));
        $nombreDB = trim((string)($filaCatalogoDB['nombre'] ?? ''));
        if ($categoriaDB === '' || $nombreDB === '') continue;
        if (!isset($ideasPorRubroDB[$categoriaDB])) $ideasPorRubroDB[$categoriaDB] = [];
        if (!in_array($nombreDB, $ideasPorRubroDB[$categoriaDB], true)) {
            $ideasPorRubroDB[$categoriaDB][] = $nombreDB;
        }
        $detallesCatalogoDB[$nombreDB] = [
            'categoria' => $categoriaDB,
            'nombre' => $nombreDB,
            'descripcion' => (string)($filaCatalogoDB['descripcion'] ?? ''),
            'provincia' => (string)($filaCatalogoDB['provincia'] ?? ''),
            'distrito' => (string)($filaCatalogoDB['distrito'] ?? ''),
            'busqueda_imagen' => (string)($filaCatalogoDB['busqueda_imagen'] ?? ''),
            'fuente_informacion' => (string)($filaCatalogoDB['fuente_informacion'] ?? '')
        ];
    }
    $resultadoCatalogoDB->free();
}

// Búsqueda de fotografías en Wikimedia Commons con enlace de origen.
function buscarFotosWikimediaCommons(string $consulta, int $limite = 10): array {
    $terminos = trim($consulta . ' Cusco Peru');
    $apiCommons = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action' => 'query',
        'generator' => 'search',
        'gsrsearch' => $terminos . ' filetype:bitmap',
        'gsrnamespace' => 6,
        'gsrlimit' => min(20, max(2, $limite)),
        'prop' => 'imageinfo',
        'iiprop' => 'url|extmetadata',
        'iiurlwidth' => 900,
        'format' => 'json',
        'origin' => '*'
    ]);
    $respuestaCommons = false;
    if (function_exists('curl_init')) {
        $chCommons = curl_init($apiCommons);
        curl_setopt_array($chCommons, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'EmprendeCusco/1.0 (catalogo educativo; image search)'
        ]);
        $respuestaCommons = curl_exec($chCommons);
        $codigoCommons = (int)curl_getinfo($chCommons, CURLINFO_HTTP_CODE);
        curl_close($chCommons);
        if ($codigoCommons < 200 || $codigoCommons >= 300) $respuestaCommons = false;
    } else {
        $ctxCommons = stream_context_create(['http' => [
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => "User-Agent: EmprendeCusco/1.0\r\n"
        ]]);
        $respuestaCommons = @file_get_contents($apiCommons, false, $ctxCommons);
    }
    $jsonCommons = is_string($respuestaCommons) ? json_decode($respuestaCommons, true) : null;
    $paginasCommons = $jsonCommons['query']['pages'] ?? [];
    if (!is_array($paginasCommons)) return [];
    $resultadoCommons = [];
    foreach ($paginasCommons as $paginaCommons) {
        $infoCommons = $paginaCommons['imageinfo'][0] ?? [];
        $urlCommons = (string)($infoCommons['thumburl'] ?? $infoCommons['url'] ?? '');
        if (!preg_match('~^https?://~i', $urlCommons)) continue;
        $metaCommons = $infoCommons['extmetadata'] ?? [];
        $tituloCommons = (string)($paginaCommons['title'] ?? $consulta);
        $fuenteCommons = (string)($infoCommons['descriptionurl'] ?? ('https://commons.wikimedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $tituloCommons))));
        $creditoCommons = trim(strip_tags((string)($metaCommons['Artist']['value'] ?? '')));
        $resultadoCommons[] = [
            'url' => $urlCommons,
            'credito' => $tituloCommons . ($creditoCommons !== '' ? ' — ' . $creditoCommons : ''),
            'fuente' => $fuenteCommons
        ];
        if (count($resultadoCommons) >= $limite) break;
    }
    return $resultadoCommons;
}


// ============================================================
// FOTOS REALES DESDE LAS PÁGINAS DE CADA EMPRENDIMIENTO
// Se usa una lista cerrada de fuentes: no se acepta una URL enviada
// libremente por el navegador. El servidor extrae og:image / twitter:image.
// ============================================================
function obtenerFuenteEmprendimiento(string $id): string {
    $fuentes = [
        'cafe-ayllu' => 'https://www.cuscosocial.com/nota.php?id_not=49',
        'arte-sano' => 'https://artesanoheladeria.mypesdigitales.com/',
        'auria' => 'https://www.gob.pe/institucion/produce/noticias/1386990-auria-el-emprendimiento-cusqueno-que-fusiona-el-tejido-tradicional-con-tecnologia-de-ultima-generacion',
        'fruticyd' => 'https://fruticydcusco.com/nosotros',
        'qori-puskay' => 'https://qoripuskay.com/sobre-nosotros/',
        'brew-brothers' => 'https://elcomercio.pe/somos/gastronomia/brew-brothers-la-primera-cafeteria-de-especialidad-en-el-mercado-de-san-blas-cusco-noticia/',
        'three-monkeys' => 'https://rpp.pe/peru/cusco/dia-del-cafe-peruano-la-reinvencion-del-triciclo-cafetero-three-monkeys-en-cusco-noticia-1289301',
        'qosqo-creme' => 'https://larepublica.pe/sociedad/2020/07/29/cusco-efrain-valles-de-ser-distinguido-como-el-mejor-guia-del-mundo-a-vender-helados-artesanales-lrsd',
        'allillanchu' => 'https://www.canalipe.gob.pe/noticias/identidad/allillanchu-el-proyecto-nacido-en-cusco-que-empodera-mujeres-en-estado-vulnerable',
        'pallay' => 'https://pallay.pe/pages/experiencia-pallay'
    ];
    return $fuentes[$id] ?? '';
}

function descargarHtmlFuente(string $url): string {
    if ($url === '') return '';
    $html = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; EmprendeCuscoCatalog/1.0; +https://www.gob.pe/)',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml', 'Accept-Language: es-PE,es;q=0.9']
        ]);
        $html = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tipo = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($status < 200 || $status >= 300 || ($tipo !== '' && stripos($tipo, 'text/html') === false)) $html = false;
    } else {
        $ctx = stream_context_create(['http' => [
            'timeout' => 12, 'ignore_errors' => true,
            'header' => "User-Agent: Mozilla/5.0 EmprendeCuscoCatalog/1.0\r\nAccept: text/html\r\n"
        ]]);
        $html = @file_get_contents($url, false, $ctx);
    }
    return is_string($html) ? $html : '';
}

if (isset($_GET['ajax_emprendimiento_images']) && $_GET['ajax_emprendimiento_images'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=21600');
    $id = preg_replace('/[^a-z0-9-]/i', '', (string)($_GET['id'] ?? ''));
    $urlFuente = obtenerFuenteEmprendimiento($id);
    if ($urlFuente === '') {
        http_response_code(404);
        echo json_encode(['ok' => false, 'images' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $html = descargarHtmlFuente($urlFuente);
    $imagenes = [];
    if ($html !== '') {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $patrones = [
            "~<meta[^>]+property=[\"\']og:image(?::secure_url)?[\"\'][^>]+content=[\"\']([^\"\']+)[\"\']~i",
            "~<meta[^>]+content=[\"\']([^\"\']+)[\"\'][^>]+property=[\"\']og:image(?::secure_url)?[\"\']~i",
            "~<meta[^>]+name=[\"\']twitter:image(?::src)?[\"\'][^>]+content=[\"\']([^\"\']+)[\"\']~i",
            "~<meta[^>]+content=[\"\']([^\"\']+)[\"\'][^>]+name=[\"\']twitter:image(?::src)?[\"\']~i"
        ];
        foreach ($patrones as $patron) {
            if (preg_match_all($patron, $html, $matches)) {
                foreach ($matches[1] as $urlImg) {
                    $urlImg = trim(html_entity_decode($urlImg, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if (str_starts_with($urlImg, '//')) $urlImg = 'https:' . $urlImg;
                    if (preg_match('~^https://~i', $urlImg) && !preg_match('~logo|icon|avatar|favicon|sprite~i', $urlImg)) {
                        $imagenes[$urlImg] = ['url' => $urlImg, 'credito' => 'Imagen de la página fuente', 'fuente' => $urlFuente];
                    }
                }
            }
        }
        // Extrae también imágenes de contenido cuando la página no publica og:image.
        if (count($imagenes) < 2 && preg_match_all("~<img\\b[^>]*(?:src|data-src)=[\"\']([^\"\']+)[\"\'][^>]*>~i", $html, $matches)) {
            foreach ($matches[1] as $urlImg) {
                $urlImg = trim(html_entity_decode($urlImg, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (str_starts_with($urlImg, '//')) $urlImg = 'https:' . $urlImg;
                if (!preg_match('~^https://~i', $urlImg) || preg_match('~logo|icon|avatar|favicon|sprite|banner|pixel|tracking~i', $urlImg)) continue;
                $imagenes[$urlImg] = ['url' => $urlImg, 'credito' => 'Fotografía publicada en la página fuente', 'fuente' => $urlFuente];
                if (count($imagenes) >= 4) break;
            }
        }
    }
    echo json_encode(['ok' => count($imagenes) > 0, 'images' => array_slice(array_values($imagenes), 0, 4), 'source' => $urlFuente], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Endpoint AJAX para imágenes de Google (o Commons como alternativa).
if (isset($_GET['ajax_google_images']) && $_GET['ajax_google_images'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $archivoConfigGoogle = __DIR__ . '/config/google_images.php';
    if (is_file($archivoConfigGoogle)) {
        require_once $archivoConfigGoogle;
    }
    $apiKey = getenv('GOOGLE_CSE_API_KEY') ?: (defined('GOOGLE_CSE_API_KEY') ? GOOGLE_CSE_API_KEY : '');
    $searchEngineId = getenv('GOOGLE_CSE_CX') ?: (defined('GOOGLE_CSE_CX') ? GOOGLE_CSE_CX : '');
    $query = trim((string)($_GET['q'] ?? ''));
    if ($query === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'images' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($apiKey === '' || $searchEngineId === '') {
        $googleUrl = 'https://www.google.com/search?' . http_build_query([
            'tbm' => 'isch', 'q' => $query . ' Cusco Perú', 'hl' => 'es', 'gl' => 'pe', 'safe' => 'active'
        ]);
        $htmlGoogle = false;
        $agenteGoogle = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/125.0 Safari/537.36';
        if (function_exists('curl_init')) {
            $chGoogle = curl_init($googleUrl);
            curl_setopt_array($chGoogle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => $agenteGoogle,
                CURLOPT_HTTPHEADER => ['Accept-Language: es-PE,es;q=0.9,en;q=0.8']
            ]);
            $htmlGoogle = curl_exec($chGoogle);
            $googleStatus = (int)curl_getinfo($chGoogle, CURLINFO_HTTP_CODE);
            curl_close($chGoogle);
            if ($googleStatus < 200 || $googleStatus >= 300) $htmlGoogle = false;
        } else {
            $contextoGoogle = stream_context_create(['http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "User-Agent: {$agenteGoogle}\r\nAccept-Language: es-PE,es;q=0.9,en;q=0.8\r\n"
            ]]);
            $htmlGoogle = @file_get_contents($googleUrl, false, $contextoGoogle);
            $estadoGoogle = $http_response_header[0] ?? '';
            if (!is_string($htmlGoogle) || (preg_match('~\s([45][0-9]{2})\s~', $estadoGoogle))) {
                $htmlGoogle = false;
            }
        }
        $imagenesGoogle = [];
        if (is_string($htmlGoogle) && stripos($htmlGoogle, 'unusual traffic') === false) {
            $htmlGoogleNormalizado = html_entity_decode(
                str_replace(['\\u003d', '\\u0026', '\\/', '\\u003c', '\\u003e'], ['=', '&', '/', '<', '>'], $htmlGoogle),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $urlsCandidatasGoogle = [];
            foreach (['tu', 'ou'] as $claveImagen) {
                $patronJson = '~\"' . $claveImagen . '\"\s*:\s*\"((?:https?:)?(?:\\/|/){1,2}[^\"]+)~i';
                if (preg_match_all($patronJson, $htmlGoogleNormalizado, $coincidenciasJson)) {
                    foreach (($coincidenciasJson[1] ?? []) as $urlJson) {
                        $urlJson = str_replace('\\/', '/', $urlJson);
                        if (strpos($urlJson, '//') === 0) $urlJson = 'https:' . $urlJson;
                        if (preg_match('~^https?://~i', $urlJson)) $urlsCandidatasGoogle[] = $urlJson;
                    }
                }
            }
            if (preg_match_all('~https?://encrypted-tbn[0-9]+\\.gstatic\\.com/images\\?[^\"<>\s]+~i', $htmlGoogleNormalizado, $coincidenciasTbn)) {
                foreach (($coincidenciasTbn[0] ?? []) as $urlTbn) $urlsCandidatasGoogle[] = $urlTbn;
            }
            foreach ($urlsCandidatasGoogle as $urlImagenGoogle) {
                $urlImagenGoogle = rtrim(str_replace('\\/', '/', html_entity_decode($urlImagenGoogle, ENT_QUOTES | ENT_HTML5, 'UTF-8')), ',);');
                if (!filter_var($urlImagenGoogle, FILTER_VALIDATE_URL)) continue;
                if (preg_match('~(gstatic\.com/images\?q=tbn|googleusercontent\.com)~i', $urlImagenGoogle)
                    && !isset($imagenesGoogle[$urlImagenGoogle])) {
                    $imagenesGoogle[$urlImagenGoogle] = [
                        'url' => $urlImagenGoogle,
                        'credito' => 'Resultado de Google Imágenes para: ' . $query,
                        'fuente' => $googleUrl
                    ];
                }
                if (count($imagenesGoogle) >= 20) break;
            }
        }
        if ($imagenesGoogle) {
            echo json_encode(['ok' => true, 'images' => array_values($imagenesGoogle), 'source' => 'google-images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $fotosCommons = buscarFotosWikimediaCommons($query, 12);
        if ($fotosCommons) {
            echo json_encode(['ok' => true, 'configured' => false, 'images' => $fotosCommons, 'source' => 'wikimedia-commons', 'message' => 'Google no estuvo disponible; se usó Wikimedia Commons.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        http_response_code(503);
        echo json_encode(['ok' => false, 'configured' => false, 'images' => [], 'message' => 'Google y Wikimedia Commons no devolvieron imágenes en este momento.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $apiUrl = 'https://www.googleapis.com/customsearch/v1?' . http_build_query([
        'key' => $apiKey, 'cx' => $searchEngineId, 'q' => $query . ' Cusco Perú',
        'searchType' => 'image', 'num' => 10, 'safe' => 'active', 'imgType' => 'photo'
    ]);
    $respuesta = false;
    $codigoHttp = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12]);
        $respuesta = curl_exec($ch);
        $codigoHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $contexto = stream_context_create(['http' => ['timeout' => 12, 'ignore_errors' => true]]);
        $respuesta = @file_get_contents($apiUrl, false, $contexto);
        $codigoHttp = is_string($respuesta) ? 200 : 502;
    }
    $datos = is_string($respuesta) ? json_decode($respuesta, true) : null;
    if ($codigoHttp < 200 || $codigoHttp >= 300 || !is_array($datos)) {
        $fotosCommons = buscarFotosWikimediaCommons($query, 12);
        if ($fotosCommons) {
            echo json_encode(['ok' => true, 'images' => $fotosCommons, 'source' => 'wikimedia-commons', 'message' => 'Se usó Wikimedia Commons como alternativa.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        http_response_code(502);
        echo json_encode(['ok' => false, 'images' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $imagenes = [];
    foreach (($datos['items'] ?? []) as $item) {
        $urlImagen = (string)($item['link'] ?? '');
        if (preg_match('~^https?://~i', $urlImagen)) {
            $imagenes[] = ['url' => $urlImagen, 'credito' => (string)($item['title'] ?? $query), 'fuente' => (string)($item['image']['contextLink'] ?? $item['link'] ?? '')];
        }
    }
    echo json_encode(['ok' => true, 'images' => $imagenes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function h($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function postTexto(string $campo, int $maximo = 5000): string {
    $valor = trim((string)($_POST[$campo] ?? ''));
    return mb_substr($valor, 0, $maximo, 'UTF-8');
}

function postLista(string $campo): array {
    $valor = $_POST[$campo] ?? [];
    if (!is_array($valor)) {
        return [];
    }
    return array_values(array_filter(array_map(
        fn($v) => mb_substr(trim((string)$v), 0, 250, 'UTF-8'),
        $valor
    )));
}

if (empty($_SESSION['token_ideas'])) {
    $_SESSION['token_ideas'] = bin2hex(random_bytes(32));
}

$tokenCSRF = $_SESSION['token_ideas'];
$error = '';

/*
|--------------------------------------------------------------------------
| BASE DE DATOS SQL
|--------------------------------------------------------------------------
*/
$sqlTabla = "
CREATE TABLE IF NOT EXISTS planes_emprendimiento (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT NULL,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    idea_seleccionada VARCHAR(200) NOT NULL,
    datos_json LONGTEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_usuario (usuario_id),
    KEY idx_creado (creado_en),
    KEY idx_idea (idea_seleccionada(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1001;
";
$conn->query($sqlTabla);

/*
|--------------------------------------------------------------------------
| PROCESAMIENTO Y GUARDADO DEL FORMULARIO
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenEnviado = (string)($_POST['token_csrf'] ?? '');

    if (!hash_equals($tokenCSRF, $tokenEnviado)) {
        $error = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } else {
        $nombre = postTexto('nombre', 150);
        $email = postTexto('email', 190);
        $ideaSeleccionada = postTexto('idea_seleccionada', 200);

        if ($nombre === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresa tu nombre completo y un correo electrónico válido.';
        } elseif ($ideaSeleccionada === '') {
            $error = 'Selecciona una idea de negocio en el Paso 3 antes de guardar.';
        } else {
            $camposTexto = [
                'edad', 'genero', 'celular', 'ciudad', 'departamento', 'provincia', 'distrito',
                'colegio', 'grado', 'experiencia', 'objetivo', 'opinion',
                'categoria_personalizada', 'rubro_otro_texto', 'problema', 'propuesta_valor',
                'cliente_objetivo', 'canales', 'relacion_clientes', 'fuentes_ingreso',
                'recursos', 'actividades', 'socios', 'costos_fijos', 'costos_variables',
                'precio_venta', 'ventas_estimadas', 'inversion_inicial',
                'fortalezas', 'oportunidades', 'debilidades', 'amenazas',
                'responsabilidades', 'acciones', 'fecha_inicio',
                'producto_servicio', 'necesidad_cliente', 'competidores', 'oportunidad_mercado',
                'estrategia_producto', 'estrategia_precio', 'estrategia_plaza', 'estrategia_promocion',
                'estrategia_personal', 'estrategia_proceso', 'estrategia_presentacion',
                'puesto_1', 'sueldo_1', 'puesto_2', 'sueldo_2', 'puesto_3', 'sueldo_3',
                'unidades_mes', 'costo_empaque_unitario', 'mano_obra_unitaria', 'otros_variables_unitarios',
                'alquiler_mensual', 'servicios_mensuales', 'marketing_mensual', 'transporte_mensual',
                'otros_fijos_mensuales', 'margen_ganancia', 'impuesto_porcentaje',
                'inversion_equipos', 'inversion_maquinaria', 'inversion_muebles',
                'stock_inicial', 'permisos_iniciales', 'publicidad_lanzamiento', 'reserva_contingencia',
                'financiamiento', 'meses_proyeccion', 'crecimiento_mensual',
                'resumen_ejecutivo', 'mision', 'vision', 'objetivo_general', 'objetivos_especificos',
                'canvas_problema', 'canvas_clientes', 'canvas_propuesta', 'canvas_solucion', 'canvas_canales', 'canvas_ingresos', 'canvas_costos', 'canvas_metricas', 'canvas_ventaja',
                'plan_operaciones', 'fecha_inicio', 'responsable_plan'
            ];

            $datos = [];
            foreach ($camposTexto as $campo) {
                $datos[$campo] = postTexto($campo);
            }

            $camposLista = [
                'intereses', 'habilidades', 'recursos_disponibles',
                'tiempo_disponible', 'modalidad_venta', 'inversion_deseada',
                'motivaciones', 'canales_preferidos', 'zonas_cusco', 'tipo_apoyo',
                'segmentos_clientes', 'canales_venta', 'recursos_disponibles_plan', 'alianzas_clave'
            ];
            foreach ($camposLista as $campo) {
                $datos[$campo] = postLista($campo);
            }

            $datos['nombre'] = $nombre;
            $datos['email'] = $email;
            $datos['idea_seleccionada'] = $ideaSeleccionada;
            $datos['rol'] = postTexto('rol', 50);
            $datos['rubro_seleccionado'] = postTexto('rubro_seleccionado', 250);
            for ($i = 2; $i <= 10; $i++) {
                $datos['p' . $i] = postLista('p' . $i);
            }

            $usuarioId = $usuarioIdSesion;
            $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                $error = 'Error al procesar la información del formulario.';
            } else {
                $stmt = $conn->prepare("INSERT INTO planes_emprendimiento (usuario_id, nombre, email, idea_seleccionada, datos_json) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('issss', $usuarioId, $nombre, $email, $ideaSeleccionada, $json);
                    if ($stmt->execute()) {
                        $nuevoId = $stmt->insert_id;
                        $_SESSION['token_ideas'] = bin2hex(random_bytes(32));
                        $stmt->close();
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = 'Error al registrar en la base de datos: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Error de conexión MySQL: ' . $conn->error;
                }
            }
        }
    }
}

$pageTitle = 'Generador de Planes de Negocio Escolar | Emprende Cusco';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Carga de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --primary: #8b0000;
    --primary-dark: #650000;
    --gold: #d4af37;
    --bg-grey: #f4f6f9;
    --border-color: #d1d5db;
    --text-main: #1f2937;
}

body { background-color: var(--bg-grey); color: var(--text-main); font-family: system-ui, -apple-system, sans-serif; }
.emprende-container { max-width: 1100px; margin: 25px auto 60px; padding: 0 15px; }

.hero-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white; padding: 35px 25px; border-radius: 16px;
    box-shadow: 0 8px 20px rgba(139, 0, 0, 0.2); text-align: center; margin-bottom: 25px;
}
.hero-header h1 { font-size: 2.2rem; margin: 0 0 10px; font-weight: 800; }

.pasos-nav { display: flex; gap: 8px; margin-bottom: 25px; overflow-x: auto; }
.pasos-nav button {
    flex: 1; min-width: 130px; padding: 12px 6px; border: 2px solid var(--border-color);
    background: white; color: #4b5563; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
}
.pasos-nav button.activo { background: var(--primary); border-color: var(--primary); color: white; }

.paso-block {
    display: none; background: white; padding: 30px; border-radius: 16px;
    border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.03); margin-bottom: 20px;
}
.paso-block.activo { display: block; }
.paso-block h2 { color: var(--primary); font-size: 1.4rem; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; margin-top: 0; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }
.campo { margin-bottom: 18px; }
.campo label, .campo-titulo { display: block; font-weight: 700; margin-bottom: 8px; font-size: 0.92rem; color: #111827; }
.campo input, .campo select, .campo textarea {
    width: 100%; padding: 11px 13px; border: 1.5px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;
}

.opciones-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
.opcion-card {
    display: flex; align-items: center; gap: 10px; background: #f9fafb;
    border: 1px solid var(--border-color); padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 0.88rem;
}
.opcion-card input[type="radio"], .opcion-card input[type="checkbox"] { accent-color: var(--primary); width: 18px; height: 18px; }

.idea-galeria { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; padding: 6px; background: #e2e8f0; border-radius: 8px; }
.idea-foto { display: block; aspect-ratio: 16/10; overflow: hidden; border-radius: 6px; }
.idea-foto img { width: 100%; height: 100%; object-fit: cover; }

.costos-box-container {
    background: #ffffff; border: 2.5px dashed var(--primary); border-radius: 14px;
    padding: 22px; margin: 25px 0; box-shadow: 0 6px 16px rgba(139, 0, 0, 0.06);
}
.costos-grid-resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.costo-item-card { background: #f9fafb; border: 1px solid var(--border-color); padding: 12px; border-radius: 10px; }
.costo-item-card.total { background: #fff0f0; border-color: var(--primary); }

.documento-papel {
    background: white; border: 1px solid #cbd5e1; box-shadow: 0 12px 28px rgba(0,0,0,0.12);
    padding: 40px; border-radius: 4px; max-width: 850px; margin: 20px auto;
    font-family: 'Georgia', serif; color: #1e293b; line-height: 1.6;
}
.doc-header { text-align: center; border-bottom: 3px double var(--primary); padding-bottom: 15px; margin-bottom: 25px; }

.nav-buttons { display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; }
.btn { padding: 12px 28px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; }
.btn-prev { background: #e5e7eb; color: #374151; }
.btn-next, .btn-submit { background: var(--primary); color: white; }

/* Catálogo documentado de emprendimientos reales */
.catalogo-intro{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;margin:12px 0 18px;color:#7c2d12}
.idea-card{display:flex;flex-direction:column;gap:8px;transition:transform .18s ease,box-shadow .18s ease}
.idea-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(15,23,42,.10)}
.idea-sector{display:inline-flex;align-self:flex-start;background:#fee2e2;color:#7f1d1d;border-radius:20px;padding:4px 10px;font-size:.74rem;font-weight:800}
.idea-acciones{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:auto}
.idea-acciones .btn{padding:10px 8px;font-size:.82rem}
.idea-foto img{transition:transform .2s ease}.idea-foto:hover img{transform:scale(1.025)}
.modal-emprendimiento{display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.72);padding:18px;overflow-y:auto;align-items:center;justify-content:center}
.modal-emprendimiento.abierto{display:flex}
.modal-contenido{width:min(780px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:16px;padding:24px;box-shadow:0 25px 70px rgba(0,0,0,.3)}
.modal-cabecera{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin-bottom:15px}
.modal-cerrar{border:0;background:#fee2e2;color:#991b1b;border-radius:9px;padding:8px 12px;font-weight:800;cursor:pointer}
.modal-fuentes{background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:10px;margin-top:15px}
.modal-fuentes a{display:inline-block;margin:5px 12px 5px 0;color:#1d4ed8;text-decoration:underline}
@media(max-width:560px){.idea-acciones{grid-template-columns:1fr}.modal-contenido{padding:16px}}

</style>

<main class="emprende-container">

    <header class="hero-header">
        <h1> Generador de Ideas de Negocio Escolar - Cusco </h1>
        <p>Selecciona tu rubro en la Pregunta 1 para obtener 10 ideas reales adaptadas a tu elección en Cusco</p>
    </header>

    <?php if ($error !== ''): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            ⚠️ <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form id="formEmprendimiento" method="POST" action="">
        <input type="hidden" name="token_csrf" value="<?= h($tokenCSRF) ?>">
        <input type="hidden" name="idea_seleccionada" id="ideaSeleccionadaPOST" value="<?= h($_POST['idea_seleccionada'] ?? '') ?>">

        <nav class="pasos-nav">
            <button type="button" data-ir="0" class="activo">1. Perfil</button>
            <button type="button" data-ir="1">2. Test</button>
            <button type="button" data-ir="2">3. Catálogo</button>
            <button type="button" data-ir="3">4. Propuesta</button>
            <button type="button" data-ir="4">5. Costos y equilibrio</button>
            <button type="button" data-ir="5">6. Lienzo Canva</button>
            <button type="button" data-ir="6">7. Inversión y equipo</button>
            <button type="button" data-ir="7">8. Plan empresarial</button>
            <button type="button" data-ir="8">9. Resultados y guardar</button>
        </nav>

        <!-- PASO 1 -->
        <section class="paso-block activo" data-paso="0">
            <h2>1. Perfil Completo del Estudiante / Emprendedor</h2>
            <div class="form-grid">
                <div class="campo">
                    <label for="nombre">Nombre Completo *</label>
                    <input id="nombre" name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>" placeholder="Ej: Juan Quispe">
                </div>
                <div class="campo">
                    <label for="email">Correo Electrónico *</label>
                    <input id="email" name="email" type="email" required value="<?= h($_POST['email'] ?? '') ?>" placeholder="estudiante@colegio.edu.pe">
                </div>
                <div class="campo">
                    <label for="celular">Celular / WhatsApp *</label>
                    <input id="celular" name="celular" required value="<?= h($_POST['celular'] ?? '') ?>" placeholder="987654321">
                </div>
                <div class="campo">
                    <label for="edad">Edad *</label>
                    <input id="edad" name="edad" type="number" min="10" max="18" required value="<?= h($_POST['edad'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="genero">Género *</label>
                    <select id="genero" name="genero" required>
                        <option value="">-- Selecciona género --</option>
                        <option value="Hombre" <?= (($_POST['genero'] ?? '') === 'Hombre') ? 'selected' : '' ?>>Hombre</option>
                        <option value="Mujer" <?= (($_POST['genero'] ?? '') === 'Mujer') ? 'selected' : '' ?>>Mujer</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="rol">Perfil </label>
                    <select id="rol" name="rol" required>
                        <option value="">-- Selecciona perfil --</option>
                        <option value="Estudiante de secundaria" <?= (($_POST['rol'] ?? '') === 'Estudiante de secundaria') ? 'selected' : '' ?>>Estudiante de secundaria</option>
                        <option value="Egresado del colegio" <?= (($_POST['rol'] ?? '') === 'Egresado del colegio') ? 'selected' : '' ?>>Egresado del colegio</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="colegio">Colegio / Institución de Cusco *</label>
                    <select id="colegio" name="colegio" required>
                        <option value="">-- Selecciona tu colegio o institución de Cusco --</option>
                        <optgroup label="Cusco ciudad y alrededores">
                            <option>I.E. Emblemática Inca Garcilaso de la Vega</option>
                            <option>I.E. Emblemática Clorinda Matto de Turner</option>
                            <option>Glorioso Colegio Nacional de Ciencias</option>
                            <option>Colegio Salesiano del Cusco</option>
                            <option>Colegio San Francisco de Asís</option>
                            <option>Colegio La Merced</option>
                            <option>I.E. Educandas</option>
                            <option>I.E. María Auxiliadora</option>
                            <option>I.E. San Antonio Abad</option>
                            <option>I.E. Santa Rosa de Lima</option>
                            <option>I.E. San José La Salle</option>
                            <option>I.E. Alejandro Velasco Astete</option>
                            <option>I.E. Viva el Perú</option>
                            <option>I.E. General Ollanta</option>
                            <option>I.E. Uriel García</option>
                            <option>I.E. Diego Quispe Tito</option>
                            <option>I.E. Fe y Alegría N.° 21</option>
                            <option>I.E. Nuestra Señora de Fátima</option>
                        </optgroup>
                        <optgroup label="Provincia de Acomayo">
                            <option>I.E. Inca Garcilaso de la Vega - Acomayo</option>
                            <option>Otra I.E. de Acomayo</option>
                            <option>Otra I.E. de Pomacanchi</option>
                        </optgroup>
                        <optgroup label="Provincia de Anta">
                            <option>I.E. de Anta (Izcuchaca)</option>
                            <option>I.E. de Limatambo</option>
                            <option>I.E. de Huarocondo</option>
                            <option>I.E. de Zurite</option>
                            <option>Otra I.E. de la provincia de Anta</option>
                        </optgroup>
                        <optgroup label="Provincia de Calca">
                            <option>I.E. de Calca</option>
                            <option>I.E. de Pisac</option>
                            <option>I.E. de Lamay</option>
                            <option>I.E. de Lares</option>
                            <option>I.E. de Yanatile</option>
                            <option>Otra I.E. de la provincia de Calca</option>
                        </optgroup>
                        <optgroup label="Provincia de Canas">
                            <option>I.E. de Yanaoca</option>
                            <option>I.E. de Checca</option>
                            <option>I.E. de Langui</option>
                            <option>I.E. de Layo</option>
                            <option>Otra I.E. de la provincia de Canas</option>
                        </optgroup>
                        <optgroup label="Provincia de Canchis">
                            <option>I.E. Mateo Pumacahua - Sicuani</option>
                            <option>I.E. de Combapata</option>
                            <option>I.E. de Tinta</option>
                            <option>I.E. de San Pedro</option>
                            <option>I.E. de Marangani</option>
                            <option>I.E. de Pitumarca</option>
                            <option>Otra I.E. de la provincia de Canchis</option>
                        </optgroup>
                        <optgroup label="Provincia de Chumbivilcas">
                            <option>I.E. de Santo Tomás</option>
                            <option>I.E. de Velille</option>
                            <option>I.E. de Livitaca</option>
                            <option>I.E. de Colquemarca</option>
                            <option>I.E. de Chamaca</option>
                            <option>Otra I.E. de la provincia de Chumbivilcas</option>
                        </optgroup>
                        <optgroup label="Provincia de Espinar">
                            <option>I.E. de Yauri - Espinar</option>
                            <option>I.E. de Coporaque</option>
                            <option>I.E. de Pallpata</option>
                            <option>I.E. de Pichigua</option>
                            <option>Otra I.E. de la provincia de Espinar</option>
                        </optgroup>
                        <optgroup label="Provincia de La Convención">
                            <option>I.E. Manco II - Quillabamba</option>
                            <option>I.E. de Santa Ana - Quillabamba</option>
                            <option>I.E. de Echarate</option>
                            <option>I.E. de Pichari</option>
                            <option>I.E. de Kimbiri</option>
                            <option>I.E. de Santa Teresa</option>
                            <option>I.E. de Maranura</option>
                            <option>Otra I.E. de la provincia de La Convención</option>
                        </optgroup>
                        <optgroup label="Provincia de Paruro">
                            <option>I.E. de Paruro</option>
                            <option>I.E. de Accha</option>
                            <option>I.E. de Huanoquite</option>
                            <option>I.E. de Omacha</option>
                            <option>Otra I.E. de la provincia de Paruro</option>
                        </optgroup>
                        <optgroup label="Provincia de Paucartambo">
                            <option>I.E. de Paucartambo</option>
                            <option>I.E. de Colquepata</option>
                            <option>I.E. de Challabamba</option>
                            <option>I.E. de Huancarani</option>
                            <option>I.E. de Kosñipata</option>
                            <option>Otra I.E. de la provincia de Paucartambo</option>
                        </optgroup>
                        <optgroup label="Provincia de Quispicanchi">
                            <option>I.E. de Urcos</option>
                            <option>I.E. de Andahuaylillas</option>
                            <option>I.E. de Ocongate</option>
                            <option>I.E. de Quiquijana</option>
                            <option>I.E. de Oropesa</option>
                            <option>I.E. de Ccatca</option>
                            <option>Otra I.E. de la provincia de Quispicanchi</option>
                        </optgroup>
                        <optgroup label="Provincia de Urubamba">
                            <option>I.E. de Urubamba</option>
                            <option>I.E. de Chinchero</option>
                            <option>I.E. de Huayllabamba</option>
                            <option>I.E. de Machupicchu</option>
                            <option>I.E. de Maras</option>
                            <option>I.E. de Ollantaytambo</option>
                            <option>Otra I.E. de la provincia de Urubamba</option>
                        </optgroup>
                        <option value="Otro colegio de la región Cusco">Otro colegio de la región Cusco (especificar en el siguiente campo)</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="grado">Grado y Sección *</label>
                    <input id="grado" name="grado" required value="<?= h($_POST['grado'] ?? '') ?>" placeholder="Ej: 4to B de Secundaria">
                </div>
                <div class="campo">
                    <label for="ciudad">Ciudad / localidad de Cusco *</label>
                    <input id="ciudad" name="ciudad" required maxlength="100" value="<?= h($_POST['ciudad'] ?? 'Cusco') ?>" placeholder="Ej: Cusco">
                </div>
                <div class="campo">
                    <label for="provincia">Provincia de Cusco *</label>
                    <select id="provincia" name="provincia" required>
                        <option value="">-- Selecciona provincia --</option>
                        <?php foreach (['Acomayo','Anta','Calca','Canas','Canchis','Chumbivilcas','Cusco','Espinar','La Convención','Paruro','Paucartambo','Quispicanchi','Urubamba'] as $prov): ?>
                            <option value="<?= h($prov) ?>" <?= (($_POST['provincia'] ?? '') === $prov) ? 'selected' : '' ?>><?= h($prov) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="distrito">Distrito de Cusco *</label>
                    <select id="distrito" name="distrito" required disabled>
                        <option value="">Selecciona primero una provincia</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- PASO 2: TEST -->
        <section class="paso-block" data-paso="1">
            <h2>2. Test Emprendedor Escolar (Responde las 10 preguntas *)</h2>
            <p style="color: var(--primary); font-weight: bold; margin-bottom: 20px;">
                📌 Elige un rubro o escribe tu propia idea. El catálogo presenta 10 emprendimientos reales de Cusco como casos de referencia.
            </p>

            <div class="campo" data-pregunta="1">
                <span class="campo-titulo">1. ¿Qué rubro o especialidad te interesa promover? (Pregunta 1 *)</span>
                <div class="opciones-grid">
                    <?php
                    $opcionesRubro = [
                        'Gastronomía y repostería',
                        'Artesanía y manualidades',
                        'Turismo y cultura cusqueña',
                        'Tecnología y servicios digitales',
                        'Otra idea (especificar)'
                    ];
                    foreach ($opcionesRubro as $op): ?>
                        <label class="opcion-card">
                            <input
                                type="radio"
                                name="rubro_seleccionado"
                                value="<?= h($op) ?>"
                                <?= (($_POST['rubro_seleccionado'] ?? '') === $op) ? 'checked' : '' ?>
                            >
                            <strong><?= h($op) ?></strong>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="campo" id="campoRubroOtro" style="display:none;margin-top:12px">
                    <label for="rubro_otro_texto">Escribe tu propia idea de negocio *</label>
                    <textarea id="rubro_otro_texto" name="rubro_otro_texto" rows="3" maxlength="250" placeholder="Ej.: elaboración de miel, aplicación para turismo comunitario, cuidado de mascotas..." aria-describedby="ayudaRubroOtro"><?= h($_POST['rubro_otro_texto'] ?? '') ?></textarea>
                    <small id="ayudaRubroOtro" style="display:block;color:#64748b;margin-top:6px;">Escribe una idea concreta si ninguna de las opciones anteriores representa lo que deseas emprender.</small>
                </div>
            </div>

            <?php
            $preguntasExtra = [
                2 => ['¿Cuáles son tus habilidades principales?', ['Atención al cliente', 'Cocina y repostería', 'Manualidades y costura', 'Uso de tecnología', 'Organización y ventas', 'Reparación y mecánica']],
                3 => ['¿Con qué recursos cuentas?', ['Espacio disponible en casa', 'Conexión a internet y celular', 'Herramientas básicas', 'Ahorros propios', 'Apoyo de familiares']],
                4 => ['¿Tiempo disponible para emprender?', ['Fines de semana', '1 a 2 horas al día', 'Medio tiempo', 'Vacaciones escolares']],
                5 => ['¿Modalidad de venta preferida?', ['Local comercial o taller', 'Ventas por redes sociales / WhatsApp', 'A domicilio en Cusco', 'Ferias y colegios']],
                6 => ['¿Presupuesto inicial estimado?', ['Menos de S/ 100', 'S/ 100 a S/ 300', 'S/ 300 a S/ 600', 'Más de S/ 600']],
                7 => ['¿Tu principal motivación?', ['Generar mis propios ingresos', 'Aprender un oficio útil', 'Apoyar en mi hogar', 'Promover el desarrollo de Cusco']],
                8 => ['¿Canal de publicidad preferido?', ['Facebook e Instagram', 'WhatsApp y boca a boca', 'Afiches en la zona', 'TikTok']],
                9 => ['¿Zona preferida de operación en Cusco?', ['Wanchaq / San Sebastián', 'San Jerónimo / Santiago', 'Centro Histórico', 'Por internet']],
                10 => ['¿Tipo de apoyo que más necesitas?', ['Finanzas y costos', 'Diseño de marca / Logo', 'Estrategias de venta', 'Contactos de proveedores']]
            ];
            foreach ($preguntasExtra as $num => $info): ?>
                <div class="campo" data-pregunta="<?= $num ?>">
                    <span class="campo-titulo"><?= $num ?>. <?= $info[0] ?> *</span>
                    <div class="opciones-grid">
                        <?php foreach ($info[1] as $op): ?>
                            <label class="opcion-card"><input type="checkbox" name="p<?= $num ?>[]" value="<?= h($op) ?>"> <?= h($op) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- PASO 3: CATÁLOGO DE 10 IDEAS -->
        <section class="paso-block" data-paso="2">
            <h2>3. Catálogo de 10 Emprendimientos Reales de Cusco</h2>
            <p id="msgTestAnalizado" style="color: var(--primary); font-weight: bold; margin-bottom: 15px;">
                Cargando 10 emprendimientos reales de Cusco...
            </p>

            <div class="catalogo-intro"><strong>Casos reales de Cusco:</strong> explora 10 negocios y proyectos documentados. Pulsa “Conocer su historia” para leer cómo comenzaron y consultar la fuente original. Las fotografías se cargan desde páginas web y artículos que documentan cada emprendimiento; cada imagen incluye el enlace de su fuente original.</div>
            <div id="gridIdeasCatalogo" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;"></div>
            <div id="modalEmprendimiento" class="modal-emprendimiento" role="dialog" aria-modal="true" aria-labelledby="modalTituloEmprendimiento">
                <div class="modal-contenido">
                    <div class="modal-cabecera"><div><span id="modalSectorEmprendimiento" class="idea-sector"></span><h2 id="modalTituloEmprendimiento" style="margin:8px 0 0;color:#8b0000"></h2><p id="modalUbicacionEmprendimiento" style="margin:5px 0;color:#64748b"></p></div><button type="button" class="modal-cerrar" id="cerrarModalEmprendimiento">✕ Cerrar</button></div>
                    <div id="modalHistoriaEmprendimiento"></div>
                    <div id="modalFuentesEmprendimiento" class="modal-fuentes"></div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:18px"><button type="button" id="btnUsarEmprendimiento" class="btn btn-next">Elegir como referencia para mi plan</button><button type="button" id="btnCerrarModalAbajo" class="btn btn-prev">Volver al catálogo</button></div>
                </div>
            </div>
            <p id="txtIdeaSeleccionada" style="text-align: center; margin-top: 15px; font-weight: bold; color: var(--primary); font-size: 1.1rem;"></p>
        </section>

        <!-- PASO 4 -->
        <section class="paso-block" data-paso="3">
            <h2>4. Tu Propuesta de Valor Personalizada</h2>
            <div class="campo">
                <label for="opinion">¿Por qué elegiste esta idea y cómo la adaptarás?</label>
                <textarea id="opinion" name="opinion" rows="3" placeholder="Ej: Desarrollaré este proyecto en mi zona aprovechando la demanda local..."></textarea>
            </div>
            <div class="campo">
                <label for="cliente_objetivo">¿Quiénes son tus clientes?</label>
                <textarea id="cliente_objetivo" name="cliente_objetivo" rows="2" placeholder="Ej: Estudiantes, familias y negocios locales de Cusco."></textarea>
            </div>
        </section>

        <!-- PASO 5: COSTOS, PRECIO Y PUNTO DE EQUILIBRIO -->
        <section class="paso-block" data-paso="4">
            <h2>5. Costos, precio de venta y punto de equilibrio</h2>
            <p>Completa los importes estimados para la idea que seleccionaste. Los cálculos son aproximaciones de planificación y puedes editarlos.</p>
            <div class="form-grid">
                <div class="campo">
                    <label for="unidades_mes">Unidades o servicios que producirás al mes</label>
                    <input type="number" min="1" step="1" id="unidades_mes" name="unidades_mes" value="<?= h($_POST['unidades_mes'] ?? '100') ?>">
                </div>
                <div class="campo">
                    <label for="precio_venta">Precio de venta por unidad (S/)</label>
                    <input type="number" min="0" step="0.01" id="precio_venta" name="precio_venta" value="<?= h($_POST['precio_venta'] ?? '10') ?>">
                </div>
                <div class="campo">
                    <label for="costos_variables">Materia prima / costo variable por unidad (S/)</label>
                    <input type="number" min="0" step="0.01" id="costos_variables" name="costos_variables" value="<?= h($_POST['costos_variables'] ?? '3') ?>">
                </div>
                <div class="campo">
                    <label for="costo_empaque_unitario">Empaque o materiales por unidad (S/)</label>
                    <input type="number" min="0" step="0.01" id="costo_empaque_unitario" name="costo_empaque_unitario" value="<?= h($_POST['costo_empaque_unitario'] ?? '0.50') ?>">
                </div>
                <div class="campo">
                    <label for="mano_obra_unitaria">Mano de obra directa por unidad (S/)</label>
                    <input type="number" min="0" step="0.01" id="mano_obra_unitaria" name="mano_obra_unitaria" value="<?= h($_POST['mano_obra_unitaria'] ?? '0') ?>">
                </div>
                <div class="campo">
                    <label for="otros_variables_unitarios">Otros costos variables por unidad (S/)</label>
                    <input type="number" min="0" step="0.01" id="otros_variables_unitarios" name="otros_variables_unitarios" value="<?= h($_POST['otros_variables_unitarios'] ?? '0') ?>">
                </div>
                <div class="campo">
                    <label for="costos_fijos">Costos fijos mensuales totales (S/)</label>
                    <input type="number" min="0" step="0.01" id="costos_fijos" name="costos_fijos" value="<?= h($_POST['costos_fijos'] ?? '300') ?>">
                </div>
                <div class="campo">
                    <label for="alquiler_mensual">Alquiler mensual (S/)</label>
                    <input type="number" min="0" step="0.01" id="alquiler_mensual" name="alquiler_mensual" value="<?= h($_POST['alquiler_mensual'] ?? '0') ?>">
                </div>
                <div class="campo">
                    <label for="servicios_mensuales">Agua, luz, internet y servicios (S/)</label>
                    <input type="number" min="0" step="0.01" id="servicios_mensuales" name="servicios_mensuales" value="<?= h($_POST['servicios_mensuales'] ?? '50') ?>">
                </div>
                <div class="campo">
                    <label for="marketing_mensual">Publicidad mensual (S/)</label>
                    <input type="number" min="0" step="0.01" id="marketing_mensual" name="marketing_mensual" value="<?= h($_POST['marketing_mensual'] ?? '30') ?>">
                </div>
                <div class="campo">
                    <label for="transporte_mensual">Transporte y delivery mensual (S/)</label>
                    <input type="number" min="0" step="0.01" id="transporte_mensual" name="transporte_mensual" value="<?= h($_POST['transporte_mensual'] ?? '20') ?>">
                </div>
                <div class="campo">
                    <label for="otros_fijos_mensuales">Otros gastos fijos mensuales (S/)</label>
                    <input type="number" min="0" step="0.01" id="otros_fijos_mensuales" name="otros_fijos_mensuales" value="<?= h($_POST['otros_fijos_mensuales'] ?? '0') ?>">
                </div>
                <div class="campo">
                    <label for="margen_ganancia">Margen de ganancia deseado (%)</label>
                    <input type="number" min="0" max="300" step="1" id="margen_ganancia" name="margen_ganancia" value="<?= h($_POST['margen_ganancia'] ?? '30') ?>">
                </div>
                <div class="campo">
                    <label for="impuesto_porcentaje">Impuesto estimado (%)</label>
                    <input type="number" min="0" max="100" step="0.1" id="impuesto_porcentaje" name="impuesto_porcentaje" value="<?= h($_POST['impuesto_porcentaje'] ?? '0') ?>">
                </div>
                <div class="campo">
                    <label for="inversion_inicial">Inversión inicial estimada (S/)</label>
                    <input type="number" min="0" step="0.01" id="inversion_inicial" name="inversion_inicial" value="<?= h($_POST['inversion_inicial'] ?? '1000') ?>">
                </div>
                <div class="campo">
                    <label for="ventas_estimadas">Ventas mensuales estimadas (unidades)</label>
                    <input type="number" min="0" step="1" id="ventas_estimadas" name="ventas_estimadas" value="<?= h($_POST['ventas_estimadas'] ?? '100') ?>">
                </div>
            </div>

            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">📊 Indicadores financieros automáticos</h3>
                <div class="costos-grid-resumen">
                    <div class="costo-item-card"><span>Ingresos mensuales</span><strong id="resIngresos">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Costos variables totales</span><strong id="resCostosVar">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Costos fijos mensuales</span><strong id="resCostosFijos">S/ 0.00</strong></div>
                    <div class="costo-item-card total"><span>Utilidad / pérdida estimada</span><strong id="resGanancia">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Ganancia estimada (si es positiva)</span><strong id="resGananciaPositiva">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Pérdida estimada (si es negativa)</span><strong id="resPerdida">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Costo variable por unidad</span><strong id="resCostoVariableUnitario">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Margen contribución unitario</span><strong id="resMargenContribucion">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Punto de equilibrio (unidades/mes)</span><strong id="resPuntoUnidades">0</strong></div>
                    <div class="costo-item-card"><span>Punto de equilibrio (ventas S/)</span><strong id="resPuntoSoles">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Ganancia estimada por unidad</span><strong id="resGananciaUnidad">S/ 0.00</strong></div>
                </div>
            </div>

            <!-- GRÁFICO DINÁMICO DE PUNTO DE EQUILIBRIO -->
            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">📈 Gráfico Interactivo de Punto de Equilibrio</h3>
                <p style="font-size:0.9rem;color:#4b5563;margin-bottom:15px;">
                    El eje horizontal muestra las unidades de 10 en 10 (10, 20, 30, 40, etc.). La zona sombreada en <strong>verde representa las Ganancias</strong> y la zona sombreada en <strong>rojo representa las Pérdidas</strong>.
                </p>
                <div style="position: relative; width: 100%; min-height: 340px; max-height: 440px;">
                    <canvas id="chartEquilibrio"></canvas>
                </div>
            </div>

            <div class="costos-box-container" id="panelPuntoEquilibrio">
                <h3 style="margin-top:0;color:var(--primary)">Punto de equilibrio: ¿cuánto debes vender?</h3>
                <p id="textoPuntoEquilibrio">Calculando el punto de equilibrio...</p>
                <div class="costos-grid-resumen" style="margin-top:12px">
                    <div class="costo-item-card"><span>Resultado calculado</span><strong id="resEstadoFinanciero">Pendiente</strong></div>
                    <div class="costo-item-card"><span>Ganancia / pérdida en soles</span><strong id="resMontoResultado">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Diferencia frente al punto de equilibrio</span><strong id="resDiferenciaEquilibrio">No calculable</strong></div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin:12px 0">
                    <span style="background:#dcfce7;color:#166534;padding:8px 12px;border-radius:8px;font-weight:700">● GANANCIAS: ventas por encima del punto de equilibrio</span>
                    <span style="background:#fee2e2;color:#991b1b;padding:8px 12px;border-radius:8px;font-weight:700">● PÉRDIDAS: ventas por debajo del punto de equilibrio</span>
                    <span style="background:#fef3c7;color:#92400e;padding:8px 12px;border-radius:8px;font-weight:700">● EQUILIBRIO: ingresos iguales a costos</span>
                </div>
                <p style="font-size:.85rem;color:#64748b">El punto de equilibrio se calcula como costos fijos ÷ (precio de venta − costo variable unitario). Es una estimación; no garantiza ventas reales.</p>
            </div>
        </section>

        <!-- PASO 6: LIENZO CANVAS -->
        <section class="paso-block" data-paso="5">
            <h2>6. Lienzo Lean Canvas de tu emprendimiento</h2>
            <p>Resume en una página los elementos esenciales de tu idea. Puedes apoyarte en el catálogo y en tus cálculos de costos y punto de equilibrio.</p>
            <div class="form-grid">
                <div class="campo"><label for="canvas_problema">1. Problema o necesidad</label><textarea id="canvas_problema" name="canvas_problema" rows="3" placeholder="¿Qué problema concreto de tu comunidad resolverás?"><?= h($_POST['canvas_problema'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_clientes">2. Segmento de clientes</label><textarea id="canvas_clientes" name="canvas_clientes" rows="3" placeholder="¿Quiénes tienen ese problema y podrían pagar?"><?= h($_POST['canvas_clientes'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_propuesta">3. Propuesta de valor única</label><textarea id="canvas_propuesta" name="canvas_propuesta" rows="3" placeholder="¿Qué beneficio te diferencia?"><?= h($_POST['canvas_propuesta'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_solucion">4. Solución</label><textarea id="canvas_solucion" name="canvas_solucion" rows="3" placeholder="¿Qué producto o servicio ofrecerás?"><?= h($_POST['canvas_solucion'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_canales">5. Canales</label><textarea id="canvas_canales" name="canvas_canales" rows="3" placeholder="¿Cómo llegarás a tus clientes?"><?= h($_POST['canvas_canales'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_ingresos">6. Fuentes de ingresos</label><textarea id="canvas_ingresos" name="canvas_ingresos" rows="3" placeholder="¿Por qué productos/servicios y cómo te pagarán?"><?= h($_POST['canvas_ingresos'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_costos">7. Estructura de costos</label><textarea id="canvas_costos" name="canvas_costos" rows="3" placeholder="Incluye los costos fijos y variables calculados."><?= h($_POST['canvas_costos'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_metricas">8. Métricas clave</label><textarea id="canvas_metricas" name="canvas_metricas" rows="3" placeholder="Ej.: clientes por mes, ventas, satisfacción, unidades para alcanzar equilibrio."><?= h($_POST['canvas_metricas'] ?? '') ?></textarea></div>
                <div class="campo"><label for="canvas_ventaja">9. Ventaja diferencial</label><textarea id="canvas_ventaja" name="canvas_ventaja" rows="3" placeholder="¿Qué será difícil de copiar o qué vínculo local te diferencia?"><?= h($_POST['canvas_ventaja'] ?? '') ?></textarea></div>
            </div>
            <p style="background:#fff7ed;padding:12px;border-radius:8px">Nota: el lienzo Lean Canvas organiza hipótesis del negocio; valida los datos con posibles clientes antes de invertir.</p>
        </section>

        <!-- PASO 7: INVERSION, PERSONAL Y COMERCIALIZACION -->
        <section class="paso-block" data-paso="6">
            <h2>7. Capital inicial, equipo y comercialización</h2>
            <h3>6.1 Inversión inicial por rubro (S/)</h3>
            <div class="form-grid">
                <div class="campo"><label for="inversion_equipos">Equipos (computadora, herramientas, etc.)</label><input type="number" min="0" step="0.01" id="inversion_equipos" name="inversion_equipos" value="<?= h($_POST['inversion_equipos'] ?? '0') ?>"></div>
                <div class="campo"><label for="inversion_maquinaria">Maquinaria y equipos de producción</label><input type="number" min="0" step="0.01" id="inversion_maquinaria" name="inversion_maquinaria" value="<?= h($_POST['inversion_maquinaria'] ?? '0') ?>"></div>
                <div class="campo"><label for="inversion_muebles">Muebles y enseres</label><input type="number" min="0" step="0.01" id="inversion_muebles" name="inversion_muebles" value="<?= h($_POST['inversion_muebles'] ?? '0') ?>"></div>
                <div class="campo"><label for="stock_inicial">Materia prima / mercadería inicial</label><input type="number" min="0" step="0.01" id="stock_inicial" name="stock_inicial" value="<?= h($_POST['stock_inicial'] ?? '0') ?>"></div>
                <div class="campo"><label for="permisos_iniciales">Licencias y trámites iniciales</label><input type="number" min="0" step="0.01" id="permisos_iniciales" name="permisos_iniciales" value="<?= h($_POST['permisos_iniciales'] ?? '0') ?>"></div>
                <div class="campo"><label for="publicidad_lanzamiento">Publicidad de lanzamiento</label><input type="number" min="0" step="0.01" id="publicidad_lanzamiento" name="publicidad_lanzamiento" value="<?= h($_POST['publicidad_lanzamiento'] ?? '0') ?>"></div>
                <div class="campo"><label for="reserva_contingencia">Reserva para imprevistos</label><input type="number" min="0" step="0.01" id="reserva_contingencia" name="reserva_contingencia" value="<?= h($_POST['reserva_contingencia'] ?? '0') ?>"></div>
                <div class="campo"><label for="financiamiento">Fuente de financiamiento</label><select id="financiamiento" name="financiamiento"><option value="Aporte propio">Aporte propio</option><option value="Familia">Apoyo familiar</option><option value="Préstamo">Préstamo</option><option value="Mixto">Mixto</option></select></div>
            </div>
            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">6.2 Distribución de funciones y costos de personal</h3>
                <div class="form-grid">
                    <div class="campo"><label for="puesto_1">Puesto 1 / responsabilidad</label><input id="puesto_1" name="puesto_1" value="<?= h($_POST['puesto_1'] ?? 'Administración y compras') ?>"></div>
                    <div class="campo"><label for="sueldo_1">Costo mensual del puesto 1 (S/)</label><input type="number" min="0" step="0.01" id="sueldo_1" name="sueldo_1" value="<?= h($_POST['sueldo_1'] ?? '0') ?>"></div>
                    <div class="campo"><label for="puesto_2">Puesto 2 / responsabilidad</label><input id="puesto_2" name="puesto_2" value="<?= h($_POST['puesto_2'] ?? 'Producción y calidad') ?>"></div>
                    <div class="campo"><label for="sueldo_2">Costo mensual del puesto 2 (S/)</label><input type="number" min="0" step="0.01" id="sueldo_2" name="sueldo_2" value="<?= h($_POST['sueldo_2'] ?? '0') ?>"></div>
                    <div class="campo"><label for="puesto_3">Puesto 3 / responsabilidad</label><input id="puesto_3" name="puesto_3" value="<?= h($_POST['puesto_3'] ?? 'Ventas y marketing') ?>"></div>
                    <div class="campo"><label for="sueldo_3">Costo mensual del puesto 3 (S/)</label><input type="number" min="0" step="0.01" id="sueldo_3" name="sueldo_3" value="<?= h($_POST['sueldo_3'] ?? '0') ?>"></div>
                </div>
            </div>
            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">6.3 Plan de comercialización: las 7 P</h3>
                <div class="form-grid">
                    <div class="campo"><label for="estrategia_producto">Producto / servicio</label><textarea id="estrategia_producto" name="estrategia_producto" rows="2" placeholder="Qué ofreces, presentación, calidad y características"><?= h($_POST['estrategia_producto'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_precio">Precio</label><textarea id="estrategia_precio" name="estrategia_precio" rows="2" placeholder="Precio, promociones y comparación con competidores"><?= h($_POST['estrategia_precio'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_plaza">Plaza / distribución</label><textarea id="estrategia_plaza" name="estrategia_plaza" rows="2" placeholder="Dónde venderás y cómo entregarás"><?= h($_POST['estrategia_plaza'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_promocion">Promoción</label><textarea id="estrategia_promocion" name="estrategia_promocion" rows="2" placeholder="Redes sociales, ferias, recomendaciones, volantes"><?= h($_POST['estrategia_promocion'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_personal">Personal</label><textarea id="estrategia_personal" name="estrategia_personal" rows="2" placeholder="Quién atenderá, elaborará y entregará"><?= h($_POST['estrategia_personal'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_proceso">Proceso</label><textarea id="estrategia_proceso" name="estrategia_proceso" rows="2" placeholder="Pasos para producir y entregar el producto o servicio"><?= h($_POST['estrategia_proceso'] ?? '') ?></textarea></div>
                    <div class="campo"><label for="estrategia_presentacion">Presentación física</label><textarea id="estrategia_presentacion" name="estrategia_presentacion" rows="2" placeholder="Local, empaque, uniforme, higiene, presentación de marca"><?= h($_POST['estrategia_presentacion'] ?? '') ?></textarea></div>
                </div>
            </div>
        </section>

        <!-- PASO 8: PLAN EMPRESARIAL -->
        <section class="paso-block" data-paso="7">
            <h2>8. Plan empresarial y validación de mercado</h2>
            <div class="campo"><label for="resumen_ejecutivo">Resumen ejecutivo del negocio</label><textarea id="resumen_ejecutivo" name="resumen_ejecutivo" rows="3" placeholder="Resume qué harás, dónde, para quién y cómo ganarás dinero"><?= h($_POST['resumen_ejecutivo'] ?? '') ?></textarea></div>
            <div class="form-grid">
                <div class="campo"><label for="producto_servicio">Producto o servicio principal</label><textarea id="producto_servicio" name="producto_servicio" rows="2" placeholder="Qué vas a ofrecer"><?= h($_POST['producto_servicio'] ?? '') ?></textarea></div>
                <div class="campo"><label for="necesidad_cliente">Necesidad que resolverás</label><textarea id="necesidad_cliente" name="necesidad_cliente" rows="2" placeholder="Qué problema o necesidad de tus clientes atenderás"><?= h($_POST['necesidad_cliente'] ?? '') ?></textarea></div>
                <div class="campo"><label for="competidores">Competidores existentes</label><textarea id="competidores" name="competidores" rows="2" placeholder="Anota negocios parecidos en tu zona"><?= h($_POST['competidores'] ?? '') ?></textarea></div>
                <div class="campo"><label for="oportunidad_mercado">Oportunidad / vacío de mercado</label><textarea id="oportunidad_mercado" name="oportunidad_mercado" rows="2" placeholder="Qué puedes ofrecer diferente"><?= h($_POST['oportunidad_mercado'] ?? '') ?></textarea></div>
                <div class="campo"><label for="mision">Misión</label><textarea id="mision" name="mision" rows="2" placeholder="Qué hace tu negocio y para quién"><?= h($_POST['mision'] ?? '') ?></textarea></div>
                <div class="campo"><label for="vision">Visión</label><textarea id="vision" name="vision" rows="2" placeholder="Qué quieres lograr en 3 a 5 años"><?= h($_POST['vision'] ?? '') ?></textarea></div>
                <div class="campo"><label for="objetivo_general">Objetivo general</label><textarea id="objetivo_general" name="objetivo_general" rows="2" placeholder="Resultado principal que buscas"><?= h($_POST['objetivo_general'] ?? '') ?></textarea></div>
                <div class="campo"><label for="objetivos_especificos">Objetivos específicos</label><textarea id="objetivos_especificos" name="objetivos_especificos" rows="2" placeholder="Metas medibles y con plazo"><?= h($_POST['objetivos_especificos'] ?? '') ?></textarea></div>
                <div class="campo"><label for="plan_operaciones">Plan de operaciones</label><textarea id="plan_operaciones" name="plan_operaciones" rows="3" placeholder="Cómo comprarás, producirás, revisarás calidad y entregarás"><?= h($_POST['plan_operaciones'] ?? '') ?></textarea></div>
            </div>
            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">Análisis FODA</h3>
                <div class="form-grid">
                    <div class="campo"><label for="fortalezas">Fortalezas</label><textarea id="fortalezas" name="fortalezas" rows="2" placeholder="Capacidades internas positivas"></textarea></div>
                    <div class="campo"><label for="oportunidades">Oportunidades</label><textarea id="oportunidades" name="oportunidades" rows="2" placeholder="Condiciones externas favorables"></textarea></div>
                    <div class="campo"><label for="debilidades">Debilidades</label><textarea id="debilidades" name="debilidades" rows="2" placeholder="Aspectos internos por mejorar"></textarea></div>
                    <div class="campo"><label for="amenazas">Amenazas</label><textarea id="amenazas" name="amenazas" rows="2" placeholder="Riesgos externos posibles"></textarea></div>
                </div>
            </div>
        </section>

        <!-- PASO 9: RESULTADOS Y DOCUMENTO FINAL -->
        <section class="paso-block" data-paso="8">
            <h2>9. Resultados, punto de equilibrio y guardar plan</h2>
            <div class="costos-box-container">
                <h3 style="margin-top:0;color:var(--primary)">Resumen del estado de resultados</h3>
                <div class="costos-grid-resumen">
                    <div class="costo-item-card"><span>Ventas mensuales estimadas</span><strong id="docVentas">0 unidades</strong></div>
                    <div class="costo-item-card"><span>Ingresos proyectados</span><strong id="docIngresos">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Costos variables</span><strong id="docCostosVariables">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Costos fijos</span><strong id="docCostosFijos">S/ 0.00</strong></div>
                    <div class="costo-item-card total"><span>Utilidad / pérdida mensual</span><strong id="docGanancia" style="color:var(--primary)">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Ganancia mensual (si es positiva)</span><strong id="docGananciaPositiva">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Pérdida mensual (si es negativa)</span><strong id="docPerdida">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Estado financiero</span><strong id="docEstadoFinanciero">Pendiente</strong></div>
                    <div class="costo-item-card"><span>Inversión inicial total desglosada</span><strong id="docInversionTotal">S/ 0.00</strong></div>
                    <div class="costo-item-card"><span>Recuperación simple de inversión</span><strong id="docRecuperacion">No calculable</strong></div>
                    <div class="costo-item-card"><span>Rentabilidad sobre ventas</span><strong id="docRentabilidad">0%</strong></div>
                </div>
                <h3 style="color:var(--primary)">Interpretación del punto de equilibrio</h3>
                <p id="docInterpretacionEquilibrio">Completa los costos y ventas para interpretar el resultado.</p>
                <p><strong>Leyenda:</strong> GANANCIAS = utilidad positiva; PÉRDIDAS = utilidad negativa; EQUILIBRIO = utilidad igual a cero aproximadamente.</p>
            </div>

            <div class="documento-papel">
                <div class="doc-header">
                    <h3>PLAN EMPRESARIAL - EMPRENDE CUSCO</h3>
                    <p style="margin:3px 0 0;font-family:sans-serif;font-size:.85rem;color:#64748b">Ficha del proyecto generada a partir de la idea elegida</p>
                </div>
                <h4>I. Resumen ejecutivo</h4>
                <p><strong>Negocio:</strong> <span id="docIdea">Sin seleccionar</span></p>
                <p><strong>Emprendedor:</strong> <span id="docNombre">-</span></p>
                <p><strong>Ubicación:</strong> <span id="docUbicacion">-</span></p>
                <p><strong>Resumen:</strong> <span id="docResumenEjecutivo">Completa el resumen ejecutivo.</span></p>
                <h4>II. Mercado y propuesta de valor</h4>
                <p><strong>Producto/servicio:</strong> <span id="docProducto">-</span></p>
                <p><strong>Necesidad:</strong> <span id="docNecesidad">-</span></p>
                <p><strong>Clientes:</strong> <span id="docClientes">-</span></p>
                <p><strong>Competidores:</strong> <span id="docCompetidores">-</span></p>
                <p><strong>Oportunidad:</strong> <span id="docOportunidad">-</span></p>
                <h4>III. Marketing (7 P)</h4>
                <ol>
                    <li>Producto: <span id="docPProducto">-</span></li>
                    <li>Precio: <span id="docPPrecio">-</span></li>
                    <li>Plaza: <span id="docPPlaza">-</span></li>
                    <li>Promoción: <span id="docPPromocion">-</span></li>
                    <li>Personal: <span id="docPPersonal">-</span></li>
                    <li>Proceso: <span id="docPProceso">-</span></li>
                    <li>Presentación física: <span id="docPPresentacion">-</span></li>
                </ol>
                <h4>IV. Organización, inversión y finanzas</h4>
                <p><strong>Equipo:</strong> <span id="docEquipo">-</span></p>
                <p><strong>Inversión:</strong> <span id="docInversion">S/ 0.00</span></p>
                <p><strong>Precio de venta:</strong> <span id="docPrecio">S/ 0.00</span></p>
                <p><strong>Punto de equilibrio:</strong> <span id="docEquilibrio">-</span></p>
                <p><strong>Financiamiento:</strong> <span id="docFinanciamiento">Aporte propio</span></p>
                <p><strong>Misión:</strong> <span id="docMision">-</span></p>
                <p><strong>Visión:</strong> <span id="docVision">-</span></p>
                <p><strong>FODA:</strong> <span id="docFoda">Completa el análisis FODA.</span></p>
            </div>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:25px">
                <button type="button" id="btnDescargarWord" class="btn btn-prev" onclick="descargarPlanWord(event)" style="font-size:1rem;padding:14px 24px; cursor:pointer;">📄 Descargar plan completo en Word</button>
                <button type="submit" class="btn btn-submit" style="font-size:1.1rem;padding:16px 30px">💾 Guardar Plan Empresarial en MySQL</button>
            </div>
            <p style="text-align:center;color:#475569;margin-top:10px">Puedes descargar una copia para ti y guardar el mismo plan en la página de inicio.</p>
        </section>

        <div class="nav-buttons">
            <button type="button" class="btn btn-prev" id="btnAnterior">← Anterior</button>
            <button type="button" class="btn btn-next" id="btnSiguiente">Siguiente →</button>
        </div>
    </form>
</main>

<script>
let pasoActual = 0;
const pasos = Array.from(document.querySelectorAll('.paso-block'));
const botonesNav = Array.from(document.querySelectorAll('[data-ir]'));

function mostrarPaso(num) {
    if (num < 0 || num >= pasos.length) return;
    pasoActual = num;
    pasos.forEach((p, idx) => p.classList.toggle('activo', idx === num));
    botonesNav.forEach((b) => b.classList.toggle('activo', Number(b.dataset.ir) === num));

    document.getElementById('btnAnterior').style.visibility = num === 0 ? 'hidden' : 'visible';
    document.getElementById('btnSiguiente').style.display = num === pasos.length - 1 ? 'none' : 'inline-block';

    if (num === 2) generarCatalogoPorRubro();
    if (num === 4 || num === 6 || num === 8) calcularFinanzas();
    if (num === 7 || num === 8) actualizarDoc();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const distritosCusco = {
  "Acomayo": ["Acomayo", "Acopia", "Acos", "Mosoc Llacta", "Pomacanchi", "Rondocan", "Sangarará"],
  "Anta": ["Anta", "Ancahuasi", "Cachimayo", "Chinchaypujio", "Huarocondo", "Limatambo", "Mollepata", "Pucyura", "Zurite"],
  "Calca": ["Calca", "Coya", "Lamay", "Lares", "Pisac", "San Salvador", "Taray", "Yanatile"],
  "Canas": ["Yanaoca", "Checca", "Kunturkanki", "Langui", "Layo", "Pampamarca", "Quehue", "Túpac Amaru"],
  "Canchis": ["Sicuani", "Checacupe", "Combapata", "Marangani", "Pitumarca", "San Pablo", "San Pedro", "Tinta"],
  "Chumbivilcas": ["Santo Tomás", "Capacmarca", "Chamaca", "Colquemarca", "Livitaca", "Llusco", "Quiñota", "Velille"],
  "Cusco": ["Cusco", "Ccorca", "Poroy", "San Jerónimo", "San Sebastián", "Santiago", "Saylla", "Wanchaq"],
  "Espinar": ["Yauri (Espinar)", "Alto Pichigua", "Condoroma", "Coporaque", "Ocoruro", "Pallpata", "Pichigua", "Suyckutambo"],
  "La Convención": ["Santa Ana", "Echarate", "Huayopata", "Maranura", "Ocobamba", "Quellouno", "Kimbiri", "Santa Teresa", "Vilcabamba", "Pichari"],
  "Paruro": ["Paruro", "Accha", "Ccapi", "Colcha", "Huanoquite", "Omacha", "Paccaritambo", "Pillpinto", "Yaurisque"],
  "Paucartambo": ["Paucartambo", "Caicay", "Challabamba", "Colquepata", "Huancarani", "Kosñipata"],
  "Quispicanchi": ["Urcos", "Andahuaylillas", "Camanti", "Ccarhuayo", "Ccatca", "Cusipata", "Huaro", "Lucre", "Marcapata", "Ocongate", "Oropesa", "Quiquijana"],
  "Urubamba": ["Urubamba", "Chinchero", "Huayllabamba", "Machupicchu", "Maras", "Ollantaytambo", "Yucay"]
};

const selectProvincia = document.getElementById('provincia');
const selectDistrito = document.getElementById('distrito');

function actualizarDistritos(valorSeleccionado = '') {
    if (!selectProvincia || !selectDistrito) return;
    const provincia = (selectProvincia.value || '').trim();
    selectDistrito.innerHTML = '';
    const distritos = distritosCusco[provincia] || [];

    const inicial = document.createElement('option');
    inicial.value = '';
    inicial.textContent = provincia ? '-- Selecciona un distrito --' : '-- Primero selecciona una provincia --';
    selectDistrito.appendChild(inicial);
    selectDistrito.disabled = distritos.length === 0;

    distritos.forEach(distrito => {
        const opcion = document.createElement('option');
        opcion.value = distrito;
        opcion.textContent = distrito;
        if (distrito === valorSeleccionado) opcion.selected = true;
        selectDistrito.appendChild(opcion);
    });
}

if (selectProvincia && selectDistrito) {
    selectProvincia.addEventListener('change', () => actualizarDistritos());
    actualizarDistritos(<?= json_encode((string)($_POST['distrito'] ?? ''), JSON_UNESCAPED_UNICODE) ?>);
}

function obtenerImagenGraficoConFondoBlanco() {
    const original = document.getElementById('chartEquilibrio');
    try {
        if (original && original.width > 0 && original.height > 0) {
            const ctx = original.getContext('2d');
            if (ctx) {
                const copia = document.createElement('canvas');
                copia.width = original.width;
                copia.height = original.height;
                const cctx = copia.getContext('2d');
                cctx.fillStyle = '#fff';
                cctx.fillRect(0, 0, copia.width, copia.height);
                cctx.drawImage(original, 0, 0, copia.width, copia.height);
                return copia.toDataURL('image/png');
            }
        }

        if (typeof Chart !== 'undefined' && chartEquilibrioInstance) {
            const copia = document.createElement('canvas');
            copia.width = 1200;
            copia.height = 520;
            const cctx = copia.getContext('2d');
            if (!cctx) return '';
            cctx.fillStyle = '#fff';
            cctx.fillRect(0, 0, copia.width, copia.height);
            const data = JSON.parse(JSON.stringify(chartEquilibrioInstance.data));
            const opciones = {
                responsive: false,
                maintainAspectRatio: false,
                animation: false,
                devicePixelRatio: 1,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Soles (S/)' } },
                    x: { title: { display: true, text: 'Cantidad Vendida (Unidades de 10 en 10)' } }
                }
            };
            const temporal = new Chart(cctx, { type: 'line', data, options: opciones });
            temporal.update('none');
            const imagen = copia.toDataURL('image/png');
            temporal.destroy();
            return imagen;
        }
    } catch (error) {
        console.warn('No se pudo preparar el gráfico para Word:', error);
    }
    return '';
}

function descargarPlanWord(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const form = document.getElementById('formEmprendimiento');
    if (!form) {
        alert('No se encontró el formulario del plan. Recarga la página e inténtalo nuevamente.');
        return false;
    }

    try {
    if (typeof actualizarDoc === 'function') actualizarDoc();
    if (typeof calcularFinanzas === 'function') calcularFinanzas();

    const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/\n/g, '<br>');
    const canvasVal = (id, fallbackIds = []) => {
        const actual = (document.getElementById(id)?.value || '').trim();
        if (actual) return esc(actual);
        const partes = fallbackIds.map(fid => {
            const el = document.getElementById(fid);
            if (!el) return '';
            const val = (el.tagName === 'SELECT' ? (el.selectedOptions[0]?.textContent || el.value) : el.value || '').trim();
            if (!val) return '';
            const label = form.querySelector('label[for="' + fid + '"]')?.innerText.replace('*', '').trim() || fid.replace(/_/g, ' ');
            return label + ': ' + val;
        }).filter(Boolean);
        return esc(partes.join('; ') || 'Pendiente de completar por el usuario.');
    };

    const titulo = document.getElementById('ideaSeleccionadaPOST')?.value || 'Plan de negocio';
    const filas = [];
    const vistos = new Set();

    const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
    inputs.forEach(el => {
        if (!el.name || ['button','submit','reset','file'].includes(el.type)) return;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
        if (el.type === 'radio' && vistos.has(el.name)) return;

        let nombre = el.name || el.id || 'Campo';
        if (el.id) {
            const labelEl = form.querySelector('label[for="' + el.id + '"]');
            if (labelEl) nombre = labelEl.innerText.replace('*', '').trim();
        }

        if (el.type === 'radio' || el.type === 'checkbox') vistos.add(el.name);
        let valor = el.tagName === 'SELECT' ? (el.selectedOptions[0]?.textContent || el.value) : el.value;
        if (String(valor).trim() !== '') {
            filas.push('<tr><th style="background:#f3f4f6;padding:8px;border:1px solid #ccc;text-align:left;">' + esc(nombre) + '</th><td style="padding:8px;border:1px solid #ccc;">' + esc(valor) + '</td></tr>');
        }
    });

    const imagenDataUrl = obtenerImagenGraficoConFondoBlanco();
    let htmlGrafico = '';
    const descripcionGrafico = '<p><strong>Gráfico Interactivo de Punto de Equilibrio</strong></p>' +
        '<p>El eje horizontal muestra las unidades de 10 en 10 (10, 20, 30, 40, etc.). ' +
        'La zona sombreada en <strong style="color:#16a34a">verde representa las Ganancias</strong> ' +
        'y la zona sombreada en <strong style="color:#dc2626">rojo representa las Pérdidas</strong>.</p>';
    if (imagenDataUrl) {
        htmlGrafico = '<h2>Gráfico del Punto de Equilibrio</h2>' + descripcionGrafico +
                      '<div style="text-align:center;margin:15px 0;">' +
                      '<img src="' + imagenDataUrl + '" width="600" style="width:100%;max-width:600px;height:auto;border:1px solid #ddd;padding:5px;" alt="Gráfico de Equilibrio" />' +
                      '</div>';
    } else {
        htmlGrafico = '<h2>Gráfico del Punto de Equilibrio</h2>' + descripcionGrafico +
                      '<p><em>El gráfico no estaba disponible al generar el documento. Vuelve a calcular los datos financieros y descarga nuevamente para incluir la imagen.</em></p>';
    }

    const htmlLienzoCanvas = '<h2>LIENZO LEAN CANVAS</h2>' +
    '<table style="width:100%; border-collapse:collapse; border:2px solid #000080; margin:20px 0; font-family:Arial, sans-serif;">' +
        '<tr>' +
            '<td rowspan="2" style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">PROBLEMA</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_problema', ['necesidad_cliente','oportunidad_mercado','producto_servicio']) + '</span>' +
            '</td>' +
            '<td style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">SOLUCIÓN</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_solucion', ['producto_servicio','estrategia_producto','ideaSeleccionadaPOST']) + '</span>' +
            '</td>' +
            '<td rowspan="2" style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">PROPOSICIÓN DE VALOR ÚNICA</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_propuesta', ['producto_servicio','necesidad_cliente','ideaSeleccionadaPOST']) + '</span>' +
            '</td>' +
            '<td style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">VENTAJA ESPECIAL</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_ventaja', ['estrategia_producto','estrategia_promocion','zonas_cusco']) + '</span>' +
            '</td>' +
            '<td rowspan="2" style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">SEGMENTO DE CLIENTES</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_clientes', ['cliente_objetivo','segmentos_clientes','zonas_cusco']) + '</span>' +
            '</td>' +
        '</tr>' +
        '<tr>' +
            '<td style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">MÉTRICAS CLAVE</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_metricas', ['ventas_estimadas','precio_venta','costos_fijos']) + '</span>' +
            '</td>' +
            '<td style="width:20%; border:1.5px solid #000080; padding:8px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">CANALES</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_canales', ['canales_venta','canales_preferidos','modalidad_venta','estrategia_plaza']) + '</span>' +
            '</td>' +
        '</tr>' +
        '<tr>' +
            '<td colspan="2" style="width:40%; border:1.5px solid #000080; padding:10px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">ESTRUCTURA DE COSTES</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_costos', ['costos_fijos','costos_variables','costo_empaque_unitario','mano_obra_unitaria','otros_variables_unitarios','alquiler_mensual','servicios_mensuales']) + '</span>' +
            '</td>' +
            '<td colspan="3" style="width:60%; border:1.5px solid #000080; padding:10px; vertical-align:top;">' +
                '<strong style="color:#000080; font-size:10pt;">FLUJO DE INGRESOS</strong><br><br>' +
                '<span style="font-size:9.5pt;">' + canvasVal('canvas_ingresos', ['precio_venta','ventas_estimadas','estrategia_precio','financiamiento']) + '</span>' +
            '</td>' +
        '</tr>' +
    '</table>';

    const bloqueFinanzas = '<h2>Resultado financiero y punto de equilibrio</h2>' +
        '<table><tr><th>Ventas estimadas</th><td>' + esc(document.getElementById('docVentas')?.textContent || '') + '</td></tr>' +
        '<tr><th>Ingresos proyectados</th><td>' + esc(document.getElementById('docIngresos')?.textContent || '') + '</td></tr>' +
        '<tr><th>Costos variables</th><td>' + esc(document.getElementById('docCostosVariables')?.textContent || '') + '</td></tr>' +
        '<tr><th>Costos fijos</th><td>' + esc(document.getElementById('docCostosFijos')?.textContent || '') + '</td></tr>' +
        '<tr><th>Utilidad / pérdida mensual</th><td><strong>' + esc(document.getElementById('docGanancia')?.textContent || '') + '</strong></td></tr>' +
        '<tr><th>Ganancia mensual</th><td style="color:#166534;font-weight:bold">' + esc(document.getElementById('docGananciaPositiva')?.textContent || 'S/ 0.00') + '</td></tr>' +
        '<tr><th>Pérdida mensual</th><td style="color:#991b1b;font-weight:bold">' + esc(document.getElementById('docPerdida')?.textContent || 'S/ 0.00') + '</td></tr>' +
        '<tr><th>Resultado calculado</th><td><strong>' + esc(document.getElementById('resEstadoFinanciero')?.textContent || document.getElementById('docEstadoFinanciero')?.textContent || 'Pendiente') + '</strong></td></tr>' +
        '<tr><th>Importe del resultado</th><td><strong>' + esc(document.getElementById('resMontoResultado')?.textContent || 'S/ 0.00') + '</strong></td></tr>' +
        '<tr><th>Punto de equilibrio</th><td>' + esc(document.getElementById('docEquilibrio')?.textContent || '') + '</td></tr>' +
        '<tr><th>Diferencia frente al punto de equilibrio</th><td>' + esc(document.getElementById('resDiferenciaEquilibrio')?.textContent || 'No calculable') + '</td></tr></table>' +
        '<h3>Interpretación del resultado</h3><p>' + esc(document.getElementById('docInterpretacionEquilibrio')?.textContent || document.getElementById('textoPuntoEquilibrio')?.textContent || '') + '</p>' +
        '<p><strong>Leyenda:</strong> GANANCIAS = ingresos superiores a los costos; PÉRDIDAS = costos superiores a los ingresos; EQUILIBRIO = ingresos iguales a costos.</p>';
    const resumen = document.querySelector('.documento-papel')?.innerHTML || '';

    const html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;color:#222}h1,h2,h3{color:#8b0000}table{width:100%;border-collapse:collapse;margin:12px 0}td,th{border:1px solid #bbb;padding:6px;text-align:left;vertical-align:top}th{background:#f3f3f3;width:35%}p{line-height:1.45}</style></head><body>' +
        '<h1>PLAN EMPRESARIAL - EMPRENDE CUSCO</h1><h2>' + esc(titulo) + '</h2>' +
        '<h2>Datos del proyecto</h2><table>' + filas.join('') + '</table>' + bloqueFinanzas + htmlGrafico + htmlLienzoCanvas + '<h2>Resumen empresarial</h2>' + resumen +
        '</body></html>';

    const blob = new Blob(['\ufeff', html], {type: 'application/msword;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = ('Plan_Empresarial_' + titulo).replace(/[^a-z0-9_-]/gi, '_') + '.doc';
    document.body.appendChild(a);
    a.click();
    a.remove();

    window.setTimeout(() => URL.revokeObjectURL(url), 1500);
    return true;
    } catch (error) {
        console.error('Error al generar el documento Word:', error);
        alert('No se pudo generar el archivo Word. Inténtalo nuevamente o revisa la consola del navegador.');
        return false;
    }
}

function validarPasoActual() {
    if (pasoActual === 0) {
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        const celular = document.getElementById('celular').value.trim();
        const edad = document.getElementById('edad').value.trim();
        const genero = document.getElementById('genero').value;
        const ciudad = document.getElementById('ciudad').value.trim();
        const provincia = document.getElementById('provincia').value;
        const rol = document.getElementById('rol').value;
        const colegio = document.getElementById('colegio').value;
        const grado = document.getElementById('grado').value.trim();
        const distrito = document.getElementById('distrito').value;

        if (!nombre || !email || !celular || !edad || !genero || !ciudad || !provincia || !rol || !colegio || !grado || !distrito) {
            alert('⚠️ Completa todos los datos de tu Perfil para continuar.');
            return false;
        }
    }

    if (pasoActual === 1) {
        const radioChecked = document.querySelector('input[name="rubro_seleccionado"]:checked');
        if (!radioChecked) {
            alert('⚠️ Selecciona una opción en la Pregunta 1 del Test.');
            return false;
        }
        if (radioChecked.value === 'Otra idea (especificar)' && !document.getElementById('rubro_otro_texto').value.trim()) {
            alert('⚠️ Escribe tu propia idea de negocio para continuar.');
            document.getElementById('rubro_otro_texto').focus();
            return false;
        }

        for (let i = 2; i <= 10; i++) {
            const checkedCount = document.querySelectorAll(`input[name="p${i}[]"]:checked`).length;
            if (checkedCount === 0) {
                alert(`⚠️ Responde la Pregunta ${i} del test para continuar.`);
                return false;
            }
        }
    }

    if (pasoActual === 2) {
        const ideaSel = document.getElementById('ideaSeleccionadaPOST').value;
        if (!ideaSel) {
            alert('⚠️ Haz clic en "Seleccionar esta Idea" en una de las tarjetas.');
            return false;
        }
    }

    return true;
}

// ============================================================
// BANCO DE IMÁGENES DE RESPALDO (si no hay fotos externas)
// ============================================================
const bancoFotos = {
    gastronomia: [
        { url: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80', credito: 'Pan y pastelería artesanal', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80', credito: 'Plato gastronómico preparado', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?auto=format&fit=crop&w=800&q=80', credito: 'Postres y desayunos especiales', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80', credito: 'Platillos preparados', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1517433670267-08bbd4be890f?auto=format&fit=crop&w=800&q=80', credito: 'Pasteles y tortas personalizadas', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80', credito: 'Cafetería y bebidas', fuente: 'https://unsplash.com' }
    ],
    textiles: [
        { url: 'https://images.unsplash.com/photo-1606744837616-56c9a5c6a6eb?auto=format&fit=crop&w=800&q=80', credito: 'Tejido artesanal tradicional', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1528458876861-544fd1761a91?auto=format&fit=crop&w=800&q=80', credito: 'Telas y confección de prendas', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=800&q=80', credito: 'Artesanía textil hecha a mano', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80', credito: 'Muestras de lana e hilos teñidos', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=800&q=80', credito: 'Bordados artesanales', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1528698827591-e19ccd7bc23d?auto=format&fit=crop&w=800&q=80', credito: 'Exhibición de artículos artesanales', fuente: 'https://unsplash.com' }
    ],
    turismo: [
        { url: 'https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&w=800&q=80', credito: 'Ciudad Histórica de Cusco', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1589802829985-817e51171b92?auto=format&fit=crop&w=800&q=80', credito: 'Paisajes del Valle Sagrado', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1509316975850-ff9c5deb0cd9?auto=format&fit=crop&w=800&q=80', credito: 'Caminatas y tours', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1533105079780-92b9be482077?auto=format&fit=crop&w=800&q=80', credito: 'Sitios arqueológicos', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=800&q=80', credito: 'Rutas de naturaleza e historia', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80', credito: 'Paisajes turísticos', fuente: 'https://unsplash.com' }
    ],
    tecnologia: [
        { url: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80', credito: 'Servicios digitales y desarrollo web', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=800&q=80', credito: 'Mantenimiento de laptops', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1556742049-0a670f4a4591?auto=format&fit=crop&w=800&q=80', credito: 'Gestión de catálogo e inventarios', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80', credito: 'Marketing y contenido digital', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=800&q=80', credito: 'Diseño gráfico para marcas', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80', credito: 'Capacitación en herramientas TIC', fuente: 'https://unsplash.com' }
    ],
    comercio: [
        { url: 'https://images.unsplash.com/photo-1534723452862-4c874018d66d?auto=format&fit=crop&w=800&q=80', credito: 'Tienda y productos', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=800&q=80', credito: 'Puesto comercial de ventas', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1526178613552-2b45c6c302f0?auto=format&fit=crop&w=800&q=80', credito: 'Empaque de pedidos', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?auto=format&fit=crop&w=800&q=80', credito: 'Atención al cliente', fuente: 'https://unsplash.com' }
    ],
    dj: [
        { url: 'https://images.unsplash.com/photo-1571266028243-d220c9c3b6e7?auto=format&fit=crop&w=900&q=80', credito: 'DJ y consola de mezcla', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=900&q=80', credito: 'Iluminación y DJ en vivo', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=900&q=80', credito: 'Pista de baile', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=900&q=80', credito: 'Sonido para eventos', fuente: 'https://unsplash.com' }
    ],
    general: [
        { url: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=800&q=80', credito: 'Planificación de negocio', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80', credito: 'Prestación de servicios', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=800&q=80', credito: 'Reunión de trabajo', fuente: 'https://unsplash.com' },
        { url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80', credito: 'Equipo de trabajo', fuente: 'https://unsplash.com' }
    ]
};

function obtenerFotosParaIdea(titulo, rubro, indice = 0) {
    const texto = (String(titulo || '') + ' ' + String(rubro || '')).toLowerCase();
    let lista = bancoFotos.general;

    if (/\bdj\b|disc jockey|sonido para eventos|mezcla musical|m[uú]sica para fiestas|luces para eventos/.test(texto)) lista = bancoFotos.dj;
    else if (/textil|ropa|chalina|poncho|tejid|bordad|costura|alpaca|hilo|moda|artesan[ií]a|manualidad|retablo|cer[aá]mica/.test(texto)) lista = bancoFotos.textiles;
    else if (/panader|pasteler|postre|empanad|catering|gastronom|comida|desayuno|caf[eé]|bebida|chocolate|cuy|queso|quinua/.test(texto)) lista = bancoFotos.gastronomia;
    else if (/turismo|cultura|gu[ií]a|excursi[oó]n|ruta|viaje|arqueol[oó]g/.test(texto)) lista = bancoFotos.turismo;
    else if (/celular|tecnolog|digital|software|web|comput|redes sociales|fotograf[ií]a|men[uú]|whatsapp|video/.test(texto)) lista = bancoFotos.tecnologia;
    else if (/comercio|ventas|venta|mercado|tienda|feria/.test(texto)) lista = bancoFotos.comercio;

    const foto1 = lista[(indice * 2) % lista.length];
    const foto2 = lista[(indice * 2 + 1) % lista.length];
    return [foto1, foto2];
}

const urlsFotosYaUsadas = new Set();

function obtenerIdeaPrincipalDelTest1() {
    const seleccion = document.querySelector('input[name="rubro_seleccionado"]:checked');
    if (!seleccion) return '';
    if (seleccion.value === 'Otra idea (especificar)') {
        return (document.getElementById('rubro_otro_texto')?.value || '').trim();
    }
    return String(seleccion.value || '').trim();
}

function limpiarTextoParaBusqueda(texto) {
    return String(texto || '')
        .replace(/^Idea personalizada:\s*/i, '')
        .replace(/[^\p{L}\p{N}\s-]/gu, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

// ============================================================
// FOTOS DEL CATÁLOGO: URLS DIRECTAS DE FUENTES WEB + RESPALDO DEL SERVIDOR
// Las fotos son del negocio o de su actividad, no imágenes de stock.
// ============================================================
const FOTOS_REALES_CUSCO = {
  'cafe-ayllu': [
    {url:'https://explorandes.net/blogs/88ad5195-ddc0-4d01-b769-d8aab385f843/d616a74d-ecb9-4cd8-838a-28f42417a777.jpg', credito:'Café Ayllu: local y desayuno', fuente:'https://www.explorandes.com/es/blog/mejor-cafe-cusco-lima'},
    {url:'https://mainlinephoto.wordpress.com/wp-content/uploads/2009/04/ayllu.jpg', credito:'Interior de Café Ayllu', fuente:'https://mainlinephoto.wordpress.com/2009/04/10/cafe-ayllu-a-classic-coffee-shop-in-cusco/'}
  ],
  'arte-sano': [
    {url:'https://artesanoheladeria.mypesdigitales.com/public/assets/imagenes/servicios/Helado_Artesanal_en_Vasito_20250515_221118.png', credito:'Helados Arte Sano en vasito', fuente:'https://artesanoheladeria.mypesdigitales.com/'},
    {url:'https://artesanoheladeria.mypesdigitales.com/public/assets/imagenes/servicios/Helado_Artesanal_en_Paleta_20250515_221150.png', credito:'Paletas artesanales Arte Sano', fuente:'https://artesanoheladeria.mypesdigitales.com/'}
  ],
  'auria': [
    {url:'https://live.staticflickr.com/65535/55239672944_aa454e83b5_h.jpg', credito:'Visita oficial a Auria, Cusco (PRODUCE)', fuente:'https://www.flickr.com/photos/ministeriodelaproduccion/sets/72177720333408214'},
    {url:'https://live.staticflickr.com/65535/55239672949_e3696d6935_h.jpg', credito:'Segunda fotografía de la visita oficial a Auria (PRODUCE)', fuente:'https://www.flickr.com/photos/ministeriodelaproduccion/sets/72177720333408214'}
  ],
  'fruticyd': [
    {url:'https://fruticydcusco.com/public/assets/imagenes/servicios/FRUTICYD_DURAZNO_175_ML_-_12_PACK__S__5_50__20251013_235046.png', credito:'Producto FRUTICYD sabor durazno', fuente:'https://fruticydcusco.com/'}
  ],
  'qori-puskay': [
    {url:'https://qoripuskay.com/wp-content/uploads/2024/11/bolsos-1-600x600.webp', credito:'Bolso textil de Qori Puskay', fuente:'https://qoripuskay.com/tienda/'},
    {url:'https://qoripuskay.com/wp-content/uploads/2024/11/BOLSO_PQUE-600x600.webp', credito:'Bolso artesanal Qori Puskay', fuente:'https://qoripuskay.com/tienda/'}
  ],
  'brew-brothers': [
    {url:'https://cloudfront-us-east-1.images.arcpublishing.com/elcomercio/F5MUO3BTBJCKBEXGY6FSVW2A44.jpg', credito:'Brew Brothers en el Mercado de San Blas', fuente:'https://elcomercio.pe/somos/gastronomia/brew-brothers-la-primera-cafeteria-de-especialidad-en-el-mercado-de-san-blas-cusco-noticia/'},
    {url:'https://cloudfront-us-east-1.images.arcpublishing.com/elcomercio/V7QKOLVJXJCAVE6QA33SX4ZZOQ.jpg', credito:'Preparación de café de especialidad', fuente:'https://elcomercio.pe/somos/gastronomia/brew-brothers-la-primera-cafeteria-de-especialidad-en-el-mercado-de-san-blas-cusco-noticia/'}
  ],
  'three-monkeys': [
    {url:'https://maximonivel.com/wp-content/uploads/Three-Monkeys-Coffee-Company-Cusco.jpg', credito:'Triciclo cafetero Three Monkeys en Cusco', fuente:'https://maximonivel.com/coffee-shops-in-cusco-peru/'}
  ],
  'qosqo-creme': [
    {url:'https://larepublica.cronosmedia.glr.pe/migration/images/M365TLIDHFFODN4O43CCYTIAJI.jpg', credito:'Producto artesanal Qosqo Creme', fuente:'https://larepublica.pe/sociedad/2020/07/29/cusco-efrain-valles-de-ser-distinguido-como-el-mejor-guia-del-mundo-a-vender-helados-artesanales-lrsd'}
  ],
  'experiences': [
    {url:'https://experiencesarteycafe.com/nosotros-history.webp', credito:'Fachada de Experiences Arte y Café', fuente:'https://experiencesarteycafe.com/nosotros'},
    {url:'https://experiencesarteycafe.com/menu-sidebar-2.webp', credito:'Preparación de café en Experiences', fuente:'https://experiencesarteycafe.com/nosotros'}
  ],
  'pallay': [
    {url:'https://pallay.pe/cdn/shop/files/Yessenia_Angulo.jpg?v=1680204637&width=1500', credito:'Experiencia de tejido tradicional Pallay', fuente:'https://pallay.pe/pages/experiencia-pallay'},
    {url:'https://pallay.pe/cdn/shop/files/Pallay_S1__062.jpg?v=1684427269&width=1420', credito:'Proceso textil con artesana Pallay', fuente:'https://pallay.pe/pages/experiencia-pallay'}
  ]
};

const DESTINOS_EMPRENDIMIENTOS = {
  'cafe-ayllu':'https://www.google.com/maps/search/?api=1&query=Caf%C3%A9+Ayllu+Cusco',
  'arte-sano':'https://artesanoheladeria.mypesdigitales.com/',
  'auria':'https://www.gob.pe/institucion/produce/noticias/1386990-auria-el-emprendimiento-cusqueno-que-fusiona-el-tejido-tradicional-con-tecnologia-de-ultima-generacion',
  'fruticyd':'https://fruticydcusco.com/',
  'qori-puskay':'https://qoripuskay.com/',
  'brew-brothers':'https://elcomercio.pe/somos/gastronomia/brew-brothers-la-primera-cafeteria-de-especialidad-en-el-mercado-de-san-blas-cusco-noticia/',
  'three-monkeys':'https://www.rutadelcafeperuano.com/asi-fue-como-los-three-monkeys-crearon-una-hermosa-cafeteria-en-cusco-que-ahora-esta-nominada-por-su-diseno-en-los-premios-sprudge/',
  'qosqo-creme':'https://larepublica.pe/sociedad/2020/07/29/cusco-efrain-valles-de-ser-distinguido-como-el-mejor-guia-del-mundo-a-vender-helados-artesanales-lrsd',
  'experiences':'https://experiencesarteycafe.com/',
  'pallay':'https://pallay.pe/'
};

async function fotosServidorEmprendimiento(id) {
    const u = new URL(window.location.href);
    u.search = '';
    u.searchParams.set('ajax_emprendimiento_images', '1');
    u.searchParams.set('id', id);
    const controlador = new AbortController();
    const timeout = setTimeout(() => controlador.abort(), 15000);
    try {
        const r = await fetch(u.toString(), {headers:{'Accept':'application/json'}, signal:controlador.signal});
        if (!r.ok) return [];
        const d = await r.json();
        return (d.images || []).filter(f => f.url && /^https:\/\//i.test(f.url));
    } finally { clearTimeout(timeout); }
}

async function obtenerDosFotosCusco(consultas, nombreIdea, rubro, idNegocio) {
    // No se generan ilustraciones ni se mezclan fotos de otros negocios.
    // Solo se devuelven fotografías enlazadas desde páginas del propio negocio
    // o artículos que documentan específicamente ese emprendimiento.
    return (FOTOS_REALES_CUSCO[idNegocio] || []).slice(0, 2);
}

function obtenerFotosDeRespaldoUnicas(idea, rubro, cantidad = 2) {
    const ideaTest1 = limpiarTextoParaBusqueda(obtenerIdeaPrincipalDelTest1());
    const sugeridas = obtenerFotosParaIdea(idea, `${rubro} ${ideaTest1}`, 0);
    const clavesSugeridas = new Set(sugeridas.map(f => f.url));
    const todas = Object.values(bancoFotos).flat();
    const ordenadas = [
        ...sugeridas,
        ...todas.filter(f => clavesSugeridas.has(f.url)),
        ...todas
    ];
    const resultado = [];
    const vistas = new Set();

    for (const foto of ordenadas) {
        if (!foto || !foto.url || vistas.has(foto.url) || urlsFotosYaUsadas.has(foto.url)) continue;
        vistas.add(foto.url);
        resultado.push({
            ...foto,
            fuente: foto.fuente || obtenerGoogleImagenesUrl(`${idea} ${ideaTest1}`),
            credito: foto.credito || `Imagen ilustrativa relacionada con ${ideaTest1 || idea}`
        });
        if (resultado.length >= cantidad) break;
    }

    let contadorFallback = 0;
    while (resultado.length < cantidad) {
        const fallback = crearImagenFallbackIdea(ideaTest1 || idea, contadorFallback++);
        if (!urlsFotosYaUsadas.has(fallback.url)) {
            resultado.push(fallback);
        }
    }
    return resultado;
}

function crearImagenFallbackIdea(idea, indice = 0) {
    const titulo = String(idea || 'Idea de negocio en Cusco').slice(0, 75);
    const colores = [ ['#0f766e','#ccfbf1'], ['#7c2d12','#ffedd5'], ['#1d4ed8','#dbeafe'], ['#6b21a8','#f3e8ff'] ];
    const par = colores[Math.abs(indice) % colores.length];
    const selloUnico = Date.now().toString(36) + Math.random().toString(36).slice(2);
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500"><metadata>${selloUnico}</metadata><rect width="800" height="500" fill="${par[1]}"/><circle cx="400" cy="190" r="95" fill="${par[0]}" opacity=".12"/><rect x="270" y="105" width="260" height="175" rx="22" fill="${par[0]}" opacity=".88"/><path d="M310 235 L370 165 L415 210 L455 175 L500 235 Z" fill="#fff" opacity=".9"/><circle cx="365" cy="145" r="15" fill="#fff"/><text x="400" y="345" text-anchor="middle" font-family="Arial,sans-serif" font-size="27" font-weight="700" fill="${par[0]}">IDEA DE NEGOCIO</text><text x="400" y="385" text-anchor="middle" font-family="Arial,sans-serif" font-size="22" fill="${par[0]}">${titulo.replace(/[&<>"']/g, ' ')}</text><text x="400" y="435" text-anchor="middle" font-family="Arial,sans-serif" font-size="20" fill="${par[0]}">CUSCO · PERÚ</text></svg>`;
    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        fuente: obtenerGoogleImagenesUrl(idea),
        credito: 'Imagen de respaldo para ' + titulo,
        ilustrativa: true
    };
}

const lugaresOportunidadCusco = [
    {lugar:'San Blas', provincia:'Cusco', contexto:'artesanía, arte y visitantes del Centro Histórico', fuente:'https://www.peru.travel/es/atractivos/centro-historico-de-cusco'},
    {lugar:'Mercado de San Pedro', provincia:'Cusco', contexto:'venta de alimentos, productos locales y comercio', fuente:'https://www.peru.travel/es/destinos/cusco'},
    {lugar:'Pisac', provincia:'Calca', contexto:'mercado artesanal, textiles, productos locales y turismo', fuente:'https://www.peru.travel/es/inspirate/conoce-toda-la-belleza-e-historia-del-emblematico-mercado-de-pisac'},
    {lugar:'Chinchero', provincia:'Urubamba', contexto:'tejido tradicional, artesanía y turismo cultural', fuente:'https://www.peru.travel/es/atractivos/valle-sagrado'},
    {lugar:'Maras y Moray', provincia:'Urubamba', contexto:'visitas culturales, salineras, agricultura y turismo', fuente:'https://www.peru.travel/es/atractivos/valle-sagrado'},
    {lugar:'Ollantaytambo', provincia:'Urubamba', contexto:'turismo, caminantes, alojamiento y servicios para visitantes', fuente:'https://www.peru.travel/es/inspirate/que-lugares-conforman-el-valle-sagrado-de-los-inkas'},
    {lugar:'Machupicchu Pueblo', provincia:'Urubamba', contexto:'visitantes, mercado artesanal, hospedaje y servicios turísticos', fuente:'https://www.peru.travel/es/inspirate/descubre-las-maravillas-que-alberga-machupicchu-pueblo'},
    {lugar:'Quillabamba', provincia:'La Convención', contexto:'café, cacao, frutas tropicales y productos transformados', fuente:'https://transparencia.regioncusco.gob.pe/attach/docs_normativo/planes/per/PEDRC_20211.pdf'},
    {lugar:'Limatambo', provincia:'Anta', contexto:'frutas y producción agrícola local', fuente:'https://transparencia.regioncusco.gob.pe/transparencia/planeamiento_organizacion/Informacion_Adicional_PO/memoria-2019.pdf'},
    {lugar:'Oropesa', provincia:'Quispicanchi', contexto:'pan tradicional, alimentos y comercio local', fuente:'https://www.peru.travel/es/destinos/cusco'},
    {lugar:'Sicuani', provincia:'Canchis', contexto:'comercio provincial, servicios técnicos y productos regionales', fuente:'https://www.inei.gob.pe/media/MenuRecursivo/publicaciones_digitales/Est/Lib2095/dptos/cusco.htm'},
    {lugar:'Paucartambo', provincia:'Paucartambo', contexto:'cultura, festividades, artesanía y turismo local', fuente:'https://www.peru.travel/es/destinos/cusco'},
    {lugar:'Yauri', provincia:'Espinar', contexto:'servicios para familias, transporte y comercio provincial', fuente:'https://www.inei.gob.pe/media/MenuRecursivo/publicaciones_digitales/Est/Lib2095/dptos/cusco.htm'}
];

function obtenerDescripcionLocal(rubro, lugar) {
    const t = (rubro + ' ' + lugar.contexto).toLowerCase();
    if (/turismo|cultura|artesan|textil/.test(t)) return `Oportunidad para ofrecer productos o servicios vinculados con ${lugar.contexto} en ${lugar.lugar}, provincia de ${lugar.provincia}.`;
    if (/café|cacao|frut|agricultura|maíz|papa|productores/.test(t)) return `Propuesta que puede aprovechar productos y productores de la zona de ${lugar.lugar}, provincia de ${lugar.provincia}, validando primero proveedores, costos y demanda.`;
    if (/pan|alimentos|comercio|mercado/.test(t)) return `Idea para atender a residentes, comerciantes y visitantes de ${lugar.lugar}, provincia de ${lugar.provincia}, según la demanda local.`;
    return `Servicio pensado para necesidades de residentes y pequeños negocios de ${lugar.lugar}, provincia de ${lugar.provincia}. Antes de invertir, valida la demanda y los costos en la zona.`;
}

function obtenerIdeasPersonalizadas(detalle) {
    const idea = String(detalle || '').trim().replace(/\s+/g, ' ');
    if (!idea) return [];

    const normalizada = idea.toLowerCase();
    if (/\bdj\b|disc jockey|sonido|mezcla musical|luces para eventos|m[uú]sica para fiestas/.test(normalizada)) {
        return [
            `Servicio de ${idea} para matrimonios y bodas en Cusco`,
            `Servicio de ${idea} para cumpleaños familiares en Cusco`,
            `Servicio de ${idea} para graduaciones y promociones escolares en Cusco`,
            `Servicio de ${idea} para eventos de empresas y asociaciones en Cusco`,
            `Servicio de ${idea} para fiestas de quince años en Cusco`,
            `Alquiler de equipos complementarios para ${idea} en Cusco`,
            `Paquetes de ${idea} con iluminación para salones de eventos en Cusco`,
            `Servicio de ${idea} para restaurantes y hoteles de Cusco`,
            `Servicio de ${idea} para celebraciones en Wanchaq y San Sebastián`,
            `Servicio de ${idea} para ferias y actividades culturales de Cusco`
        ];
    }

    if (/gelatina|postre|reposter|pastel|panader|comida|gastronom|bebida|jugos|alimento/.test(normalizada)) {
        return [
            `Elaboración y venta de ${idea} por pedido en Cusco`,
            `Venta de ${idea} para cumpleaños y reuniones familiares en Cusco`,
            `Pedidos de ${idea} por WhatsApp para hogares de Cusco`,
            `Venta de ${idea} en ferias y mercados locales de Cusco`,
            `Preparación de ${idea} para colegios y reuniones en Cusco`,
            `Presentaciones individuales de ${idea} para tiendas y cafeterías de Cusco`,
            `Entrega a domicilio de ${idea} en Cusco y Wanchaq`,
            `Paquetes de ${idea} para celebraciones y eventos en Cusco`,
            `Venta de ${idea} con insumos regionales disponibles en Cusco`,
            `Catálogo digital de ${idea} para clientes de Cusco`
        ];
    }

    if (/ropa|textil|tejid|artesan|manualidad|souvenir|joyer|cer[aá]mica|bordad/.test(normalizada)) {
        return [
            `Elaboración de ${idea} por encargo en Cusco`,
            `Venta de ${idea} en ferias artesanales de Cusco`,
            `Catálogo por WhatsApp de ${idea} para clientes de Cusco`,
            `Diseños personalizados de ${idea} para familias de Cusco`,
            `Venta de ${idea} a tiendas y hospedajes de Cusco`,
            `Exhibición y venta de ${idea} en San Blas, Cusco`,
            `Regalos personalizados de ${idea} para visitantes de Cusco`,
            `Pedidos de ${idea} para campañas escolares y fechas especiales en Cusco`,
            `Comercialización digital de ${idea} elaborada en Cusco`,
            `Taller demostrativo de ${idea} con artesanos locales de Cusco`
        ];
    }

    if (/software|app|aplicaci[oó]n|web|diseño digital|fotograf|video|redes sociales|comput|celular|tecnolog|marketing digital/.test(normalizada)) {
        return [
            `Servicio de ${idea} para pequeños negocios de Cusco`,
            `Servicio de ${idea} para artesanos y productores de Cusco`,
            `Planes económicos de ${idea} para emprendimientos de Cusco`,
            `Servicio de ${idea} para restaurantes y cafeterías de Cusco`,
            `Servicio de ${idea} para hospedajes y operadores turísticos de Cusco`,
            `Atención por cita de ${idea} en Cusco y Wanchaq`,
            `Capacitación básica en ${idea} para emprendedores de Cusco`,
            `Paquetes mensuales de ${idea} para comercios de Cusco`,
            `Servicio a domicilio de ${idea} para clientes de Cusco`,
            `Portafolio digital de ${idea} para negocios de Cusco`
        ];
    }

    return [
        `${idea}: venta o atención por pedido en Cusco`,
        `${idea}: servicio a domicilio en Cusco y Wanchaq`,
        `${idea}: oferta para familias y residentes de San Sebastián`,
        `${idea}: solución para pequeños comercios de Santiago y San Jerónimo`,
        `${idea}: pedidos mediante WhatsApp para clientes de Cusco`,
        `${idea}: venta en ferias y mercados de la región Cusco`,
        `${idea}: paquetes para eventos y reuniones en Cusco`,
        `${idea}: oferta para restaurantes, tiendas y hospedajes de Cusco`,
        `${idea}: comercialización por catálogo digital en Cusco`,
        `${idea}: atención a clientes de Cusco y el Valle Sagrado`
    ];
}

const ideasPorRubroDB = <?= json_encode(
    $ideasPorRubroDB,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const detallesCatalogo = <?= json_encode(
    $detallesCatalogoDB,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

// ============================================================
// CATÁLOGO FIJO: 10 EMPRENDIMIENTOS REALES Y DOCUMENTADOS DE CUSCO
// La historia y los datos de cada ficha se resumen desde su fuente enlazada.
// ============================================================
const emprendimientosRealesCusco = [
  {
    id:'cafe-ayllu', nombre:'Café Ayllu', sector:'Cafetería y gastronomía', ubicacion:'Centro Histórico, Cusco',
    resumen:'Cafetería tradicional cusqueña reconocida por sus productos de pastelería y sándwiches.',
    historia:'Es un negocio familiar fundado por la familia Beltrán en 1969. A lo largo de décadas construyó una clientela de varias generaciones y se convirtió en una cafetería emblemática de la ciudad. Su trayectoria muestra la importancia de mantener una identidad reconocible, cuidar la calidad y generar relaciones duraderas con los clientes.',
    comoEmpezo:'La familia Beltrán abrió la cafetería y fue consolidando su propuesta alrededor de productos propios, como las lenguas de suegra rellenas de manjarblanco y los sándwiches de asado de lechón.',
    aprendizaje:'Construir una marca familiar, mantener productos distintivos y fidelizar a los clientes con atención constante.',
    fuente:'https://www.gob.pe/institucion/indecopi/noticias/684103-emblematico-cafe-ayllu-del-cusco-negocio-familiar-con-mas-de-50-anos-de-vida-destaca-la-importancia-de-proteger-su-marca', fuenteTexto:'Indecopi: historia de Café Ayllu',
    fotos:['Café Ayllu Cusco cafetería fachada','Café Ayllu Cusco lenguas de suegra sándwich']
  },
  {
    id:'arte-sano', nombre:'Arte Sano Heladería Artesanal', sector:'Alimentos y heladería', ubicacion:'Plaza San Francisco, Cusco',
    resumen:'Heladería artesanal que desarrolla sabores inspirados en productos y raíces culturales cusqueñas.',
    historia:'El emprendimiento nació en 2021 por iniciativa de Mishell Ana Liz Huanca Mamani. Antes de abrir su propio negocio, trabajó en distintas heladerías locales y aprendió técnicas de elaboración artesanal. Con esa experiencia decidió crear una propuesta propia con sabores diferentes y una identidad vinculada al Cusco.',
    comoEmpezo:'Su fundadora aprovechó la experiencia laboral que había adquirido en heladerías y buscó un espacio para presentar sus creaciones. Su primer local se ubicó en la Plaza San Francisco.',
    aprendizaje:'Aprender primero en el sector, identificar una propuesta diferenciada y comenzar con un punto de venta accesible al público.',
    fuente:'https://artesanoheladeria.mypesdigitales.com/nosotros', fuenteTexto:'Arte Sano: nuestra historia',
    fotos:['Arte Sano Heladería Artesanal Cusco Mishell Huanca','Arte Sano helados artesanales Plaza San Francisco Cusco']
  },
  {
    id:'auria', nombre:'Auria', sector:'Textil y moda sostenible', ubicacion:'Cusco, Perú',
    resumen:'Marca textil que combina tejido artesanal y tecnología para elaborar prendas y accesorios, principalmente con fibra de baby alpaca.',
    historia:'La marca es impulsada por Edith Franco Tio, quien cuenta con más de una década de trayectoria en el sector textil. Lo que comenzó como trabajo independiente de diseño y tejido a mano se convirtió en un modelo de negocio formalizado en 2024. La inspiración para el nombre provino de su sobrina Auria, quien vestía las prendas que su tía elaboraba.',
    comoEmpezo:'La emprendedora inició diseñando y tejiendo prendas por cuenta propia. Después consolidó una marca con chompas, ponchos, cardigans, tops y accesorios, apoyada por CITEtextil Camélidos Cusco y ProCompite.',
    aprendizaje:'Convertir una habilidad artesanal en una marca, cuidar el diseño y mejorar la producción con apoyo técnico.',
    fuente:'https://www.gob.pe/institucion/produce/noticias/1386990-auria-el-emprendimiento-cusqueno-que-fusiona-el-tejido-tradicional-con-tecnologia-de-ultima-generacion', fuenteTexto:'Ministerio de la Producción: historia de Auria',
    fotos:['Auria Edith Franco Tio emprendimiento textil Cusco','Auria prendas baby alpaca Cusco']
  },
  {
    id:'fruticyd', nombre:'FRUTICYD', sector:'Alimentos y bebidas', ubicacion:'Ciudad del Cusco',
    resumen:'Empresa cusqueña dedicada a la elaboración de yogurt, néctares y agua de mesa.',
    historia:'Fue fundada el 24 de marzo del año 2000 por Dimas Efraín Niño de Guzmán Montalvo y su esposa, ambos ingenieros químicos. La idea empresarial surgió cuando eran estudiantes y participaron en un concurso de CONCITEC relacionado con una planta de extrusión para producir chizitos. Esa experiencia los animó a emprender en el sector alimentario.',
    comoEmpezo:'Con conocimientos profesionales y capital propio comenzaron con una planta de yogurt. Más adelante ampliaron su producción hacia néctares y agua de mesa, y desarrollaron distribución con vehículos propios.',
    aprendizaje:'Aprovechar conocimientos técnicos, empezar con una línea de producto y crecer progresivamente según la demanda.',
    fuente:'https://fruticydcusco.com/nosotros', fuenteTexto:'FRUTICYD: nuestra historia',
    fotos:['FRUTICYD Cusco planta productos yogurt néctares','FRUTICYD bebidas Cusco productos']
  },
  {
    id:'qori-puskay', nombre:'Qori Puskay – Centro Textil Chinchero', sector:'Artesanía y tejido tradicional', ubicacion:'Chinchero, provincia de Urubamba',
    resumen:'Iniciativa familiar y comunitaria que promueve el tejido tradicional y la venta de artesanías elaboradas por mujeres de Chinchero.',
    historia:'Su antecedente se remonta a los años noventa, cuando artesanas se reunían en “La casa del abuelo” para tejer, preservar sus conocimientos y generar ingresos. En 2007 nació Qori Puskay y abrió sus puertas a visitantes del Parque Arqueológico de Chinchero, incorporando demostraciones del tejido y de los materiales utilizados.',
    comoEmpezo:'Un grupo de artesanas se organizó para tejer y vender sus productos. Con el crecimiento de las visitas turísticas, el proyecto amplió su actividad y pasó a mostrar el proceso de elaboración, además de comercializar las prendas.',
    aprendizaje:'Trabajar asociativamente, poner en valor los saberes locales y ofrecer una experiencia además de un producto.',
    fuente:'https://qoripuskay.com/sobre-nosotros/', fuenteTexto:'Qori Puskay: historia',
    fotos:['Qori Puskay Centro Textil Chinchero artesanas','Qori Puskay Chinchero demostración tejido']
  },
  {
    id:'brew-brothers', nombre:'Brew Brothers', sector:'Cafetería y café de especialidad', ubicacion:'Mercado de San Blas, Cusco',
    resumen:'Cafetería de especialidad creada por dos amigos y ubicada en el tradicional mercado de San Blas.',
    historia:'Detrás del proyecto están Abraham Queccaño y Eriksson Huamanga. Su interés por el café los llevó a explorar el mundo del café de especialidad. Abraham, programador de profesión, cuenta que su curiosidad se despertó en 2023 al conocer más sobre preparación, filtrado y ciencia del café. La idea de llevar café de especialidad a un mercado tradicional terminó convirtiéndose en el negocio.',
    comoEmpezo:'Dos amigos unieron su interés por el café y apostaron por instalar una barra de café de especialidad dentro del Mercado de San Blas, un espacio poco habitual para ese formato.',
    aprendizaje:'Detectar una oportunidad en un lugar tradicional, combinar habilidades distintas y diferenciarse por la calidad del producto.',
    fuente:'https://elcomercio.pe/somos/gastronomia/brew-brothers-la-primera-cafeteria-de-especialidad-en-el-mercado-de-san-blas-cusco-noticia/', fuenteTexto:'El Comercio: historia de Brew Brothers',
    fotos:['Brew Brothers cafetería Mercado San Blas Cusco','Brew Brothers Abraham Queccaño Eriksson Huamanga café']
  },
  {
    id:'three-monkeys', nombre:'Three Monkeys', sector:'Café y venta móvil', ubicacion:'Cusco, Perú',
    resumen:'Emprendimiento de café que acercó una propuesta de bike coffee al público mediante un triciclo cafetero.',
    historia:'Fue creado por tres jóvenes apasionados por el café: Diego Huillca Santa Cruz, Iván Salas y Neto Solórzano. Decidieron dejar sus trabajos para buscar café de calidad y desarrollar una propuesta móvil que también contara al público el recorrido del grano, desde el cultivo y la cosecha hasta el tueste y la preparación.',
    comoEmpezo:'La idea se materializó como una cafetería montada en un triciclo. Durante la pandemia, sus responsables buscaron nuevas formas de sostener el negocio y lanzaron variedades de café en bolsitas filtrantes.',
    aprendizaje:'Empezar con un formato de inversión y movilidad distintos a una cafetería tradicional, contar la historia del producto y adaptarse ante cambios del mercado.',
    fuente:'https://rpp.pe/peru/cusco/dia-del-cafe-peruano-la-reinvencion-del-triciclo-cafetero-three-monkeys-en-cusco-noticia-1289301', fuenteTexto:'RPP: historia de Three Monkeys',
    fotos:['Three Monkeys triciclo cafetero Cusco','Three Monkeys bike coffee Cusco café']
  },
  {
    id:'qosqo-creme', nombre:'Qosqo Creme', sector:'Heladería y alimentos', ubicacion:'Cusco, Perú',
    resumen:'Emprendimiento de helados artesanales de fruta impulsado por el guía turístico cusqueño Efraín Valles.',
    historia:'Efraín Valles trabajaba como guía de turismo en Cusco desde hacía aproximadamente veinte años. Durante la pandemia de 2020, cuando la actividad turística se detuvo, empezó a preparar helados artesanales elaborados con fruta. Así convirtió una etapa difícil en una oportunidad para desarrollar un nuevo negocio.',
    comoEmpezo:'El fundador se reinventó durante la pandemia: comenzó a preparar helados de pura fruta y buscó que su emprendimiento creciera más allá del mercado local.',
    aprendizaje:'Identificar habilidades transferibles, reinventarse frente a una crisis y crear un producto con identidad propia.',
    fuente:'https://elcomercio.pe/vamos/consejos-de-viajes/el-mejor-guia-del-mundo-se-reinventa-conoce-su-emprendimiento-de-helados-artesanales-en-cusco-efrain-valles-cusco-qosqo-creme-helados-mejor-guia-del-mundo-wanderlust-coronavirus-pandemia-noticia/', fuenteTexto:'El Comercio: historia de Qosqo Creme',
    fotos:['Qosqo Creme Efraín Valles helados Cusco','Qosqo Creme helados artesanales fruta Cusco']
  },
  {
    id:'experiences', nombre:'Experiences Arte y Café', sector:'Cafetería, arte y experiencias culturales', ubicacion:'Plaza de Armas y calle Marqués, Cusco',
    resumen:'Cafetería cusqueña que combina café peruano de especialidad, brunch artesanal y talleres de arte andino.',
    historia:'El negocio nació con la visión de crear un espacio donde los visitantes puedan disfrutar café de especialidad y acercarse a la tradición artística de Cusco. Su propuesta integra café de origen, arte vivo y hospitalidad.',
    comoEmpezo:'Sus responsables plantearon una experiencia que va más allá de vender bebidas: integraron café de productores locales con talleres y actividades de cerámica y pintura cusqueña, en dos sedes del Centro Histórico.',
    aprendizaje:'Diferenciar un negocio combinando un producto principal con experiencias culturales y alianzas con productores locales.',
    fuente:'https://experiencesarteycafe.com/nosotros', fuenteTexto:'Experiences Arte y Café: historia oficial',
    fotos:['Experiences Arte y Café Cusco fachada','Experiences Arte y Café café filtrado Cusco']
  },
  {
    id:'pallay', nombre:'Pallay', sector:'Moda sostenible y textiles', ubicacion:'Cusco y comunidades altoandinas',
    resumen:'Marca de moda sostenible que revaloriza el tejido ancestral y trabaja con artesanas de comunidades altoandinas.',
    historia:'La iniciativa fue creada por Alejandra Rivera, quien dejó su carrera corporativa para desarrollar en Cusco una marca vinculada al arte textil andino. El proyecto articula el trabajo de más de cien tejedoras de cuatro comunidades altoandinas y comercializa piezas hechas a mano, incluyendo regalos corporativos y productos para hoteles.',
    comoEmpezo:'La fundadora decidió iniciar un proyecto propio en Cusco y conectar el talento de las tejedoras con clientes y mercados que valoran los productos artesanales y sostenibles.',
    aprendizaje:'Conectar productores locales con nuevos mercados, construir una marca con propósito y organizar una cadena de producción colaborativa.',
    fuente:'https://infomercado.pe/pallay-renuncio-al-mundo-corporativo-y-creo-una-marca-de-moda-sostenible-en-cusco-que-pone-en-valor-e-impulsa-el-talento-textil-andino/', fuenteTexto:'Infomercado: historia de Pallay',
    fotos:['Pallay marca textil Cusco Alejandra Rivera tejedoras','Pallay tejidos artesanales comunidades Cusco']
  }
];

let emprendimientoAbierto = null;

function obtenerGoogleImagenesUrl(idea) {
    return 'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(String(idea || '') + ' Cusco Perú');
}

function crearEnlaceFuente(url, texto) {
    const a = document.createElement('a');
    a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer';
    a.textContent = texto || 'Consultar fuente original';
    return a;
}

function mostrarDetalleEmprendimiento(negocio) {
    emprendimientoAbierto = negocio;
    document.getElementById('modalSectorEmprendimiento').textContent = negocio.sector;
    document.getElementById('modalTituloEmprendimiento').textContent = negocio.nombre;
    document.getElementById('modalUbicacionEmprendimiento').textContent = '📍 ' + negocio.ubicacion;
    const historia = document.getElementById('modalHistoriaEmprendimiento');
    historia.replaceChildren();
    const bloques = [
        ['¿Qué hace este emprendimiento?', negocio.resumen],
        ['¿Cómo nació?', negocio.historia],
        ['¿Cómo comenzó?', negocio.comoEmpezo],
        ['¿Qué puede aprender un nuevo emprendedor?', negocio.aprendizaje]
    ];
    bloques.forEach(([titulo, contenido]) => {
        const h = document.createElement('h3'); h.textContent = titulo; h.style.color = '#8b0000';
        const p = document.createElement('p'); p.textContent = contenido; p.style.lineHeight = '1.65';
        historia.append(h, p);
    });
    const fuentes = document.getElementById('modalFuentesEmprendimiento');
    fuentes.replaceChildren();
    const encabezado = document.createElement('strong'); encabezado.textContent = 'Fuentes consultadas';
    fuentes.append(encabezado, document.createElement('br'), crearEnlaceFuente(negocio.fuente, negocio.fuenteTexto));
    const visitar = crearEnlaceFuente(DESTINOS_EMPRENDIMIENTOS[negocio.id] || negocio.fuente, 'Visitar este emprendimiento');
    visitar.style.cssText = 'display:inline-block;margin-top:10px;font-weight:700;color:#0f766e;text-decoration:underline;';
    fuentes.append(document.createElement('br'), visitar);
    const modal = document.getElementById('modalEmprendimiento');
    modal.classList.add('abierto');
    document.body.style.overflow = 'hidden';
}

function cerrarDetalleEmprendimiento() {
    document.getElementById('modalEmprendimiento').classList.remove('abierto');
    document.body.style.overflow = '';
}

document.getElementById('cerrarModalEmprendimiento').addEventListener('click', cerrarDetalleEmprendimiento);
document.getElementById('btnCerrarModalAbajo').addEventListener('click', cerrarDetalleEmprendimiento);
document.getElementById('modalEmprendimiento').addEventListener('click', (e) => {
    if (e.target.id === 'modalEmprendimiento') cerrarDetalleEmprendimiento();
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarDetalleEmprendimiento(); });
document.getElementById('btnUsarEmprendimiento').addEventListener('click', () => {
    if (!emprendimientoAbierto) return;
    elegirIdea('Modelo de negocio inspirado en ' + emprendimientoAbierto.nombre + ' — ' + emprendimientoAbierto.sector);
    cerrarDetalleEmprendimiento();
});

function generarCatalogoPorRubro() {
    const grid = document.getElementById('gridIdeasCatalogo');
    if (!grid) return;
    grid.replaceChildren();
    urlsFotosYaUsadas.clear();
    const radio = document.querySelector('input[name="rubro_seleccionado"]:checked');
    let rubro = radio ? radio.value : 'Sin preferencia seleccionada';
    if (rubro === 'Otra idea (especificar)') {
        const texto = (document.getElementById('rubro_otro_texto')?.value || '').trim();
        rubro = texto ? 'Idea propia: ' + texto : 'Idea propia';
    }

    emprendimientosRealesCusco.forEach((negocio, indice) => {
        const card = document.createElement('article');
        card.className = 'idea-card';
        card.style.cssText = 'border:2px solid #e2e8f0;border-radius:14px;padding:15px;background:white;';

        const galeria = document.createElement('div');
        galeria.className = 'idea-galeria';
        galeria.style.cssText = 'display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;min-height:132px;background:#f8fafc;';
        galeria.textContent = 'Buscando fotografías del emprendimiento…';

        obtenerDosFotosCusco(negocio.fotos, negocio.nombre, negocio.sector, negocio.id).then(fotos => {
            galeria.replaceChildren();
            if (!fotos.length) {
                const aviso = document.createElement('p');
                aviso.textContent = 'No se encontró una fotografía directa verificada. Abre la página del emprendimiento para consultar sus imágenes.';
                aviso.style.cssText = 'grid-column:1/-1;color:#64748b;font-size:.82rem;padding:12px;';
                galeria.append(aviso);
            } else {
                fotos.forEach(foto => {
                    const cont = document.createElement('div');
                    const a = document.createElement('a'); a.href = DESTINOS_EMPRENDIMIENTOS[negocio.id] || negocio.fuente; a.target = '_blank'; a.rel = 'noopener noreferrer';
                    a.className = 'idea-foto';
                    const img = document.createElement('img'); img.src = foto.url; img.alt = 'Fotografía relacionada con ' + negocio.nombre;
                    img.loading = 'lazy'; img.referrerPolicy = 'strict-origin-when-cross-origin'; img.style.cssText = 'width:100%;height:130px;object-fit:cover;border-radius:8px;';
                    img.onerror = () => { a.replaceChildren(); a.textContent = 'Foto no disponible en el servidor externo. Abrir fuente original'; a.style.cssText = 'display:flex;min-height:90px;align-items:center;justify-content:center;padding:10px;background:#f1f5f9;color:#1d4ed8;font-size:.78rem;text-align:center;border-radius:8px;'; };
                    a.append(img);
                    const credito = document.createElement('small'); credito.textContent = foto.credito || 'Ver origen de la fotografía';
                    credito.style.cssText = 'display:block;color:#475569;font-size:.68rem;margin-top:4px;';
                    cont.append(a, credito); galeria.append(cont);
                });
            }
        }).catch(() => {
            galeria.textContent = 'No hay una fotografía directa verificada para este negocio. Visita su página para ver su galería.';
        });

        const sector = document.createElement('span'); sector.className = 'idea-sector'; sector.textContent = negocio.sector;
        const h3 = document.createElement('h3'); h3.textContent = negocio.nombre; h3.style.cssText = 'margin:5px 0;color:#8b0000;font-size:1.12rem;';
        const desc = document.createElement('p'); desc.textContent = negocio.resumen; desc.style.cssText = 'font-size:.88rem;color:#475569;line-height:1.5;';
        const ubic = document.createElement('p'); ubic.textContent = '📍 ' + negocio.ubicacion; ubic.style.cssText = 'font-size:.8rem;color:#64748b;margin:0;';
        const fuente = crearEnlaceFuente(negocio.fuente, 'Leer historia publicada'); fuente.style.cssText = 'font-size:.78rem;color:#1d4ed8;text-decoration:underline;';
        const google = crearEnlaceFuente(obtenerGoogleImagenesUrl(negocio.fotos.join(' ')), 'Ver fuente'); google.style.cssText = 'font-size:.78rem;color:#1d4ed8;text-decoration:underline;';
        const acciones = document.createElement('div'); acciones.className = 'idea-acciones';
        const detalle = document.createElement('button'); detalle.type = 'button'; detalle.className = 'btn btn-prev'; detalle.textContent = 'Conocer su historia';
        detalle.addEventListener('click', () => mostrarDetalleEmprendimiento(negocio));
        const visitar = document.createElement('a');
        visitar.href = DESTINOS_EMPRENDIMIENTOS[negocio.id] || negocio.fuente;
        visitar.target = '_blank'; visitar.rel = 'noopener noreferrer';
        visitar.className = 'btn btn-next'; visitar.textContent = 'Visitar emprendimiento ↗';
        visitar.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;text-decoration:none;';
        const elegir = document.createElement('button'); elegir.type = 'button'; elegir.className = 'btn btn-prev'; elegir.textContent = 'Elegir como referencia';
        elegir.addEventListener('click', () => elegirIdea('Modelo de negocio inspirado en ' + negocio.nombre + ' — ' + negocio.sector));
        acciones.append(visitar, detalle, elegir);
        card.style.cursor = 'pointer';
        card.addEventListener('click', (evento) => {
            if (evento.target.closest('a,button')) return;
            window.open(DESTINOS_EMPRENDIMIENTOS[negocio.id] || negocio.fuente, '_blank', 'noopener');
        });
        card.append(galeria, sector, h3, desc, ubic, fuente, acciones);
        grid.append(card);
    });

    document.getElementById('msgTestAnalizado').textContent =
        `Se muestran exactamente 10 emprendimientos reales documentados de Cusco. Tu respuesta del test (“${rubro}”) sirve como orientación; puedes revisar todos los sectores y elegir el caso que más te inspire.`;
}

function elegirIdea(titulo) {
    document.getElementById('ideaSeleccionadaPOST').value = titulo;
    document.getElementById('txtIdeaSeleccionada').textContent = '✓ Referencia seleccionada: ' + titulo;
    mostrarPaso(3);
}

function valorNumero(id) {
    const el = document.getElementById(id);
    return el ? Math.max(0, Number.parseFloat(el.value) || 0) : 0;
}
function dinero(n) {
    return 'S/ ' + (Number.isFinite(n) ? n : 0).toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function ponerTexto(id, texto) {
    const el = document.getElementById(id);
    if (el) el.textContent = texto;
}

let chartEquilibrioInstance = null;

function renderizarGraficoEquilibrio(precio, variableUnitario, fijos, cantidad, equilibrio) {
    const canvas = document.getElementById('chartEquilibrio');
    if (!canvas || typeof Chart === 'undefined') return;

    const ctx = canvas.getContext('2d');

    const maxUnidades = Math.max(100, Math.ceil((equilibrio ? equilibrio * 1.6 : cantidad * 1.5) / 10) * 10);
    const labels = [];
    const dataIngresos = [];
    const dataCostosTotales = [];
    const dataCostosFijos = [];
    const dataGanancias = [];
    const dataPerdidas = [];

    for (let u = 0; u <= maxUnidades; u += 10) {
        labels.push(u);
        const ing = u * precio;
        const cos = fijos + (u * variableUnitario);

        dataIngresos.push(ing);
        dataCostosTotales.push(cos);
        dataCostosFijos.push(fijos);

        if (equilibrio && u >= equilibrio) {
            dataGanancias.push(ing);
            dataPerdidas.push(null);
        } else {
            dataGanancias.push(null);
            dataPerdidas.push(cos);
        }
    }

    if (chartEquilibrioInstance) {
        chartEquilibrioInstance.destroy();
    }

    chartEquilibrioInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Zona de Ganancias',
                    data: dataGanancias,
                    backgroundColor: 'rgba(34, 197, 94, 0.25)',
                    fill: 'origin',
                    borderWidth: 0,
                    pointRadius: 0
                },
                {
                    label: 'Zona de Pérdidas',
                    data: dataPerdidas,
                    backgroundColor: 'rgba(239, 68, 68, 0.25)',
                    fill: 'origin',
                    borderWidth: 0,
                    pointRadius: 0
                },
                {
                    label: 'Ingresos Totales (S/)',
                    data: dataIngresos,
                    borderColor: '#16a34a',
                    backgroundColor: '#16a34a',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Costos Totales (S/)',
                    data: dataCostosTotales,
                    borderColor: '#dc2626',
                    backgroundColor: '#dc2626',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Costos Fijos (S/)',
                    data: dataCostosFijos,
                    borderColor: '#6b7280',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Soles (S/)' }
                },
                x: {
                    title: { display: true, text: 'Cantidad Vendida (Unidades de 10 en 10)' }
                }
            }
        }
    });
}

function calcularFinanzas() {
    const precio = valorNumero('precio_venta');
    const cantidad = valorNumero('ventas_estimadas') || valorNumero('unidades_mes');
    const materia = valorNumero('costos_variables');
    const empaque = valorNumero('costo_empaque_unitario');
    const manoObra = valorNumero('mano_obra_unitaria');
    const otrosVar = valorNumero('otros_variables_unitarios');
    const variableUnitario = materia + empaque + manoObra + otrosVar;
    const fijosBase = valorNumero('costos_fijos');
    const fijos = fijosBase + valorNumero('alquiler_mensual') + valorNumero('servicios_mensuales') +
        valorNumero('marketing_mensual') + valorNumero('transporte_mensual') + valorNumero('otros_fijos_mensuales');
    const ingresos = precio * cantidad;
    const costosVariablesTotal = variableUnitario * cantidad;
    const ganancia = ingresos - costosVariablesTotal - fijos;
    const margenContribucion = precio - variableUnitario;
    const equilibrio = margenContribucion > 0 ? Math.ceil(fijos / margenContribucion) : null;
    const puntoSoles = equilibrio === null ? null : equilibrio * precio;
    const inversionDesglosada = ['inversion_equipos','inversion_maquinaria','inversion_muebles','stock_inicial','permisos_iniciales','publicidad_lanzamiento','reserva_contingencia'].reduce((s,id)=>s+valorNumero(id),0);
    const inversion = Math.max(valorNumero('inversion_inicial'), inversionDesglosada);
    const precioSugerido = (variableUnitario + (cantidad > 0 ? fijos/cantidad : 0)) * (1 + valorNumero('margen_ganancia')/100) * (1 + valorNumero('impuesto_porcentaje')/100);
    ponerTexto('resIngresos', dinero(ingresos));
    ponerTexto('resCostosVar', dinero(costosVariablesTotal));
    ponerTexto('resCostosFijos', dinero(fijos));
    ponerTexto('resGanancia', dinero(ganancia));
    ponerTexto('resGananciaPositiva', dinero(Math.max(0, ganancia)));
    ponerTexto('resPerdida', dinero(Math.max(0, -ganancia)));
    const estadoFinanciero = ganancia > 0.005 ? 'GANANCIA' : ganancia < -0.005 ? 'PÉRDIDA' : 'EQUILIBRIO';
    ponerTexto('resEstadoFinanciero', estadoFinanciero);
    ponerTexto('resMontoResultado', dinero(Math.abs(ganancia)) + (ganancia < -0.005 ? ' de pérdida' : ganancia > 0.005 ? ' de ganancia' : ''));
    const diferenciaEquilibrio = equilibrio === null ? null : cantidad - equilibrio;
    ponerTexto('resDiferenciaEquilibrio', diferenciaEquilibrio === null ? 'No calculable' : (diferenciaEquilibrio > 0 ? '+' : '') + diferenciaEquilibrio.toLocaleString('es-PE') + ' unidades ' + (diferenciaEquilibrio > 0 ? 'sobre' : diferenciaEquilibrio < 0 ? 'por debajo' : 'en') + ' el punto de equilibrio');
    ponerTexto('resCostoVariableUnitario', dinero(variableUnitario));
    ponerTexto('resMargenContribucion', dinero(margenContribucion));
    ponerTexto('resPuntoUnidades', equilibrio === null ? 'No viable con estos datos' : equilibrio.toLocaleString('es-PE')+' unidades');
    ponerTexto('resPuntoSoles', puntoSoles === null ? 'No calculable' : dinero(puntoSoles));
    ponerTexto('resGananciaUnidad', dinero(precio-variableUnitario));
    ponerTexto('docIngresos', dinero(ingresos));
    ponerTexto('docCostosVariables', dinero(costosVariablesTotal));
    ponerTexto('docCostosFijos', dinero(fijos));
    ponerTexto('docGanancia', dinero(ganancia));
    ponerTexto('docGananciaPositiva', dinero(Math.max(0, ganancia)));
    ponerTexto('docPerdida', dinero(Math.max(0, -ganancia)));
    ponerTexto('docEstadoFinanciero', estadoFinanciero);
    ponerTexto('docInversion', dinero(inversion));
    ponerTexto('docInversionTotal', dinero(inversion));
    ponerTexto('docPrecio', dinero(precio));
    ponerTexto('docVentas', cantidad.toLocaleString('es-PE')+' unidades/mes');
    ponerTexto('docRecuperacion', ganancia > 0 ? (inversion/ganancia).toFixed(1)+' meses aprox.' : 'No calculable: utilidad no positiva');
    ponerTexto('docRentabilidad', ingresos > 0 ? (ganancia/ingresos*100).toFixed(1)+'%' : '0%');
    ponerTexto('docEquilibrio', equilibrio === null ? 'No viable con los valores actuales' : `${equilibrio} unidades al mes (${dinero(puntoSoles)})`);
    const textoPE = equilibrio === null
        ? 'No existe un punto de equilibrio viable con estos valores: el precio debe superar el costo variable por unidad.'
        : `Debes vender aproximadamente ${equilibrio} unidades al mes (${dinero(puntoSoles)}) para cubrir todos los costos. Con la proyección actual de ${cantidad} unidades, ${ganancia > 0 ? 'obtienes GANANCIAS porque superas el punto de equilibrio.' : ganancia < 0 ? 'obtienes PÉRDIDAS porque estás por debajo del punto de equilibrio.' : 'estás aproximadamente en EQUILIBRIO.'}`;
    ponerTexto('textoPuntoEquilibrio', textoPE);
    ponerTexto('docInterpretacionEquilibrio', textoPE);

    renderizarGraficoEquilibrio(precio, variableUnitario, fijos, cantidad, equilibrio);

    return {precio,cantidad,variableUnitario,fijos,ingresos,costosVariablesTotal,ganancia, equilibrio,puntoSoles,inversion,precioSugerido};
}

function actualizarDoc() {
    const f = calcularFinanzas();
    const get = id => (document.getElementById(id)?.value || '').trim() || 'Pendiente de completar';
    ponerTexto('docNombre', get('nombre'));
    ponerTexto('docUbicacion', `${get('distrito')}, provincia de ${get('provincia')}`);
    ponerTexto('docIdea', document.getElementById('ideaSeleccionadaPOST').value || 'No seleccionada');
    ponerTexto('docResumenEjecutivo', get('resumen_ejecutivo'));
    ponerTexto('docProducto', get('producto_servicio'));
    ponerTexto('docNecesidad', get('necesidad_cliente'));
    ponerTexto('docClientes', get('cliente_objetivo'));
    ponerTexto('docCompetidores', get('competidores'));
    ponerTexto('docOportunidad', get('oportunidad_mercado'));
    ponerTexto('docPProducto', get('estrategia_producto'));
    ponerTexto('docPPrecio', get('estrategia_precio'));
    ponerTexto('docPPlaza', get('estrategia_plaza'));
    ponerTexto('docPPromocion', get('estrategia_promocion'));
    ponerTexto('docPPersonal', get('estrategia_personal'));
    ponerTexto('docPProceso', get('estrategia_proceso'));
    ponerTexto('docPPresentacion', get('estrategia_presentacion'));
    ponerTexto('docEquipo', `${get('puesto_1')} (${dinero(valorNumero('sueldo_1'))}/mes); ${get('puesto_2')} (${dinero(valorNumero('sueldo_2'))}/mes); ${get('puesto_3')} (${dinero(valorNumero('sueldo_3'))}/mes)`);
    ponerTexto('docFinanciamiento', get('financiamiento'));
    ponerTexto('docMision', get('mision'));
    ponerTexto('docVision', get('vision'));
    ponerTexto('docFoda', `Fortalezas: ${get('fortalezas')}; Oportunidades: ${get('oportunidades')}; Debilidades: ${get('debilidades')}; Amenazas: ${get('amenazas')}`);
}

[
'inversion_inicial','precio_venta','ventas_estimadas','unidades_mes','costos_fijos','costos_variables',
'costo_empaque_unitario','mano_obra_unitaria','otros_variables_unitarios','alquiler_mensual',
'servicios_mensuales','marketing_mensual','transporte_mensual','otros_fijos_mensuales','margen_ganancia',
'impuesto_porcentaje','inversion_equipos','inversion_maquinaria','inversion_muebles','stock_inicial',
'permisos_iniciales','publicidad_lanzamiento','reserva_contingencia','sueldo_1','sueldo_2','sueldo_3'
].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => { calcularFinanzas(); if (pasoActual === 8) actualizarDoc(); });
});
document.querySelectorAll('#formEmprendimiento input, #formEmprendimiento textarea, #formEmprendimiento select').forEach(el => {
    el.addEventListener('input', () => { if (pasoActual === 8) actualizarDoc(); });
    el.addEventListener('change', () => { if (pasoActual === 8) actualizarDoc(); });
});
const inputUnidadesMes = document.getElementById('unidades_mes');
const inputVentasEstimadas = document.getElementById('ventas_estimadas');
if (inputUnidadesMes && inputVentasEstimadas) {
    inputUnidadesMes.addEventListener('input', () => { inputVentasEstimadas.value = inputUnidadesMes.value; calcularFinanzas(); });
    inputVentasEstimadas.addEventListener('input', () => { inputUnidadesMes.value = inputVentasEstimadas.value; calcularFinanzas(); });
}
window.addEventListener('resize', () => { if (pasoActual === 4 || pasoActual === 8) calcularFinanzas(); });

document.getElementById('btnSiguiente').addEventListener('click', () => {
    if (validarPasoActual()) {
        mostrarPaso(pasoActual + 1);
    }
});

document.getElementById('btnAnterior').addEventListener('click', () => mostrarPaso(pasoActual - 1));

botonesNav.forEach(btn => btn.addEventListener('click', () => {
    const irA = Number(btn.dataset.ir);
    if (irA < pasoActual || validarPasoActual()) {
        mostrarPaso(irA);
    }
}));

const campoOtroRubro = document.getElementById('campoRubroOtro');
const textoOtroRubro = document.getElementById('rubro_otro_texto');

function actualizarCampoOtraIdea() {
    const seleccion = document.querySelector('input[name="rubro_seleccionado"]:checked');
    const esOtraIdea = Boolean(seleccion && seleccion.value === 'Otra idea (especificar)');

    campoOtroRubro.style.display = esOtraIdea ? 'block' : 'none';
    textoOtroRubro.required = esOtraIdea;
    textoOtroRubro.setAttribute('aria-required', esOtraIdea ? 'true' : 'false');

    if (!esOtraIdea) {
        textoOtroRubro.setCustomValidity('');
    }
}

document.querySelectorAll('input[name="rubro_seleccionado"]').forEach(radio => {
    radio.addEventListener('change', () => {
        actualizarCampoOtraIdea();
        const campoIdeaSeleccionada = document.getElementById('ideaSeleccionadaPOST');
        if (campoIdeaSeleccionada) campoIdeaSeleccionada.value = '';
        const avisoIdea = document.getElementById('txtIdeaSeleccionada');
        if (avisoIdea) avisoIdea.textContent = 'Selecciona una de las ideas del catálogo.';
        if (typeof generarCatalogoPorRubro === 'function') {
            generarCatalogoPorRubro();
        }
    });
});

let temporizadorCatalogoPersonalizado = null;
textoOtroRubro.addEventListener('input', () => {
    textoOtroRubro.setCustomValidity('');
    window.clearTimeout(temporizadorCatalogoPersonalizado);
    temporizadorCatalogoPersonalizado = window.setTimeout(() => {
        if (typeof generarCatalogoPorRubro === 'function') {
            generarCatalogoPorRubro();
        }
    }, 600);
});

actualizarCampoOtraIdea();

mostrarPaso(0);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>