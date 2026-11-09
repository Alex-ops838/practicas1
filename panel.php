
<?php
/**
 * ==========================================================
 * EMPRENDE CUSCO - PANEL DEL USUARIO
 * Archivo: panel.php
 * Base de datos: emprende_cusco_db
 * ==========================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/config/conexion.php';

/* ==========================================================
   1. VALIDAR CONEXIÓN
========================================================== */

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa config/conexion.php.');
}

$conn->set_charset('utf8mb4');

/* ==========================================================
   2. VALIDAR SESIÓN
========================================================== */

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['usuario_id'];

/* ==========================================================
   3. FUNCIONES AUXILIARES
========================================================== */

function panelEsc($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Devuelve las columnas existentes de una tabla.
 */
function panelColumnas(mysqli $db, string $tabla): array
{
    $permitidas = [
        'usuarios',
        'colegios',
        'ideas',
        'ideas_negocio',
        'empleos'
    ];

    if (!in_array($tabla, $permitidas, true)) {
        return [];
    }

    $resultado = $db->query("SHOW COLUMNS FROM `$tabla`");

    if (!$resultado) {
        return [];
    }

    $columnas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $columnas[] = $fila['Field'];
    }

    $resultado->free();

    return $columnas;
}

/**
 * Busca la primera columna disponible entre varias alternativas.
 */
function panelElegirColumna(array $columnas, array $alternativas): ?string
{
    foreach ($alternativas as $columna) {
        if (in_array($columna, $columnas, true)) {
            return $columna;
        }
    }

    return null;
}

/**
 * Comprueba si existe una tabla.
 */
function panelExisteTabla(mysqli $db, string $tabla): bool
{
    $permitidas = [
        'usuarios',
        'colegios',
        'ideas',
        'ideas_negocio',
        'empleos'
    ];

    if (!in_array($tabla, $permitidas, true)) {
        return false;
    }

    $resultado = $db->query("SHOW TABLES LIKE '$tabla'");

    if (!$resultado) {
        return false;
    }

    $existe = $resultado->num_rows > 0;
    $resultado->free();

    return $existe;
}

/**
 * Cuenta registros reales de una tabla existente.
 */
function panelContar(mysqli $db, string $tabla): int
{
    if (!panelExisteTabla($db, $tabla)) {
        return 0;
    }

    $permitidas = [
        'ideas',
        'ideas_negocio',
        'empleos',
        'colegios'
    ];

    if (!in_array($tabla, $permitidas, true)) {
        return 0;
    }

    $resultado = $db->query("SELECT COUNT(*) AS total FROM `$tabla`");

    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();
    $resultado->free();

    return (int) ($fila['total'] ?? 0);
}

/* ==========================================================
   4. CONSULTAR ESTRUCTURA DE USUARIOS
========================================================== */

if (!panelExisteTabla($conn, 'usuarios')) {
    http_response_code(500);
    exit('No existe la tabla usuarios en la base de datos.');
}

$columnasUsuarios = panelColumnas($conn, 'usuarios');

$colId = panelElegirColumna(
    $columnasUsuarios,
    ['id', 'usuario_id']
);

$colNombres = panelElegirColumna(
    $columnasUsuarios,
    ['nombres', 'nombre']
);

$colApellidos = panelElegirColumna(
    $columnasUsuarios,
    ['apellidos', 'apellido']
);

$colCorreo = panelElegirColumna(
    $columnasUsuarios,
    ['correo', 'email']
);

$colRol = panelElegirColumna(
    $columnasUsuarios,
    ['rol', 'tipo_usuario']
);

$colColegio = panelElegirColumna(
    $columnasUsuarios,
    ['colegio_id']
);

$colGrado = panelElegirColumna(
    $columnasUsuarios,
    ['grado_estudio', 'grado']
);

$colPuntos = panelElegirColumna(
    $columnasUsuarios,
    ['puntos_gamificacion', 'puntos']
);

$colFoto = panelElegirColumna(
    $columnasUsuarios,
    ['foto_perfil', 'foto']
);

$colBio = panelElegirColumna(
    $columnasUsuarios,
    ['bio', 'descripcion']
);

$colEstado = panelElegirColumna(
    $columnasUsuarios,
    ['estado']
);

if (!$colId) {
    http_response_code(500);
    exit('La tabla usuarios necesita una columna id o usuario_id.');
}

/* ==========================================================
   5. CONSTRUIR CONSULTA DEL PERFIL
========================================================== */

$campos = [
    "u.`$colId` AS id"
];

$campos[] = $colNombres
    ? "u.`$colNombres` AS nombres"
    : "'' AS nombres";

$campos[] = $colApellidos
    ? "u.`$colApellidos` AS apellidos"
    : "'' AS apellidos";

$campos[] = $colCorreo
    ? "u.`$colCorreo` AS correo"
    : "'' AS correo";

$campos[] = $colRol
    ? "u.`$colRol` AS rol"
    : "'estudiante' AS rol";

$campos[] = $colGrado
    ? "u.`$colGrado` AS grado_estudio"
    : "'' AS grado_estudio";

$campos[] = $colPuntos
    ? "u.`$colPuntos` AS puntos_gamificacion"
    : "0 AS puntos_gamificacion";

$campos[] = $colFoto
    ? "u.`$colFoto` AS foto_perfil"
    : "'' AS foto_perfil";

$campos[] = $colBio
    ? "u.`$colBio` AS bio"
    : "'' AS bio";

$campos[] = $colEstado
    ? "u.`$colEstado` AS estado"
    : "'' AS estado";

$usarColegio = (
    $colColegio !== null
    && panelExisteTabla($conn, 'colegios')
);

if ($usarColegio) {
    $columnasColegios = panelColumnas($conn, 'colegios');

    if (
        in_array('id', $columnasColegios, true)
        && in_array('nombre', $columnasColegios, true)
    ) {
        $campos[] = 'c.nombre AS colegio_nombre';

        $colProvincia = panelElegirColumna(
            $columnasColegios,
            ['provincia']
        );

        $campos[] = $colProvincia
            ? "c.`$colProvincia` AS colegio_provincia"
            : "'' AS colegio_provincia";
    } else {
        $usarColegio = false;
    }
}

$sql = 'SELECT ' . implode(', ', $campos) .
       ' FROM usuarios u ';

if ($usarColegio) {
    $sql .= ' LEFT JOIN colegios c ON u.`' .
            $colColegio . '` = c.id ';
}

$sql .= ' WHERE u.`' . $colId . '` = ? LIMIT 1';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Error preparando panel: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo preparar la consulta del perfil. Revisa la estructura de usuarios.');
}

$stmt->bind_param('i', $userId);

if (!$stmt->execute()) {
    error_log('Error consultando panel: ' . $stmt->error);
    $stmt->close();

    http_response_code(500);
    exit('No se pudieron consultar los datos del usuario.');
}

$resultado = $stmt->get_result();
$datos = $resultado ? $resultado->fetch_assoc() : null;

$stmt->close();

if (!$datos) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php');
    exit;
}

/* ==========================================================
   6. PREPARAR DATOS DEL PERFIL
========================================================== */

$nombreCompleto = trim(
    ($datos['nombres'] ?? '') . ' ' .
    ($datos['apellidos'] ?? '')
);

if ($nombreCompleto === '') {
    $nombreCompleto = 'Usuario';
}

$correoUsuario = $datos['correo'] ?? '';

$rol = $datos['rol'] ?? 'estudiante';

$colegio = trim($datos['colegio_nombre'] ?? '');

if ($colegio === '') {
    $colegio = 'No especificado';
} elseif (!empty($datos['colegio_provincia'])) {
    $colegio .= ' - ' . $datos['colegio_provincia'];
}

$grado = trim($datos['grado_estudio'] ?? '');

if ($grado === '') {
    $grado = 'No especificado';
}

$puntos = (int) ($datos['puntos_gamificacion'] ?? 0);

$fotoPerfil = trim($datos['foto_perfil'] ?? '');

$bio = trim($datos['bio'] ?? '');

/* Mantener el nombre disponible para el encabezado. */
$_SESSION['usuario_nombre'] = $nombreCompleto;
$_SESSION['usuario_email'] = $correoUsuario;
$_SESSION['usuario_rol'] = $rol;

/* ==========================================================
   7. CONTADORES DE LA BASE DE DATOS
========================================================== */

$totalIdeas = panelExisteTabla($conn, 'ideas')
    ? panelContar($conn, 'ideas')
    : panelContar($conn, 'ideas_negocio');

$totalEmpleos = panelContar($conn, 'empleos');

$totalColegios = panelContar($conn, 'colegios');

/* ==========================================================
   8. CONSULTAR IDEAS PUBLICADAS
========================================================== */

$ideas = [];

$tablaIdeas = panelExisteTabla($conn, 'ideas')
    ? 'ideas'
    : (panelExisteTabla($conn, 'ideas_negocio')
        ? 'ideas_negocio'
        : '');

if ($tablaIdeas !== '') {
    $columnasIdeas = panelColumnas($conn, $tablaIdeas);

    $colTituloIdea = panelElegirColumna(
        $columnasIdeas,
        ['titulo', 'nombre', 'nombre_idea']
    );

    $colCategoriaIdea = panelElegirColumna(
        $columnasIdeas,
        ['categoria', 'rubro']
    );

    $colDescripcionIdea = panelElegirColumna(
        $columnasIdeas,
        ['descripcion', 'detalle']
    );

    $colInversionIdea = panelElegirColumna(
        $columnasIdeas,
        ['inversion', 'presupuesto_min']
    );

    $colUbicacionIdea = panelElegirColumna(
        $columnasIdeas,
        ['ubicacion', 'provincia', 'distrito']
    );

    $colEstadoIdea = panelElegirColumna(
        $columnasIdeas,
        ['estado']
    );

    if ($colTituloIdea) {
        $selectIdeas = [
            "`$colTituloIdea` AS titulo",
            $colCategoriaIdea
                ? "`$colCategoriaIdea` AS categoria"
                : "'' AS categoria",
            $colDescripcionIdea
                ? "`$colDescripcionIdea` AS descripcion"
                : "'' AS descripcion",
            $colInversionIdea
                ? "`$colInversionIdea` AS inversion"
                : 'NULL AS inversion',
            $colUbicacionIdea
                ? "`$colUbicacionIdea` AS ubicacion"
                : "'' AS ubicacion"
        ];

        $sqlIdeas = 'SELECT ' . implode(', ', $selectIdeas) .
                    " FROM `$tablaIdeas`";

        if ($colEstadoIdea) {
            $sqlIdeas .= " WHERE LOWER(`$colEstadoIdea`) IN
                ('publicado', 'publicada', 'activo', 'activa', 'aprobado', 'aprobada')";
        }

        $sqlIdeas .= ' ORDER BY 1 LIMIT 6';

        $resIdeas = $conn->query($sqlIdeas);

        if ($resIdeas) {
            while ($idea = $resIdeas->fetch_assoc()) {
                $ideas[] = $idea;
            }

            $resIdeas->free();
        }
    }
}

/* ==========================================================
   9. CONSULTAR EMPLEOS
========================================================== */

$empleos = [];

if (panelExisteTabla($conn, 'empleos')) {
    $columnasEmpleos = panelColumnas($conn, 'empleos');

    $colTituloEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['titulo', 'puesto', 'nombre', 'cargo']
    );

    $colEmpresaEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['empresa', 'nombre_empresa', 'empleador']
    );

    $colUbicacionEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['ubicacion', 'provincia', 'departamento', 'lugar']
    );

    $colModalidadEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['modalidad']
    );

    $colSalarioEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['salario', 'sueldo', 'remuneracion']
    );

    $colEstadoEmpleo = panelElegirColumna(
        $columnasEmpleos,
        ['estado']
    );

    if ($colTituloEmpleo) {
        $selectEmpleos = [
            "`$colTituloEmpleo` AS titulo",
            $colEmpresaEmpleo
                ? "`$colEmpresaEmpleo` AS empresa"
                : "'' AS empresa",
            $colUbicacionEmpleo
                ? "`$colUbicacionEmpleo` AS ubicacion"
                : "'' AS ubicacion",
            $colModalidadEmpleo
                ? "`$colModalidadEmpleo` AS modalidad"
                : "'' AS modalidad",
            $colSalarioEmpleo
                ? "`$colSalarioEmpleo` AS salario"
                : "'' AS salario"
        ];

        $sqlEmpleos = 'SELECT ' . implode(', ', $selectEmpleos) .
                      ' FROM empleos';

        if ($colEstadoEmpleo) {
            $sqlEmpleos .= " WHERE LOWER(`$colEstadoEmpleo`) IN
                ('activo', 'activa', 'publicado', 'publicada', 'aprobado', 'aprobada')";
        }

        $sqlEmpleos .= ' ORDER BY 1 LIMIT 6';

        $resEmpleos = $conn->query($sqlEmpleos);

        if ($resEmpleos) {
            while ($empleo = $resEmpleos->fetch_assoc()) {
                $empleos[] = $empleo;
            }

            $resEmpleos->free();
        }
    }
}

/* ==========================================================
   10. CARGAR ENCABEZADO
========================================================== */

include __DIR__ . '/includes/header.php';
?>

<style>
.panel-ec {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px 55px;
    color: #262626;
}

.panel-hero {
    background: linear-gradient(135deg, #8b0000, #5f0710);
    color: #fff;
    border-radius: 18px;
    padding: 32px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
    box-shadow: 0 10px 28px rgba(100, 0, 0, .15);
}

.panel-user {
    display: flex;
    align-items: center;
    gap: 18px;
}

.panel-avatar {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background: #fff;
    color: #8b0000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: 800;
    overflow: hidden;
    flex-shrink: 0;
}

.panel-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.panel-hero h1 {
    font-size: clamp(1.5rem, 3vw, 2.15rem);
    margin: 8px 0;
    color: #fff;
}

.panel-hero p {
    margin: 7px 0;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.panel-role {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(255,255,255,.17);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 30px;
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.panel-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 9px;
    text-decoration: none;
    font-weight: 700;
    border: 0;
    cursor: pointer;
    transition: .2s;
}

.panel-btn:hover {
    transform: translateY(-2px);
}

.panel-btn-white {
    background: #fff;
    color: #8b0000;
}

.panel-btn-red {
    background: #8b0000;
    color: #fff;
}

.panel-section {
    margin-top: 38px;
}

.panel-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.panel-section-head h2 {
    margin: 0;
    color: #242424;
    font-size: 1.5rem;
}

.panel-section-head p {
    color: #6b7280;
    margin: 6px 0 0;
}

.panel-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.panel-stat {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 16px rgba(0,0,0,.035);
}

.panel-stat-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #fff0f0;
    color: #8b0000;
    font-size: 19px;
    margin-bottom: 13px;
}

.panel-stat strong {
    display: block;
    font-size: 1.9rem;
    color: #222;
}

.panel-stat span {
    color: #6b7280;
    font-size: .92rem;
}

.panel-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.panel-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,.035);
    min-width: 0;
}

.panel-card h3 {
    margin: 10px 0;
    font-size: 1.08rem;
    overflow-wrap: anywhere;
}

.panel-card p {
    color: #5f6368;
    line-height: 1.6;
    font-size: .93rem;
    overflow-wrap: anywhere;
}

.panel-tag {
    display: inline-block;
    color: #8b0000;
    background: #fff0f0;
    border-radius: 20px;
    padding: 5px 10px;
    font-size: .75rem;
    font-weight: 800;
}

.panel-meta {
    border-top: 1px solid #eee;
    padding-top: 12px;
    margin-top: 14px;
    color: #555;
    font-size: .88rem;
    line-height: 1.7;
}

.panel-empty {
    background: #fff;
    border: 1px dashed #d4d4d4;
    padding: 25px;
    text-align: center;
    border-radius: 14px;
    color: #666;
}

.panel-job {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 12px;
}

.panel-job h3 {
    margin: 0 0 7px;
    font-size: 1.05rem;
}

.panel-job p {
    margin: 4px 0;
    color: #666;
    font-size: .9rem;
}

.panel-bio {
    margin-top: 10px;
    font-size: .94rem;
    opacity: .95;
    max-width: 720px;
}

@media (max-width: 850px) {
    .panel-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .panel-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .panel-ec {
        padding: 20px 12px 35px;
    }

    .panel-hero {
        padding: 22px 17px;
    }

    .panel-user {
        align-items: flex-start;
    }

    .panel-avatar {
        width: 58px;
        height: 58px;
        font-size: 22px;
    }

    .panel-grid,
    .panel-stats {
        grid-template-columns: 1fr;
    }

    .panel-section-head h2 {
        font-size: 1.25rem;
    }
}
</style>

<main class="panel-ec">

    <!-- PERFIL DEL USUARIO -->
    <section class="panel-hero">

        <div class="panel-user">

            <div class="panel-avatar">
                <?php if ($fotoPerfil !== ''): ?>
                    <img
                        src="<?php echo panelEsc($fotoPerfil); ?>"
                        alt="Foto de perfil"
                    >
                <?php else: ?>
                    <?php
                    $iniciales = strtoupper(
                        substr($nombreCompleto, 0, 1)
                    );
                    echo panelEsc($iniciales);
                    ?>
                <?php endif; ?>
            </div>

            <div>
                <span class="panel-role">
                    <i class="fa-solid fa-user-check"></i>
                    <?php echo panelEsc($rol); ?>
                </span>

                <h1>
                    ¡Hola, <?php echo panelEsc($nombreCompleto); ?>!
                </h1>

                <p>
                    <i class="fa-solid fa-envelope"></i>
                    <?php echo panelEsc($correoUsuario ?: 'Correo no registrado'); ?>
                </p>

                <p>
                    <i class="fa-solid fa-school"></i>
                    Colegio: <strong><?php echo panelEsc($colegio); ?></strong>
                    &nbsp;|&nbsp;
                    <i class="fa-solid fa-book"></i>
                    Grado: <strong><?php echo panelEsc($grado); ?></strong>
                </p>

                <p>
                    <i class="fa-solid fa-star"></i>
                    Puntos: <strong><?php echo $puntos; ?></strong>
                </p>

                <?php if ($bio !== ''): ?>
                    <p class="panel-bio">
                        <?php echo panelEsc($bio); ?>
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <a href="logout.php" class="panel-btn panel-btn-white">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar sesión
        </a>

    </section>

    <!-- MÉTRICAS REALES -->
    <section class="panel-stats">

        <article class="panel-stat">
            <div class="panel-stat-icon">
                <i class="fa-solid fa-lightbulb"></i>
            </div>

            <strong><?php echo $totalIdeas; ?></strong>
            <span>Ideas registradas en el catálogo</span>
        </article>

        <article class="panel-stat">
            <div class="panel-stat-icon">
                <i class="fa-solid fa-briefcase"></i>
            </div>

            <strong><?php echo $totalEmpleos; ?></strong>
            <span>Empleos registrados</span>
        </article>

        <article class="panel-stat">
            <div class="panel-stat-icon">
                <i class="fa-solid fa-school"></i>
            </div>

            <strong><?php echo $totalColegios; ?></strong>
            <span>Colegios registrados</span>
        </article>

    </section>

    <!-- CATÁLOGO DE IDEAS -->
    <section class="panel-section">

        <div class="panel-section-head">
            <div>
                <h2>
                    <i class="fa-solid fa-lightbulb"></i>
                    Ideas de negocio
                </h2>
                <p>Consulta las ideas guardadas en la base de datos.</p>
            </div>

            <a href="ideas.php" class="panel-btn panel-btn-red">
                Ver todas las ideas
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (count($ideas) > 0): ?>

            <div class="panel-grid">

                <?php foreach ($ideas as $idea): ?>

                    <article class="panel-card">

                        <span class="panel-tag">
                            <?php echo panelEsc(
                                $idea['categoria'] ?: 'Idea de negocio'
                            ); ?>
                        </span>

                        <h3>
                            <?php echo panelEsc($idea['titulo']); ?>
                        </h3>

                        <p>
                            <?php
                            $descripcion = trim(
                                (string) ($idea['descripcion'] ?? '')
                            );

                            if ($descripcion === '') {
                                $descripcion = 'Consulta los detalles de esta idea en el catálogo.';
                            }

                            echo panelEsc(
                                mb_strimwidth($descripcion, 0, 180, '...')
                            );
                            ?>
                        </p>

                        <div class="panel-meta">

                            <?php if (
                                isset($idea['inversion'])
                                && $idea['inversion'] !== ''
                                && $idea['inversion'] !== null
                            ): ?>
                                <div>
                                    <i class="fa-solid fa-coins"></i>
                                    Inversión: S/
                                    <?php echo panelEsc($idea['inversion']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($idea['ubicacion'])): ?>
                                <div>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?php echo panelEsc($idea['ubicacion']); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="panel-empty">
                <i class="fa-solid fa-lightbulb"></i>
                <p>Todavía no hay ideas publicadas para mostrar.</p>

                <a href="ideas.php" class="panel-btn panel-btn-red">
                    Explorar ideas
                </a>
            </div>

        <?php endif; ?>

    </section>

    <!-- BOLSA DE EMPLEO -->
    <section class="panel-section">

        <div class="panel-section-head">
            <div>
                <h2>
                    <i class="fa-solid fa-briefcase"></i>
                    Bolsa de empleo
                </h2>

                <p>Oportunidades registradas en el sistema.</p>
            </div>

            <a href="empleos.php" class="panel-btn panel-btn-red">
                Ver empleos
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (count($empleos) > 0): ?>

            <?php foreach ($empleos as $empleo): ?>

                <article class="panel-job">

                    <div>
                        <h3>
                            <?php echo panelEsc($empleo['titulo']); ?>
                        </h3>

                        <?php if (!empty($empleo['empresa'])): ?>
                            <p>
                                <i class="fa-solid fa-building"></i>
                                <?php echo panelEsc($empleo['empresa']); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <i class="fa-solid fa-location-dot"></i>
                            <?php echo panelEsc(
                                $empleo['ubicacion'] ?: 'Ubicación no especificada'
                            ); ?>

                            <?php if (!empty($empleo['modalidad'])): ?>
                                &nbsp;|&nbsp;
                                <i class="fa-solid fa-laptop"></i>
                                <?php echo panelEsc($empleo['modalidad']); ?>
                            <?php endif; ?>
                        </p>

                        <?php if (
                            isset($empleo['salario'])
                            && $empleo['salario'] !== ''
                            && $empleo['salario'] !== null
                        ): ?>
                            <p>
                                <i class="fa-solid fa-money-bill-wave"></i>
                                Remuneración: <?php echo panelEsc($empleo['salario']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <a href="empleos.php" class="panel-btn panel-btn-red">
                        Ver oportunidad
                    </a>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="panel-empty">
                <i class="fa-solid fa-briefcase"></i>
                <p>No hay empleos disponibles para mostrar.</p>

                <a href="empleos.php" class="panel-btn panel-btn-red">
                    Consultar bolsa de empleo
                </a>
            </div>

        <?php endif; ?>

    </section>

    <!-- ACCESOS RÁPIDOS -->
    <section class="panel-section">

        <div class="panel-section-head">
            <div>
                <h2>Accesos rápidos</h2>
                <p>Continúa usando Emprende Cusco.</p>
            </div>
        </div>

        <div class="panel-grid">

            <article class="panel-card">
                <div class="panel-stat-icon">
                    <i class="fa-solid fa-house"></i>
                </div>

                <h3>Inicio</h3>

                <p>Regresa a la página principal de Emprende Cusco.</p>

                <a href="index.php" class="panel-btn panel-btn-red">
                    Ir al inicio
                </a>
            </article>

            <article class="panel-card">
                <div class="panel-stat-icon">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>

                <h3>Ideas de negocio</h3>

                <p>Explora oportunidades y propuestas de emprendimiento.</p>

                <a href="ideas.php" class="panel-btn panel-btn-red">
                    Explorar ideas
                </a>
            </article>

            <article class="panel-card">
                <div class="panel-stat-icon">
                    <i class="fa-solid fa-briefcase"></i>
                </div>

                <h3>Empleos</h3>

                <p>Consulta las oportunidades laborales disponibles.</p>

                <a href="empleos.php" class="panel-btn panel-btn-red">
                    Buscar empleos
                </a>
            </article>

        </div>

    </section>

</main>

<?php
include __DIR__ . '/includes/footer.php';
?>
