<?php
/* =========================================================
   PUBLICAR IDEA - EMPRENDE CUSCO
   Guarda ideas de negocio en MySQL
   VERSIÓN CORREGIDA
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');

require_once 'config/conexion.php';

/* =========================================================
   VERIFICAR CONEXIÓN
========================================================= */

/*
 * Compatible con conexiones llamadas:
 * $conexion
 * o
 * $conn
 */
if (isset($conexion) && $conexion instanceof mysqli) {
    $db = $conexion;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} else {
    die("
        <div style='
            max-width:700px;
            margin:50px auto;
            padding:25px;
            background:#fff3f3;
            border:1px solid #e5a0a0;
            border-radius:12px;
            font-family:Arial,sans-serif;
            color:#8B0000;
        '>
            <h2>❌ Error de conexión</h2>
            <p>
                No se encontró una conexión válida con la base de datos.
            </p>
            <p>
                Revisa el archivo <strong>config/conexion.php</strong>.
            </p>
        </div>
    ");
}

/* =========================================================
   VERIFICAR SESIÓN
========================================================= */

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {

    header("Location: login.php?redirect=publicar-idea.php");
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';

/* =========================================================
   TOKEN CSRF
========================================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================================
   VARIABLES
========================================================= */

$mensaje = "";
$error = "";

$titulo = "";
$categoria = "";
$descripcion = "";
$problema = "";
$solucion = "";
$clientes = "";
$inversion = "";
$ubicacion = "";
$venta = "";
$contacto = "";

/* =========================================================
   CATEGORÍAS
========================================================= */

$categorias = [
    "Agroindustria",
    "Agricultura",
    "Artesanía",
    "Comercio",
    "Gastronomía",
    "Turismo",
    "Tecnología",
    "Educación",
    "Servicios",
    "Moda y textil",
    "Medio ambiente",
    "Salud y bienestar",
    "Ganadería",
    "Alimentos y bebidas",
    "Innovación",
    "Otro"
];

/* =========================================================
   DEPARTAMENTOS DEL PERÚ
========================================================= */

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

/* =========================================================
   PROCESAR FORMULARIO
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       VALIDAR CSRF
    ===================================================== */

    $csrf_recibido = $_POST['csrf_token'] ?? '';

    if (
        empty($csrf_recibido) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrf_recibido)
    ) {

        $error = "La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.";

    } else {

        /* =================================================
           RECIBIR DATOS
        ================================================= */

        $titulo = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $problema = trim($_POST['problema'] ?? '');
        $solucion = trim($_POST['solucion'] ?? '');
        $clientes = trim($_POST['clientes'] ?? '');
        $inversion = trim($_POST['inversion'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $venta = trim($_POST['venta'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');

        /* =================================================
           LIMPIAR CONTACTO
        ================================================= */

        if ($contacto !== '') {
            $contacto = preg_replace('/[^0-9]/', '', $contacto);
        }

        /* =================================================
           VALIDACIONES
        ================================================= */

        if ($titulo === '') {

            $error = "Debes ingresar el título de tu idea de negocio.";

        } elseif (mb_strlen($titulo) < 5) {

            $error = "El título debe tener al menos 5 caracteres.";

        } elseif (mb_strlen($titulo) > 150) {

            $error = "El título no puede superar los 150 caracteres.";

        } elseif (
            $categoria === '' ||
            !in_array($categoria, $categorias, true)
        ) {

            $error = "Selecciona una categoría válida.";

        } elseif (
            $ubicacion !== '' &&
            !in_array($ubicacion, $departamentos, true)
        ) {

            $error = "Selecciona un departamento válido.";

        } elseif ($descripcion === '') {

            $error = "Debes describir tu idea de negocio.";

        } elseif (mb_strlen($descripcion) < 20) {

            $error = "La descripción debe tener al menos 20 caracteres.";

        } elseif ($problema === '') {

            $error = "Indica qué problema o necesidad deseas solucionar.";

        } elseif ($solucion === '') {

            $error = "Explica cuál es la solución que propones.";

        } elseif ($clientes === '') {

            $error = "Indica quiénes serán tus clientes o público objetivo.";

        }

        /* =================================================
           VALIDAR CONTACTO
        ================================================= */

        if ($error === '' && $contacto !== '') {

            if (!preg_match('/^9[0-9]{8}$/', $contacto)) {

                $error =
                    "El celular/WhatsApp debe tener 9 dígitos y comenzar con 9.";
            }
        }

        /* =================================================
           GUARDAR EN BASE DE DATOS
        ================================================= */

        if ($error === '') {

            $sql = "
                INSERT INTO ideas_negocio
                (
                    usuario_id,
                    titulo,
                    categoria,
                    descripcion,
                    problema,
                    solucion,
                    clientes,
                    inversion,
                    ubicacion,
                    venta,
                    contacto,
                    estado,
                    fecha_publicacion
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pendiente',
                    NOW()
                )
            ";

            $stmt = $db->prepare($sql);

            if (!$stmt) {

                error_log(
                    "Error prepare publicar idea: " . $db->error
                );

                $error =
                    "No se pudo preparar el registro de la idea. Revisa la estructura de la tabla ideas_negocio.";

            } else {

                /*
                 * 1 entero + 10 textos
                 */

                $stmt->bind_param(
                    "issssssssss",
                    $usuario_id,
                    $titulo,
                    $categoria,
                    $descripcion,
                    $problema,
                    $solucion,
                    $clientes,
                    $inversion,
                    $ubicacion,
                    $venta,
                    $contacto
                );

                if ($stmt->execute()) {

                    /*
                     * Guardar datos mínimos para el mensaje
                     */
                    $_SESSION['idea_publicada'] = true;
                    $_SESSION['idea_titulo'] = $titulo;

                    /*
                     * Generar nuevo CSRF para futuros formularios
                     */
                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                    /*
                     * Redirección PRG
                     */
                    header(
                        "Location: publicar-idea.php?success=1"
                    );

                    exit;

                } else {

                    error_log(
                        "Error execute publicar idea: " .
                        $stmt->error
                    );

                    $error =
                        "No se pudo registrar la idea. Error de base de datos: " .
                        $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

/* =========================================================
   MENSAJE DE ÉXITO
========================================================= */

if (
    isset($_GET['success']) &&
    $_GET['success'] === '1'
) {

    $idea_titulo =
        $_SESSION['idea_titulo'] ?? 'Tu idea de negocio';

    $mensaje = "
        <div class='alert success'>
            <div class='alert-icon'>
                <i class='fa-solid fa-circle-check'></i>
            </div>

            <div>
                <strong>¡Idea registrada correctamente!</strong>

                <p>
                    Tu idea
                    <strong>" .
                    htmlspecialchars(
                        $idea_titulo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                    "</strong>
                    fue enviada correctamente.
                </p>

                <p>
                    Tu propuesta quedó
                    <strong>pendiente de revisión</strong>.
                    Una vez aprobada por el administrador,
                    podrá aparecer públicamente en Emprende Cusco.
                </p>
            </div>
        </div>
    ";

    unset($_SESSION['idea_publicada']);
    unset($_SESSION['idea_titulo']);
}

/* =========================================================
   HEADER
========================================================= */

include_once 'includes/header.php';

?>

<style>

/* =========================================================
   CONTENEDOR PRINCIPAL
========================================================= */

.idea-page {
    max-width: 850px;
    margin: 40px auto;
    padding: 0 15px;
}

/* =========================================================
   TARJETA
========================================================= */

.idea-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 35px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

/* =========================================================
   TÍTULO
========================================================= */

.idea-title {
    text-align: center;
    color: #8B0000;
    font-size: 30px;
    font-weight: 800;
    margin: 0 0 8px;
}

.idea-subtitle {
    text-align: center;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 30px;
}

/* =========================================================
   FORMULARIO
========================================================= */

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 700;
    color: #374151;
    margin-bottom: 7px;
}

.required {
    color: #8B0000;
}

.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    font-size: 15px;
    box-sizing: border-box;
    background: #fff;
    transition: 0.2s;
    font-family: inherit;
}

.form-control:focus {
    border-color: #8B0000;
    outline: none;
    box-shadow:
        0 0 0 3px rgba(139,0,0,0.10);
}

textarea.form-control {
    resize: vertical;
    min-height: 110px;
}

/* =========================================================
   GRID
========================================================= */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

/* =========================================================
   AYUDA
========================================================= */

.help-text {
    display: block;
    color: #6b7280;
    font-size: 13px;
    margin-top: 5px;
}

/* =========================================================
   ALERTAS
========================================================= */

.alert {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 25px;
    line-height: 1.5;
}

.alert p {
    margin: 5px 0 0;
    font-weight: 400;
}

.alert.success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.alert.error {
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f1aeb5;
}

.alert-icon {
    font-size: 24px;
}

/* =========================================================
   INFORMACIÓN
========================================================= */

.idea-info {
    background: #fff8e1;
    border-left: 4px solid #D4AF37;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 25px;
    color: #5f4b00;
    line-height: 1.5;
}

/* =========================================================
   SECCIONES
========================================================= */

.section-title {
    color: #8B0000;
    font-size: 19px;
    font-weight: 800;
    margin: 30px 0 18px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f0f0f0;
}

/* =========================================================
   BOTONES
========================================================= */

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn-back {
    text-decoration: none;
    color: #6b7280;
    font-weight: 700;
}

.btn-back:hover {
    color: #8B0000;
}

.btn-submit {
    background: #8B0000;
    color: #fff;
    border: none;
    padding: 13px 28px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: 0.2s;
}

.btn-submit:hover {
    background: #5C0000;
    transform: translateY(-1px);
}

.btn-submit:disabled {
    background: #777;
    cursor: not-allowed;
    transform: none;
}

/* =========================================================
   ESTADO DE CARGA
========================================================= */

.loading-message {
    display: none;
    margin-top: 12px;
    text-align: center;
    color: #8B0000;
    font-weight: 700;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 700px) {

    .idea-page {
        margin: 20px auto;
    }

    .idea-card {
        padding: 22px 17px;
    }

    .idea-title {
        font-size: 24px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .btn-submit {
        width: 100%;
    }

    .btn-back {
        text-align: center;
    }

}

</style>

<div class="idea-page">

    <div class="idea-card">

        <!-- =================================================
             TÍTULO
        ================================================== -->

        <h1 class="idea-title">

            <i class="fa-solid fa-lightbulb"></i>

            Publicar Idea de Negocio

        </h1>

        <p class="idea-subtitle">

            Comparte tu emprendimiento, proyecto o propuesta
            de innovación y forma parte de la comunidad
            de Emprende Cusco.

        </p>


        <!-- =================================================
             MENSAJE ÉXITO
        ================================================== -->

        <?php if ($mensaje !== ''): ?>

            <?= $mensaje ?>

        <?php endif; ?>


        <!-- =================================================
             MENSAJE ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div class="alert error">

                <div class="alert-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>

                <div>

                    <strong>
                        No se pudo publicar la idea
                    </strong>

                    <p>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             INFORMACIÓN
        ================================================== -->

        <div class="idea-info">

            <strong>

                <i class="fa-solid fa-circle-info"></i>

                Importante:

            </strong>

            Tu idea será revisada antes de hacerse pública.
            Esto ayuda a mantener información confiable y de
            calidad dentro de Emprende Cusco.

        </div>


        <!-- =================================================
             FORMULARIO
        ================================================== -->

        <form
            method="POST"
            action="publicar-idea.php"
            id="formPublicarIdea"
        >

            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <!-- =================================================
                 INFORMACIÓN PRINCIPAL
            ================================================== -->

            <div class="section-title">

                <i class="fa-solid fa-file-lines"></i>

                Información de la idea

            </div>


            <!-- TÍTULO -->

            <div class="form-group">

                <label for="titulo">

                    Título del proyecto o idea

                    <span class="required">*</span>

                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    class="form-control"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars(
                        $titulo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Ej. Producción y comercialización de snacks de kiwicha"
                >

            </div>


            <!-- CATEGORÍA + UBICACIÓN -->

            <div class="form-grid">

                <div class="form-group">

                    <label for="categoria">

                        Categoría

                        <span class="required">*</span>

                    </label>

                    <select
                        id="categoria"
                        name="categoria"
                        class="form-control"
                        required
                    >

                        <option value="">

                            Seleccionar categoría

                        </option>

                        <?php foreach ($categorias as $cat): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $cat,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= ($categoria === $cat)
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $cat,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="ubicacion">

                        Ubicación

                    </label>

                    <select
                        id="ubicacion"
                        name="ubicacion"
                        class="form-control"
                    >

                        <option value="">

                            Seleccionar departamento

                        </option>

                        <?php foreach ($departamentos as $dep): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $dep,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= ($ubicacion === $dep)
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $dep,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 DESCRIPCIÓN
            ================================================== -->

            <div class="form-group">

                <label for="descripcion">

                    Descripción detallada

                    <span class="required">*</span>

                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    rows="6"
                    maxlength="5000"
                    required
                    placeholder="Describe tu idea, producto o servicio. Explica en qué consiste y qué la hace diferente."
                ><?= htmlspecialchars(
                    $descripcion,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

                <span class="help-text">

                    Explica claramente tu propuesta para que otras
                    personas puedan entenderla.

                </span>

            </div>


            <!-- =================================================
                 PROBLEMA Y SOLUCIÓN
            ================================================== -->

            <div class="section-title">

                <i class="fa-solid fa-chart-line"></i>

                Problema y solución

            </div>


            <!-- PROBLEMA -->

            <div class="form-group">

                <label for="problema">

                    ¿Qué problema o necesidad resuelve?

                    <span class="required">*</span>

                </label>

                <textarea
                    id="problema"
                    name="problema"
                    class="form-control"
                    rows="4"
                    maxlength="3000"
                    required
                    placeholder="Ej. Los productores de la zona tienen dificultades para comercializar sus productos..."
                ><?= htmlspecialchars(
                    $problema,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <!-- SOLUCIÓN -->

            <div class="form-group">

                <label for="solucion">

                    ¿Cuál es tu solución?

                    <span class="required">*</span>

                </label>

                <textarea
                    id="solucion"
                    name="solucion"
                    class="form-control"
                    rows="4"
                    maxlength="3000"
                    required
                    placeholder="Explica cómo tu producto o servicio solucionará el problema."
                ><?= htmlspecialchars(
                    $solucion,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <!-- CLIENTES -->

            <div class="form-group">

                <label for="clientes">

                    ¿Quiénes serán tus clientes?

                    <span class="required">*</span>

                </label>

                <textarea
                    id="clientes"
                    name="clientes"
                    class="form-control"
                    rows="3"
                    maxlength="2000"
                    required
                    placeholder="Ej. Turistas nacionales y extranjeros, familias, estudiantes, empresas, etc."
                ><?= htmlspecialchars(
                    $clientes,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <!-- =================================================
                 INFORMACIÓN COMERCIAL
            ================================================== -->

            <div class="section-title">

                <i class="fa-solid fa-coins"></i>

                Información comercial

            </div>


            <div class="form-grid">


                <!-- INVERSIÓN -->

                <div class="form-group">

                    <label for="inversion">

                        Inversión estimada

                    </label>

                    <input
                        type="text"
                        id="inversion"
                        name="inversion"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $inversion,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Ej. S/ 5,000"
                    >

                </div>


                <!-- VENTA -->

                <div class="form-group">

                    <label for="venta">

                        ¿Cómo venderás?

                    </label>

                    <input
                        type="text"
                        id="venta"
                        name="venta"
                        class="form-control"
                        maxlength="200"
                        value="<?= htmlspecialchars(
                            $venta,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Ej. Tienda, redes sociales, delivery"
                    >

                </div>

            </div>


            <!-- =================================================
                 CONTACTO
            ================================================== -->

            <div class="section-title">

                <i class="fa-solid fa-address-book"></i>

                Contacto

            </div>


            <div class="form-group">

                <label for="contacto">

                    Celular / WhatsApp

                </label>

                <input
                    type="tel"
                    id="contacto"
                    name="contacto"
                    class="form-control"
                    maxlength="9"
                    inputmode="numeric"
                    pattern="9[0-9]{8}"
                    value="<?= htmlspecialchars(
                        $contacto,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Ej. 987654321"
                >

                <span class="help-text">

                    Opcional. Debe contener 9 dígitos y comenzar con 9.

                </span>

            </div>


            <!-- =================================================
                 BOTONES
            ================================================== -->

            <div class="form-actions">

                <a
                    href="ideas.php"
                    class="btn-back"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Volver a Ideas

                </a>


                <button
                    type="submit"
                    class="btn-submit"
                    id="btnPublicar"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Publicar Idea

                </button>

            </div>


            <div
                class="loading-message"
                id="loadingMessage"
            >

                ⏳ Registrando tu idea, espera un momento...

            </div>

        </form>

    </div>

</div>


<script>

/* =========================================================
   PUBLICAR IDEA
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const formulario =
        document.getElementById("formPublicarIdea");

    const boton =
        document.getElementById("btnPublicar");

    const mensaje =
        document.getElementById("loadingMessage");

    const contacto =
        document.getElementById("contacto");


    /* =====================================================
       VALIDAR FORMULARIO
    ===================================================== */

    if (formulario) {

        formulario.addEventListener("submit", function (event) {

            /*
             * Permitir que HTML5 haga las validaciones.
             */

            if (!formulario.checkValidity()) {

                return;

            }


            /*
             * Evitar doble clic.
             */

            if (boton.disabled) {

                event.preventDefault();

                return;

            }


            boton.disabled = true;

            boton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> ' +
                'Publicando...';

            if (mensaje) {

                mensaje.style.display = "block";

            }

        });

    }


    /* =====================================================
       SOLO NÚMEROS EN CONTACTO
    ===================================================== */

    if (contacto) {

        contacto.addEventListener("input", function () {

            this.value =
                this.value
                .replace(/[^0-9]/g, '')
                .substring(0, 9);

        });

    }

});

</script>


<?php

/* =========================================================
   FOOTER
========================================================= */

if (file_exists('includes/footer.php')) {

    include_once 'includes/footer.php';

}

?>