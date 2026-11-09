
<?php
/**
 * =========================================================
 * EMPRENDE CUSCO - REGISTRO DE USUARIOS
 * Compatible con PHP 8 y MySQL / XAMPP
 * Base de datos: emprende_cusco_db
 * =========================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/config/conexion.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log('registro.php: no se encontró la conexión mysqli $conn.');
    exit('No se pudo conectar con la base de datos.');
}

$conn->set_charset('utf8mb4');

/* =========================================================
   FUNCIONES
   ========================================================= */

function e($valor): string
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================================
   PROTECCIÓN CSRF
   ========================================================= */

if (
    !isset($_SESSION['token_registro']) ||
    !is_string($_SESSION['token_registro']) ||
    strlen($_SESSION['token_registro']) < 32
) {
    $_SESSION['token_registro'] = bin2hex(random_bytes(32));
}

/* =========================================================
   VARIABLES DEL FORMULARIO
   ========================================================= */

$errores = [];

$nombre = '';
$email = '';
$celular = '';
$edad = '';
$rol = 'estudiante';
$colegio_id = '';

$colegios = [];

/* =========================================================
   CARGAR COLEGIOS DE CUSCO
   ========================================================= */

try {
    $sqlColegios = "
        SELECT id, nombre, departamento, provincia, distrito
        FROM colegios
        WHERE LOWER(TRIM(COALESCE(departamento, '')))
              IN ('cusco', 'cuzco')
        ORDER BY provincia, distrito, nombre
    ";

    $resultado = $conn->query($sqlColegios);

    while ($fila = $resultado->fetch_assoc()) {
        $colegios[] = $fila;
    }

    $resultado->free();

} catch (mysqli_sql_exception $ex) {
    error_log('registro.php - Error al cargar colegios: '
        . $ex->getMessage());

    $errores[] =
        'No se pudo cargar la lista de colegios. '
        . 'Comprueba la tabla colegios de tu base de datos.';
}

/* =========================================================
   PROCESAR FORMULARIO
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $celular = trim((string)($_POST['celular'] ?? ''));
    $edad = trim((string)($_POST['edad'] ?? ''));

    $rol = trim((string)($_POST['rol'] ?? 'estudiante'));

    $colegio_id = trim(
        (string)($_POST['colegio_id'] ?? '')
    );

    $password = (string)($_POST['password'] ?? '');

    $confirmar_password =
        (string)($_POST['confirmar_password'] ?? '');

    $token = $_POST['token_registro'] ?? '';

    /* -----------------------------------------------------
       VALIDAR TOKEN
       ----------------------------------------------------- */

    $tokenValido = (
        is_string($token) &&
        isset($_SESSION['token_registro']) &&
        is_string($_SESSION['token_registro']) &&
        hash_equals($_SESSION['token_registro'], $token)
    );

    if (!$tokenValido) {
        $errores[] =
            'La sesión del formulario ha expirado. '
            . 'Recarga la página e inténtalo nuevamente.';
    }

    /* -----------------------------------------------------
       VALIDAR NOMBRE
       ----------------------------------------------------- */

    if (
        mb_strlen($nombre, 'UTF-8') < 2 ||
        mb_strlen($nombre, 'UTF-8') > 150
    ) {
        $errores[] =
            'Ingresa tu nombre completo, entre 2 y 150 caracteres.';
    }

    /* -----------------------------------------------------
       VALIDAR CORREO
       ----------------------------------------------------- */

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {
        $errores[] =
            'Ingresa un correo electrónico válido de máximo 190 caracteres.';
    }

    /* -----------------------------------------------------
       VALIDAR CELULAR PERUANO
       ----------------------------------------------------- */

    if (!preg_match('/^9[0-9]{8}$/', $celular)) {
        $errores[] =
            'El celular debe tener 9 dígitos y comenzar con 9.';
    }

    /* -----------------------------------------------------
       VALIDAR EDAD
       ----------------------------------------------------- */

    if (
        $edad !== '' &&
        (
            !ctype_digit($edad) ||
            (int)$edad < 14 ||
            (int)$edad > 100
        )
    ) {
        $errores[] =
            'La edad debe estar entre 14 y 100 años.';
    }

    /* -----------------------------------------------------
       VALIDAR ROL
       ----------------------------------------------------- */

    $rolesPermitidos = [
        'estudiante',
        'emprendedor',
        'trabajador'
    ];

    if (!in_array($rol, $rolesPermitidos, true)) {
        $errores[] = 'Selecciona un tipo de usuario válido.';
    }

    /* -----------------------------------------------------
       VALIDAR CONTRASEÑA
       ----------------------------------------------------- */

    if (strlen($password) < 8) {
        $errores[] =
            'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($password !== $confirmar_password) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    /* -----------------------------------------------------
       VALIDAR COLEGIO SELECCIONADO
       ----------------------------------------------------- */

    $colegioValido = false;

    if (
        $colegio_id !== '' &&
        ctype_digit($colegio_id) &&
        (int)$colegio_id > 0
    ) {
        try {
            $idColegio = (int)$colegio_id;

            $stmtColegio = $conn->prepare("
                SELECT id
                FROM colegios
                WHERE id = ?
                  AND LOWER(TRIM(COALESCE(departamento, '')))
                      IN ('cusco', 'cuzco')
                LIMIT 1
            ");

            $stmtColegio->bind_param('i', $idColegio);
            $stmtColegio->execute();

            $resultadoColegio = $stmtColegio->get_result();

            $colegioValido =
                ($resultadoColegio->num_rows === 1);

            $stmtColegio->close();

        } catch (mysqli_sql_exception $ex) {
            error_log(
                'registro.php - Error verificando colegio: '
                . $ex->getMessage()
            );

            $errores[] =
                'No se pudo verificar el colegio seleccionado.';
        }
    }

    if (!$colegioValido) {
        $errores[] =
            'Selecciona un colegio válido de la lista de Cusco.';
    }

    /* =====================================================
       COMPROBAR CORREO Y CELULAR POR SEPARADO
       ===================================================== */

    if (empty($errores)) {
        try {
            $stmtDuplicados = $conn->prepare("
                SELECT email, celular
                FROM usuarios
                WHERE email = ? OR celular = ?
            ");

            $stmtDuplicados->bind_param(
                'ss',
                $email,
                $celular
            );

            $stmtDuplicados->execute();

            $resultadoDuplicados =
                $stmtDuplicados->get_result();

            $correoExiste = false;
            $celularExiste = false;

            while (
                $usuarioExistente =
                    $resultadoDuplicados->fetch_assoc()
            ) {
                /*
                 * La comparación del correo no distingue
                 * mayúsculas y minúsculas.
                 */
                if (
                    strcasecmp(
                        trim((string)$usuarioExistente['email']),
                        $email
                    ) === 0
                ) {
                    $correoExiste = true;
                }

                if (
                    (string)$usuarioExistente['celular']
                    === $celular
                ) {
                    $celularExiste = true;
                }
            }

            $stmtDuplicados->close();

            if ($correoExiste && $celularExiste) {
                $errores[] =
                    'El correo electrónico y el celular '
                    . 'ya están registrados. Utiliza otros datos '
                    . 'o inicia sesión con tu cuenta existente.';

            } elseif ($correoExiste) {
                $errores[] =
                    'Este correo electrónico ya está registrado. '
                    . 'Utiliza otro correo o inicia sesión.';

            } elseif ($celularExiste) {
                $errores[] =
                    'Este número de celular ya está registrado. '
                    . 'Utiliza otro número o inicia sesión.';
            }

        } catch (mysqli_sql_exception $ex) {
            error_log(
                'registro.php - Error verificando duplicados: '
                . $ex->getMessage()
            );

            $errores[] =
                'No se pudieron verificar los datos del registro. '
                . 'Inténtalo nuevamente.';
        }
    }

    /* =====================================================
       INSERTAR USUARIO
       ===================================================== */

    if (empty($errores)) {
        try {
            /*
             * Detectar las columnas existentes para adaptarse
             * a la estructura actual de la tabla usuarios.
             */
            $columnas = [];

            $resultadoColumnas =
                $conn->query("SHOW COLUMNS FROM usuarios");

            while ($filaColumna =
                $resultadoColumnas->fetch_assoc()
            ) {
                $columnas[$filaColumna['Field']] = $filaColumna;
            }

            $resultadoColumnas->free();

            /*
             * Identificar la columna de contraseña.
             */
            $colPassword = null;

            foreach (
                [
                    'password',
                    'contrasena_hash',
                    'contrasena',
                    'clave'
                ] as $posibleColumna
            ) {
                if (isset($columnas[$posibleColumna])) {
                    $colPassword = $posibleColumna;
                    break;
                }
            }

            if ($colPassword === null) {
                throw new RuntimeException(
                    'No se encontró una columna compatible '
                    . 'para almacenar la contraseña.'
                );
            }

            /*
             * Preparar los datos del usuario.
             */
            $hashPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($hashPassword === false) {
                throw new RuntimeException(
                    'No se pudo proteger la contraseña.'
                );
            }

           $datosInsertar = [
    'nombre'     => $nombre,
    'nombres'    => $nombre,
    'email'      => $email,
    'correo'     => $email,
    'celular'    => $celular,
    $colPassword => $hashPassword,
    'rol'        => $rol,
    'colegio_id' => (int)$colegio_id
];

/* Guardar solo una columna para el nombre y otra para el correo. */
if (isset($columnas['nombres'])) {
    unset($datosInsertar['nombre']);
}

if (isset($columnas['correo'])) {
    unset($datosInsertar['email']);
}

/* Guardar la edad si existe la columna. */
if (isset($columnas['edad']) && $edad !== '') {
    $datosInsertar['edad'] = (int)$edad;
}

/* Guardar la fecha si existe la columna. */
foreach (['created_at', 'creado_en', 'fecha_registro'] as $colFecha) {
    if (isset($columnas[$colFecha])) {
        $datosInsertar[$colFecha] = date('Y-m-d H:i:s');
        break;
    }
}

/* Generar los tipos según los valores. */
$tipos = '';

foreach ($datosInsertar as $columna => $valor) {
    if (is_int($valor)) {
        $tipos .= 'i';
    } else {
        $tipos .= 's';
    }
}

            $tipos = 'sssssi';

            /*
             * Edad es opcional si existe esa columna.
             */
            if (
                isset($columnas['edad']) &&
                $edad !== ''
            ) {
                $datosInsertar['edad'] = (int)$edad;
                $tipos .= 'i';
            }

            /*
             * Agregar fecha solamente si la columna existe.
             */
            foreach (
                ['created_at', 'creado_en', 'fecha_registro']
                as $colFecha
            ) {
                if (isset($columnas[$colFecha])) {
                    $datosInsertar[$colFecha] =
                        date('Y-m-d H:i:s');

                    $tipos .= 's';
                    break;
                }
            }

            /*
             * Verificar que las columnas necesarias existan.
             */
            foreach (array_keys($datosInsertar) as $columna) {
                if (!isset($columnas[$columna])) {
                    throw new RuntimeException(
                        'Falta una columna requerida en usuarios: '
                        . $columna
                    );
                }
            }

            /*
             * Construir la consulta con marcadores.
             * Los valores siempre se envían por parámetros.
             */
            $listaColumnas = implode(
                ', ',
                array_map(
                    function ($columna) {
                        return '`' . str_replace(
                            '`',
                            '``',
                            $columna
                        ) . '`';
                    },
                    array_keys($datosInsertar)
                )
            );

            $marcadores = implode(
                ', ',
                array_fill(
                    0,
                    count($datosInsertar),
                    '?'
                )
            );

            $sqlInsertar = "
                INSERT INTO usuarios ($listaColumnas)
                VALUES ($marcadores)
            ";

            $stmtInsertar = $conn->prepare($sqlInsertar);

            /*
             * bind_param necesita referencias para sus valores.
             */
            $valores = array_values($datosInsertar);
            $parametros = [$tipos];

            foreach ($valores as $indice => &$valor) {
                $parametros[] = &$valor;
            }

            unset($valor);

            call_user_func_array(
                [$stmtInsertar, 'bind_param'],
                $parametros
            );

            $stmtInsertar->execute();
            $stmtInsertar->close();

            /*
             * Renovar el token y redirigir al inicio de sesión.
             */
            $_SESSION['token_registro'] =
                bin2hex(random_bytes(32));

            header('Location: login.php?registro=exitoso');
            exit;

        } catch (Throwable $ex) {
            error_log(
                'registro.php - Error al registrar usuario: '
                . $ex->getMessage()
            );

            /*
             * No mostrar detalles internos de MySQL
             * al visitante.
             */
            if (
                $ex instanceof mysqli_sql_exception &&
                (int)$ex->getCode() === 1062
            ) {
                $errores[] =
                    'El correo o el celular ya se encuentran '
                    . 'registrados. Comprueba tus datos.';
            } else {
                $errores[] =
                    'No se pudo guardar la cuenta. '
                    . 'Comprueba la estructura de la tabla usuarios '
                    . 'y vuelve a intentarlo.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear cuenta | Emprende Cusco</title>

    <style>
        :root {
            --rojo: #8b0000;
            --rojo-oscuro: #650000;
            --dorado: #d4af37;
            --fondo: #f5f6f8;
            --texto: #252525;
            --borde: #d9dce2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--fondo);
            color: var(--texto);
        }

        header {
            background: var(--rojo);
            color: white;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header strong {
            font-size: 21px;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            color: var(--dorado);
        }

        .contenedor {
            width: min(720px, 94%);
            margin: 32px auto;
            background: white;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 5px 22px rgba(0, 0, 0, .08);
        }

        h1 {
            margin: 0 0 8px;
            text-align: center;
            color: var(--rojo);
        }

        .subtitulo {
            text-align: center;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .campo {
            margin-bottom: 16px;
            min-width: 0;
        }

        .completo {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            background: white;
            font-size: 14px;
        }

        input:focus,
        select:focus {
            outline: 2px solid rgba(139, 0, 0, .15);
            border-color: var(--rojo);
        }

        .boton {
            width: 100%;
            border: none;
            border-radius: 8px;
            padding: 13px;
            color: white;
            background: var(--rojo);
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        .boton:hover {
            background: var(--rojo-oscuro);
        }

        .boton:disabled {
            background: #999;
            cursor: not-allowed;
        }

        .errores {
            margin-bottom: 18px;
            padding: 14px 16px;
            background: #fff0f0;
            border: 1px solid #e6a5a5;
            color: #8b0000;
            border-radius: 8px;
            line-height: 1.5;
        }

        .errores ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .nota {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            line-height: 1.4;
        }

        .enlace-login {
            text-align: center;
            font-size: 14px;
            margin-top: 18px;
        }

        .enlace-login a {
            color: var(--rojo);
            font-weight: bold;
        }

        @media (max-width: 560px) {
            .contenedor {
                padding: 20px;
                margin: 20px auto;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .completo {
                grid-column: auto;
            }

            header {
                align-items: flex-start;
                flex-direction: column;
            }

            nav {
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>Emprende Cusco</strong>

    <nav>
        <a href="index.php">Volver al inicio</a>
        <a href="ideas.php">Ideas de negocio</a>
        <a href="bolsa.php">Empleos</a>
        <a href="login.php">Iniciar sesión</a>
    </nav>
</header>

<main class="contenedor">

    <h1>Crear cuenta</h1>

    <p class="subtitulo">
        Regístrate para acceder a las oportunidades de
        Emprende Cusco.
    </p>

    <?php if (!empty($errores)): ?>
        <div class="errores" role="alert">
            <strong>No se pudo completar el registro:</strong>

            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form
        method="POST"
        action="registro.php"
        id="formRegistro"
    >

        <input
            type="hidden"
            name="token_registro"
            value="<?= e($_SESSION['token_registro']) ?>"
        >

        <div class="grid">

            <div class="campo">
                <label for="nombre">Nombre completo *</label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="150"
                    autocomplete="name"
                    value="<?= e($nombre) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="email">Correo electrónico *</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    value="<?= e($email) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="celular">Celular peruano *</label>

                <input
                    type="tel"
                    id="celular"
                    name="celular"
                    inputmode="numeric"
                    pattern="9[0-9]{8}"
                    maxlength="9"
                    placeholder="987654321"
                    autocomplete="tel-national"
                    value="<?= e($celular) ?>"
                    required
                >

                <div class="nota">
                    Ingresa 9 dígitos, comenzando con 9.
                </div>
            </div>

            <div class="campo">
                <label for="edad">Edad</label>

                <input
                    type="number"
                    id="edad"
                    name="edad"
                    min="14"
                    max="100"
                    value="<?= e($edad) ?>"
                >
            </div>

            <div class="campo">
                <label for="password">Contraseña *</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <div class="nota">
                    Mínimo 8 caracteres.
                </div>
            </div>

            <div class="campo">
                <label for="confirmar_password">
                    Confirmar contraseña *
                </label>

                <input
                    type="password"
                    id="confirmar_password"
                    name="confirmar_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="campo completo">
                <label for="rol">Tipo de usuario *</label>

                <select id="rol" name="rol" required>
                    <option
                        value="estudiante"
                        <?= $rol === 'estudiante' ? 'selected' : '' ?>
                    >
                        Estudiante
                    </option>

                    <option
                        value="emprendedor"
                        <?= $rol === 'emprendedor' ? 'selected' : '' ?>
                    >
                        Emprendedor
                    </option>

                    <option
                        value="trabajador"
                        <?= $rol === 'trabajador' ? 'selected' : '' ?>
                    >
                        Trabajador
                    </option>
                </select>
            </div>

            <div class="campo completo">
                <label for="colegio_id">Colegio de Cusco *</label>

                <select
                    id="colegio_id"
                    name="colegio_id"
                    required
                >
                    <option value="">
                        Selecciona tu colegio
                    </option>

                    <?php foreach ($colegios as $colegio): ?>
                        <option
                            value="<?= (int)$colegio['id'] ?>"
                            <?= (string)$colegio_id ===
                                (string)$colegio['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($colegio['nombre']) ?>
                            <?= !empty($colegio['provincia'])
                                ? ' — ' . e($colegio['provincia'])
                                : '' ?>
                            <?= !empty($colegio['distrito'])
                                ? ' (' . e($colegio['distrito']) . ')'
                                : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (empty($colegios)): ?>
                    <div class="nota">
                        No se encontraron colegios de Cusco.
                        Comprueba la tabla colegios de tu base de datos.
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <button
            type="submit"
            class="boton"
            <?= empty($colegios) ? 'disabled' : '' ?>
        >
            Crear mi cuenta
        </button>

    </form>

    <div class="enlace-login">
        ¿Ya tienes una cuenta?
        <a href="login.php">Inicia sesión</a>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formulario = document.getElementById('formRegistro');
    const password = document.getElementById('password');
    const confirmar = document.getElementById('confirmar_password');
    const celular = document.getElementById('celular');

    if (celular) {
        celular.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    }

    if (formulario) {
        formulario.addEventListener('submit', function (event) {
            if (
                password.value !== confirmar.value
            ) {
                event.preventDefault();

                alert(
                    'Las contraseñas no coinciden. ' +
                    'Escríbelas nuevamente.'
                );

                confirmar.focus();
                return;
            }

            if (!/^9[0-9]{8}$/.test(celular.value)) {
                event.preventDefault();

                alert(
                    'Ingresa un celular peruano válido de 9 dígitos.'
                );

                celular.focus();
            }
        });
    }
});
</script>

</body>
</html>
