<?php
// ============================================================
// EMPRENDE CUSCO - PUBLICAR EMPLEO
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

// ------------------------------------------------------------
// CONEXIÓN COMPATIBLE
// ------------------------------------------------------------
$db = null;

if (isset($conexion) && $conexion instanceof mysqli) {
    $db = $conexion;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
}

if (!$db) {
    die("Error: No se pudo establecer la conexión con la base de datos.");
}

// ------------------------------------------------------------
// VERIFICAR SESIÓN
// ------------------------------------------------------------
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?mensaje=debes_iniciar_sesion");
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];

$mensaje = "";
$error = "";

// ------------------------------------------------------------
// TOKEN CSRF
// ------------------------------------------------------------
if (empty($_SESSION['csrf_empleo'])) {
    $_SESSION['csrf_empleo'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_empleo'];

// ------------------------------------------------------------
// DATOS DEL USUARIO
// ------------------------------------------------------------
$nombre_usuario = "Usuario";

$stmtUsuario = $db->prepare("
    SELECT nombres, apellidos, email
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

if ($stmtUsuario) {
    $stmtUsuario->bind_param("i", $usuario_id);
    $stmtUsuario->execute();

    $resultadoUsuario = $stmtUsuario->get_result();

    if ($resultadoUsuario && $resultadoUsuario->num_rows > 0) {
        $usuario = $resultadoUsuario->fetch_assoc();

        $nombre_usuario = trim(
            ($usuario['nombres'] ?? '') . ' ' .
            ($usuario['apellidos'] ?? '')
        );
    }

    $stmtUsuario->close();
}

// ------------------------------------------------------------
// DEPARTAMENTOS DEL PERÚ
// ------------------------------------------------------------
$departamentos = [
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

// ------------------------------------------------------------
// CATEGORÍAS
// ------------------------------------------------------------
$categorias = [
    "Administración",
    "Agricultura",
    "Atención al cliente",
    "Comercio",
    "Construcción",
    "Contabilidad",
    "Educación",
    "Hotelería y turismo",
    "Ingeniería",
    "Informática y tecnología",
    "Marketing",
    "Salud",
    "Seguridad",
    "Transporte",
    "Ventas",
    "Otros"
];

// ------------------------------------------------------------
// VARIABLES DEL FORMULARIO
// ------------------------------------------------------------
$titulo = "";
$empresa = "";
$categoria = "";
$descripcion = "";
$requisitos = "";
$funciones = "";
$salario = "";
$tipo_contrato = "";
$modalidad = "";
$ubicacion = "";
$departamento = "";
$contacto = "";
$email_contacto = "";
$telefono = "";

// ------------------------------------------------------------
// PROCESAR FORMULARIO
// ------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar CSRF
    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf_empleo'], $_POST['csrf'])
    ) {
        $error = "La sesión del formulario ha expirado. Recarga la página e inténtalo nuevamente.";
    } else {

        $titulo = trim($_POST['titulo'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $requisitos = trim($_POST['requisitos'] ?? '');
        $funciones = trim($_POST['funciones'] ?? '');
        $salario = trim($_POST['salario'] ?? '');
        $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
        $modalidad = trim($_POST['modalidad'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $email_contacto = trim($_POST['email_contacto'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        // ----------------------------------------------------
        // VALIDACIONES
        // ----------------------------------------------------

        if ($titulo === '') {
            $error = "Ingresa el título del puesto.";
        } elseif ($empresa === '') {
            $error = "Ingresa el nombre de la empresa o institución.";
        } elseif ($categoria === '') {
            $error = "Selecciona una categoría.";
        } elseif ($descripcion === '') {
            $error = "Ingresa la descripción del empleo.";
        } elseif ($requisitos === '') {
            $error = "Ingresa los requisitos.";
        } elseif ($funciones === '') {
            $error = "Ingresa las funciones del puesto.";
        } elseif ($departamento === '') {
            $error = "Selecciona el departamento.";
        } elseif ($contacto === '') {
            $error = "Ingresa la información de contacto.";
        } elseif ($email_contacto !== '' && !filter_var($email_contacto, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo de contacto no es válido.";
        }

        // ----------------------------------------------------
        // INSERTAR
        // ----------------------------------------------------
        if ($error === "") {

            /*
             IMPORTANTE:
             Esta consulta utiliza una estructura amplia para la tabla
             empleos.

             Si tu tabla todavía no tiene estas columnas, abajo te
             dejo el SQL para crearla.
            */

            $sql = "
                INSERT INTO empleos (
                    usuario_id,
                    titulo,
                    empresa,
                    categoria,
                    descripcion,
                    requisitos,
                    funciones,
                    salario,
                    tipo_contrato,
                    modalidad,
                    ubicacion,
                    departamento,
                    contacto,
                    email_contacto,
                    telefono,
                    estado,
                    fecha_publicacion
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW()
                )
            ";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                $error = "No se pudo preparar el registro: " . $db->error;
            } else {

                $stmt->bind_param(
                    "issssssssssssss",
                    $usuario_id,
                    $titulo,
                    $empresa,
                    $categoria,
                    $descripcion,
                    $requisitos,
                    $funciones,
                    $salario,
                    $tipo_contrato,
                    $modalidad,
                    $ubicacion,
                    $departamento,
                    $contacto,
                    $email_contacto,
                    $telefono
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    // Regenerar CSRF
                    $_SESSION['csrf_empleo'] = bin2hex(random_bytes(32));

                    header("Location: publicar-empleo.php?publicado=1");
                    exit;

                } else {

                    $error = "No se pudo publicar el empleo: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// ------------------------------------------------------------
// MENSAJE DE ÉXITO
// ------------------------------------------------------------
if (isset($_GET['publicado']) && $_GET['publicado'] == '1') {
    $mensaje = "¡Empleo registrado correctamente! Será revisado antes de aparecer públicamente.";
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Publicar Empleo | Emprende Cusco</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f6f8;
            color: #333;
        }

        .contenedor {
            width: 94%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .cabecera {
            background: linear-gradient(
                135deg,
                #8B0000,
                #5C0000
            );

            color: white;
            padding: 30px;

            border-radius: 18px 18px 0 0;

            box-shadow: 0 5px 18px rgba(0,0,0,.12);
        }

        .cabecera h1 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .cabecera p {
            opacity: .92;
            line-height: 1.6;
        }

        .usuario {
            margin-top: 15px;
            background: rgba(255,255,255,.12);
            padding: 10px 14px;
            border-radius: 10px;
            display: inline-block;
        }

        .formulario {
            background: white;
            padding: 30px;

            border-radius: 0 0 18px 18px;

            box-shadow: 0 5px 18px rgba(0,0,0,.08);
        }

        .seccion {
            margin-bottom: 30px;
        }

        .seccion-titulo {
            color: #8B0000;
            font-size: 21px;
            font-weight: bold;

            border-bottom: 2px solid #eee;

            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .campo {
            display: flex;
            flex-direction: column;
        }

        .campo-completo {
            grid-column: 1 / -1;
        }

        label {
            font-weight: bold;
            margin-bottom: 8px;
            color: #444;
        }

        label span {
            color: #8B0000;
        }

        input,
        select,
        textarea {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #ccc;
            border-radius: 9px;

            font-size: 15px;

            outline: none;

            transition: .2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139,0,0,.08);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .ayuda {
            margin-top: 6px;
            color: #777;
            font-size: 13px;
        }

        .alerta {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .exito {
            background: #e8f7ee;
            color: #176b3a;
            border: 1px solid #b7e5c9;
        }

        .error {
            background: #fff0f0;
            color: #a00000;
            border: 1px solid #efb6b6;
        }

        .botones {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;

            margin-top: 20px;
        }

        .btn {
            display: inline-block;

            padding: 14px 24px;

            border-radius: 10px;

            text-decoration: none;

            font-weight: bold;

            cursor: pointer;

            border: none;

            font-size: 15px;
        }

        .btn-volver {
            background: #eee;
            color: #444;
        }

        .btn-volver:hover {
            background: #ddd;
        }

        .btn-publicar {
            background: linear-gradient(
                135deg,
                #A30826,
                #8B0000
            );

            color: white;

            min-width: 190px;
        }

        .btn-publicar:hover {
            opacity: .9;
        }

        .info {
            margin-top: 25px;

            background: #fff8e5;

            border-left: 5px solid #D4AF37;

            padding: 18px;

            border-radius: 8px;

            line-height: 1.6;
        }

        .contador {
            text-align: right;
            color: #777;
            font-size: 12px;
            margin-top: 5px;
        }

        @media(max-width: 700px) {

            .grid {
                grid-template-columns: 1fr;
            }

            .campo-completo {
                grid-column: auto;
            }

            .contenedor {
                width: 96%;
                margin: 15px auto;
            }

            .cabecera,
            .formulario {
                padding: 20px;
            }

            .cabecera h1 {
                font-size: 24px;
            }

            .botones {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="contenedor">

    <div class="cabecera">

        <h1>💼 Publicar una Oferta de Empleo</h1>

        <p>
            Publica oportunidades laborales para personas de Cusco
            y de todo el Perú a través de Emprende Cusco.
        </p>

        <div class="usuario">
            👤 Publicando como:
            <strong>
                <?= htmlspecialchars($nombre_usuario) ?>
            </strong>
        </div>

    </div>

    <form
        method="POST"
        action="publicar-empleo.php"
        class="formulario"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= htmlspecialchars($csrf) ?>"
        >

        <?php if ($mensaje !== ""): ?>

            <div class="alerta exito">
                ✅
                <?= htmlspecialchars($mensaje) ?>
            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alerta error">
                ❌
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- =================================================
             INFORMACIÓN PRINCIPAL
        ================================================== -->

        <div class="seccion">

            <div class="seccion-titulo">
                📋 Información principal
            </div>

            <div class="grid">

                <div class="campo campo-completo">

                    <label>
                        Título del puesto <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="titulo"
                        maxlength="150"
                        placeholder="Ejemplo: Asistente de Administración"
                        value="<?= htmlspecialchars($titulo) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Empresa o institución <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="empresa"
                        maxlength="150"
                        placeholder="Ejemplo: Empresa Cusco S.A.C."
                        value="<?= htmlspecialchars($empresa) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Categoría <span>*</span>
                    </label>

                    <select
                        name="categoria"
                        required
                    >

                        <option value="">
                            Selecciona una categoría
                        </option>

                        <?php foreach ($categorias as $cat): ?>

                            <option
                                value="<?= htmlspecialchars($cat) ?>"
                                <?= $categoria === $cat ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($cat) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

        </div>


        <!-- =================================================
             DESCRIPCIÓN
        ================================================== -->

        <div class="seccion">

            <div class="seccion-titulo">
                📝 Descripción del puesto
            </div>

            <div class="grid">

                <div class="campo campo-completo">

                    <label>
                        Descripción del empleo <span>*</span>
                    </label>

                    <textarea
                        name="descripcion"
                        maxlength="3000"
                        placeholder="Describe detalladamente la oferta laboral..."
                        required
                    ><?= htmlspecialchars($descripcion) ?></textarea>

                    <div class="contador">
                        Máximo 3000 caracteres
                    </div>

                </div>


                <div class="campo">

                    <label>
                        Requisitos <span>*</span>
                    </label>

                    <textarea
                        name="requisitos"
                        maxlength="3000"
                        placeholder="Ejemplo:
- Experiencia mínima de 1 año
- Estudios técnicos o universitarios
- Manejo de Office"
                        required
                    ><?= htmlspecialchars($requisitos) ?></textarea>

                </div>


                <div class="campo">

                    <label>
                        Funciones <span>*</span>
                    </label>

                    <textarea
                        name="funciones"
                        maxlength="3000"
                        placeholder="Describe las principales funciones del puesto..."
                        required
                    ><?= htmlspecialchars($funciones) ?></textarea>

                </div>

            </div>

        </div>


        <!-- =================================================
             CONDICIONES
        ================================================== -->

        <div class="seccion">

            <div class="seccion-titulo">
                💰 Condiciones laborales
            </div>

            <div class="grid">

                <div class="campo">

                    <label>
                        Salario
                    </label>

                    <input
                        type="text"
                        name="salario"
                        maxlength="100"
                        placeholder="Ejemplo: S/ 1,500 - S/ 2,000"
                        value="<?= htmlspecialchars($salario) ?>"
                    >

                </div>


                <div class="campo">

                    <label>
                        Tipo de contrato
                    </label>

                    <select name="tipo_contrato">

                        <option value="">
                            Seleccionar
                        </option>

                        <option
                            value="Tiempo completo"
                            <?= $tipo_contrato === "Tiempo completo" ? 'selected' : '' ?>
                        >
                            Tiempo completo
                        </option>

                        <option
                            value="Medio tiempo"
                            <?= $tipo_contrato === "Medio tiempo" ? 'selected' : '' ?>
                        >
                            Medio tiempo
                        </option>

                        <option
                            value="Prácticas"
                            <?= $tipo_contrato === "Prácticas" ? 'selected' : '' ?>
                        >
                            Prácticas
                        </option>

                        <option
                            value="Temporal"
                            <?= $tipo_contrato === "Temporal" ? 'selected' : '' ?>
                        >
                            Temporal
                        </option>

                        <option
                            value="Por proyecto"
                            <?= $tipo_contrato === "Por proyecto" ? 'selected' : '' ?>
                        >
                            Por proyecto
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Modalidad
                    </label>

                    <select name="modalidad">

                        <option value="">
                            Seleccionar
                        </option>

                        <option
                            value="Presencial"
                            <?= $modalidad === "Presencial" ? 'selected' : '' ?>
                        >
                            Presencial
                        </option>

                        <option
                            value="Remoto"
                            <?= $modalidad === "Remoto" ? 'selected' : '' ?>
                        >
                            Remoto
                        </option>

                        <option
                            value="Híbrido"
                            <?= $modalidad === "Híbrido" ? 'selected' : '' ?>
                        >
                            Híbrido
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Ubicación
                    </label>

                    <input
                        type="text"
                        name="ubicacion"
                        maxlength="150"
                        placeholder="Ejemplo: Cusco, Wanchaq"
                        value="<?= htmlspecialchars($ubicacion) ?>"
                    >

                </div>

            </div>

        </div>


        <!-- =================================================
             UBICACIÓN
        ================================================== -->

        <div class="seccion">

            <div class="seccion-titulo">
                📍 Ubicación de la oferta
            </div>

            <div class="grid">

                <div class="campo">

                    <label>
                        Departamento <span>*</span>
                    </label>

                    <select
                        name="departamento"
                        required
                    >

                        <option value="">
                            Selecciona un departamento
                        </option>

                        <?php foreach ($departamentos as $dep): ?>

                            <option
                                value="<?= htmlspecialchars($dep) ?>"
                                <?= $departamento === $dep ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($dep) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

        </div>


        <!-- =================================================
             CONTACTO
        ================================================== -->

        <div class="seccion">

            <div class="seccion-titulo">
                📞 Información de contacto
            </div>

            <div class="grid">

                <div class="campo">

                    <label>
                        Persona o medio de contacto <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="contacto"
                        maxlength="150"
                        placeholder="Ejemplo: Área de Recursos Humanos"
                        value="<?= htmlspecialchars($contacto) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        name="email_contacto"
                        maxlength="150"
                        placeholder="empresa@correo.com"
                        value="<?= htmlspecialchars($email_contacto) ?>"
                    >

                </div>


                <div class="campo">

                    <label>
                        Teléfono / WhatsApp
                    </label>

                    <input
                        type="text"
                        name="telefono"
                        maxlength="30"
                        placeholder="Ejemplo: 987654321"
                        value="<?= htmlspecialchars($telefono) ?>"
                    >

                </div>

            </div>

        </div>


        <!-- =================================================
             INFORMACIÓN
        ================================================== -->

        <div class="info">

            <strong>ℹ️ Importante:</strong>

            <br>

            La oferta será registrada inicialmente como
            <strong>pendiente</strong>.

            <br>

            Después podrá ser revisada antes de mostrarse
            públicamente en Emprende Cusco.

            <br><br>

            <strong>🚫 No publiques información falsa,
            engañosa o que solicite pagos a los postulantes.</strong>

        </div>


        <!-- =================================================
             BOTONES
        ================================================== -->

        <div class="botones">

            <a
                href="index.php"
                class="btn btn-volver"
            >
                ← Volver al inicio
            </a>

            <button
                type="submit"
                class="btn btn-publicar"
            >
                🚀 Publicar empleo
            </button>

        </div>

    </form>

</div>


<script>

document.addEventListener("DOMContentLoaded", function() {

    const formulario = document.querySelector(".formulario");

    if (!formulario) {
        return;
    }

    formulario.addEventListener("submit", function() {

        const boton = formulario.querySelector(
            'button[type="submit"]'
        );

        if (boton) {

            boton.disabled = true;

            boton.innerHTML = "⏳ Publicando...";

        }

    });

});

</script>

</body>

</html>