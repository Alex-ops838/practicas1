<?php
/**
 * ============================================================
 * EMPRENDE CUSCO
 * Portal de empleo, emprendimiento y capacitación
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

/* ============================================================
   CONFIGURACIÓN DEL IDIOMA
   ============================================================ */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'es');

if (!in_array($lang, ['es', 'qu'], true)) {
    $lang = 'es';
}

$_SESSION['lang'] = $lang;

/* ============================================================
   TEXTOS
   ============================================================ */

$txt_defaults = [

    'es' => [
        'hero_title'      => 'Impulsa tu negocio y encuentra trabajo en Cusco ',
        'hero_sub'        => 'Accede a oportunidades laborales, capacitación y herramientas prácticas para desarrollar tus habilidades y crear nuevas oportunidades.',
        'crear_cuenta'    => 'Crear cuenta',
        'ingresar'       => 'Ingresar',
        'seccion_video'   => 'Tutoriales y capacitación',
        'ideas'           => 'Ideas de negocio en Cusco',
        'empleos'         => 'Empleos disponibles en Cusco'
    ],

    'qu' => [
        'hero_title'      => 'Tariy llank’ana, qallariy mosoq negocio Qosqo suyupi',
        'hero_sub'        => 'Llank’anata tariypaq, yachaykunata hinaspa mosoq negociokuna qallariypaq kaypi yanapayta tariy.',
        'crear_cuenta'    => 'Qillqakuy',
        'ingresar'        => 'Yaykuy',
        'seccion_video'   => 'Runasimipi yachay',
        'ideas'           => 'Mosoq yuyaykuna negociopaq',
        'empleos'         => 'Llank’anakuna Qosqo suyupi'
    ]
];

$txt = $txt_defaults[$lang];

/* ============================================================
   SEGURIDAD PARA SALIDA HTML
   ============================================================ */

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/* ============================================================
   IMÁGENES LOCALES
   ============================================================ */

$flayer_imagen = 'q.jpg';

$hero_images = [
    'imagenes/logo.jpg',
    'imagenes/artesania.jpg'
];

/* ============================================================
   CARGAR IDEAS GUARDADAS DESDE ideas.php
   ============================================================ */

$ideasPublicadas = [];

try {
    $archivoConexion = __DIR__ . '/config/conexion.php';

    if (file_exists($archivoConexion)) {
        require_once $archivoConexion;
    }

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->set_charset('utf8mb4');

        $resultadoIdeas = $conn->query(
            "SELECT id, nombre, idea_seleccionada, datos_json, creado_en
             FROM planes_emprendimiento
             ORDER BY creado_en DESC, id DESC
             LIMIT 6"
        );

        if ($resultadoIdeas) {
            while ($filaIdea = $resultadoIdeas->fetch_assoc()) {
                $datosIdea = json_decode((string)($filaIdea['datos_json'] ?? ''), true);
                if (!is_array($datosIdea)) {
                    $datosIdea = [];
                }

                $tituloIdea = trim((string)($filaIdea['idea_seleccionada'] ?? ''));
                if ($tituloIdea === '') {
                    $tituloIdea = trim((string)($datosIdea['idea_seleccionada'] ?? 'Idea de negocio'));
                }

                $categoriaIdea = trim((string)($datosIdea['categoria_personalizada'] ?? ''));
                if ($categoriaIdea === '') {
                    $categoriaIdea = 'Idea de negocio';
                }

                $descripcionIdea = trim((string)($datosIdea['propuesta_valor'] ?? ''));
                if ($descripcionIdea === '') {
                    $descripcionIdea = trim((string)($datosIdea['problema'] ?? ''));
                }
                if ($descripcionIdea === '') {
                    $descripcionIdea = 'Idea de negocio propuesta por un emprendedor de Cusco.';
                }

                $ideasPublicadas[] = [
                    'id' => (int)($filaIdea['id'] ?? 0),
                    'nombre_emprendedor' => trim((string)($filaIdea['nombre'] ?? '')) ?: 'Emprendedor de Cusco',
                    'titulo' => $tituloIdea,
                    'categoria' => $categoriaIdea,
                    'descripcion' => $descripcionIdea,
                    'creado_en' => $filaIdea['creado_en'] ?? null,
                ];
            }
            $resultadoIdeas->free();
        }
    }
} catch (Throwable $errorIdeas) {
    $ideasPublicadas = [];
}

/* ============================================================
   MENSAJE DE CONFIRMACIÓN AL VOLVER DE ideas.php
   ============================================================ */

$mensajeInicio = $_SESSION['mensaje_inicio'] ?? '';
unset($_SESSION['mensaje_inicio']);

/* ============================================================
   HEADER
   ============================================================ */

include 'includes/header.php';
?>

<style>

/* ============================================================
   VARIABLES
   ============================================================ */

:root {
    --primary: #8B0000;
    --primary-dark: #5C0000;
    --primary-light: #A30826;
    --gold: #D4AF37;

    --bg: #F8FAFC;
    --white: #FFFFFF;

    --text: #1F2937;
    --text-soft: #64748B;
    --border: #E5E7EB;

    --shadow:
        0 10px 30px rgba(15, 23, 42, .07);

    --shadow-hover:
        0 18px 40px rgba(139, 0, 0, .13);

    --radius: 18px;

    --transition:
        all .25s ease;
}


/* ============================================================
   BASE
   ============================================================ */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    padding: 0;
    background: var(--bg);
    color: var(--text);
    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;
}

button,
a {
    -webkit-tap-highlight-color: transparent;
}

img {
    max-width: 100%;
}


/* ============================================================
   CONTENEDOR
   ============================================================ */

.ec-container {
    width: min(1200px, calc(100% - 40px));
    margin: 0 auto;
}


/* ============================================================
   BOTONES
   ============================================================ */

.ec-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;

    min-height: 46px;
    padding: 11px 19px;

    border-radius: 11px;

    text-decoration: none;
    font-weight: 700;
    font-size: .92rem;

    border: 0;
    cursor: pointer;

    transition: var(--transition);
}

.ec-btn:hover {
    transform: translateY(-2px);
}

.ec-btn-primary {
    color: #fff;
    background:
        linear-gradient(
            135deg,
            var(--primary-light),
            var(--primary)
        );

    box-shadow:
        0 7px 18px rgba(139, 0, 0, .20);
}

.ec-btn-primary:hover {
    color: #fff;
    background: var(--primary-dark);
}

.ec-btn-outline {
    color: #fff;
    border: 1px solid rgba(255,255,255,.45);
    background: rgba(255,255,255,.10);
    backdrop-filter: blur(8px);
}

.ec-btn-outline:hover {
    color: #fff;
    background: rgba(255,255,255,.20);
}

.ec-btn-gold {
    color: var(--gold);
    border-color: rgba(212,175,55,.7);
}


/* ============================================================
   HERO
   ============================================================ */

.ec-hero {
    position: relative;

    min-height: 600px;

    display: flex;
    align-items: center;

    overflow: hidden;

    color: #fff;

    background: var(--primary-dark);

    border-bottom:
        4px solid var(--primary);
}


/* FONDO */

.ec-hero-slides {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.ec-hero-slide {
    position: absolute;
    inset: 0;

    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;

    opacity: 0;

    transform: scale(1);

    transition:
        opacity 1s ease,
        transform 6s ease;

    will-change: opacity, transform;
}

.ec-hero-slide.active {
    opacity: 1;
    transform: scale(1.04);
}


/* CAPA OSCURA */

.ec-hero-overlay {
    position: absolute;
    inset: 0;

    z-index: 2;

    background:
        linear-gradient(
            90deg,
            rgba(20, 3, 5, .94) 0%,
            rgba(35, 5, 9, .80) 48%,
            rgba(0,0,0,.42) 100%
        );
}


/* CONTENIDO */

.ec-hero-content {
    position: relative;
    z-index: 3;

    width: min(800px, 100%);

    padding: 90px 0;
}

.ec-hero-title {
    margin: 0 0 18px;

    color: #fff;

    font-size:
        clamp(2rem, 5vw, 3.3rem);

    line-height: 1.08;

    font-weight: 850;

    letter-spacing: -.8px;

    text-shadow:
        0 3px 12px rgba(0,0,0,.45);
}

.ec-hero-subtitle {
    margin: 0 0 28px;

    max-width: 720px;

    color: #F1F5F9;

    font-size: 1.08rem;

    line-height: 1.7;
}


/* ALERTA QUECHUA */

.ec-quechua-alert {
    display: flex;
    align-items: flex-start;
    gap: 14px;

    max-width: 720px;

    margin-bottom: 25px;

    padding: 15px 17px;

    color: #1F2937;

    background:
        rgba(255, 251, 235, .94);

    border-left:
        4px solid var(--gold);

    border-radius: 10px;

    box-shadow:
        0 8px 20px rgba(0,0,0,.12);
}

.ec-quechua-alert i {
    margin-top: 3px;

    color: #B45309;

    font-size: 1.3rem;
}

.ec-quechua-alert strong {
    display: block;
    margin-bottom: 4px;
}

.ec-quechua-alert p {
    margin: 0;

    font-size: .88rem;
    line-height: 1.5;
}


/* ACCIONES */

.ec-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;

    margin-bottom: 30px;
}


/* CARACTERÍSTICAS */

.ec-hero-features {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.ec-hero-feature {
    display: flex;
    align-items: center;
    gap: 8px;

    color: #F8FAFC;

    font-size: .92rem;
    font-weight: 650;
}

.ec-hero-feature i {
    color: #FCA5A5;
}


/* ============================================================
   CONTROLES CARRUSEL
   ============================================================ */

.ec-carousel-btn {
    position: absolute;

    top: 50%;
    z-index: 5;

    width: 46px;
    height: 46px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    color: #fff;

    background: rgba(0,0,0,.38);

    border:
        1px solid rgba(255,255,255,.25);

    cursor: pointer;

    transform: translateY(-50%);

    transition: var(--transition);
}

.ec-carousel-btn:hover,
.ec-carousel-btn:focus-visible {
    background: var(--primary);
    outline: none;
}

.ec-carousel-prev {
    left: 22px;
}

.ec-carousel-next {
    right: 22px;
}


/* INDICADORES */

.ec-carousel-indicators {
    position: absolute;

    bottom: 25px;
    right: 30px;

    z-index: 5;

    display: flex;
    gap: 8px;
}

.ec-indicator {
    width: 11px;
    height: 11px;

    padding: 0;

    border: 0;

    border-radius: 20px;

    background: rgba(255,255,255,.42);

    cursor: pointer;

    transition: var(--transition);
}

.ec-indicator.active {
    width: 32px;
    background: var(--gold);
}


/* ============================================================
   ACCESOS RÁPIDOS
   ============================================================ */

.ec-quick-section {
    position: relative;
    z-index: 10;

    margin-top: -42px;
    padding-bottom: 20px;
}

.ec-quick-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}

.ec-quick-card {
    display: flex;
    align-items: center;
    gap: 14px;

    min-height: 105px;

    padding: 19px;

    color: var(--text);

    text-decoration: none;

    background: #fff;

    border:
        1px solid var(--border);

    border-radius: var(--radius);

    box-shadow: var(--shadow);

    transition: var(--transition);
}

.ec-quick-card:hover {
    color: var(--text);

    transform: translateY(-5px);

    border-color:
        rgba(139,0,0,.35);

    box-shadow:
        var(--shadow-hover);
}

.ec-quick-icon {
    width: 52px;
    height: 52px;

    flex-shrink: 0;

    display: grid;
    place-items: center;

    border-radius: 14px;

    color: var(--primary);

    background: #FDECEC;

    font-size: 1.25rem;
}

.ec-quick-content {
    min-width: 0;
}

.ec-quick-title {
    display: block;

    margin-bottom: 4px;

    font-size: .98rem;
    font-weight: 800;
}

.ec-quick-description {
    color: var(--text-soft);

    font-size: .82rem;
}

.ec-quick-arrow {
    margin-left: auto;

    color: #94A3B8;

    transition: var(--transition);
}

.ec-quick-card:hover .ec-quick-arrow {
    color: var(--primary);
    transform: translateX(4px);
}


/* ============================================================
   SECCIONES
   ============================================================ */

.ec-section {
    padding: 70px 0;
}

.ec-section-white {
    background: #fff;
}

.ec-section-light {
    background: #F8FAFC;
}

.ec-section-heading {
    max-width: 800px;

    margin:
        0 auto 32px;

    text-align: center;
}

.ec-section-label {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    color: var(--primary-light);

    font-size: .76rem;

    font-weight: 800;

    letter-spacing: 1px;

    text-transform: uppercase;
}

.ec-section-title {
    margin: 8px 0;

    color: var(--primary-dark);

    font-size:
        clamp(1.65rem, 4vw, 2.2rem);

    line-height: 1.2;
}

.ec-section-description {
    margin: 0;

    color: var(--text-soft);

    font-size: .95rem;

    line-height: 1.7;
}


/* ============================================================
   VIDEOS
   ============================================================ */

.ec-video-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 22px;
}

.ec-video-card {
    overflow: hidden;

    background: #fff;

    border:
        1px solid var(--border);

    border-radius: var(--radius);

    box-shadow: var(--shadow);
}

.ec-video-container {
    position: relative;

    aspect-ratio: 16 / 9;

    background: #111827;
}

.ec-video-container iframe {
    width: 100%;
    height: 100%;

    display: block;

    border: 0;
}

.ec-video-placeholder {
    position: absolute;
    inset: 0;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 12px;

    color: #fff;

    cursor: pointer;

    background:
        linear-gradient(
            135deg,
            #5C0000,
            #A30826
        );
}

.ec-video-placeholder i {
    width: 64px;
    height: 64px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    background: rgba(255,255,255,.15);

    font-size: 1.5rem;
}

.ec-video-placeholder strong {
    font-size: .95rem;
}

.ec-video-info {
    padding: 20px;
}

.ec-video-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 5px 9px;

    color: var(--primary);

    background: #FDECEC;

    border-radius: 7px;

    font-size: .72rem;
    font-weight: 800;
}

.ec-video-title {
    margin: 11px 0 7px;

    color: var(--text);

    font-size: 1.08rem;
}

.ec-video-description {
    margin: 0;

    color: var(--text-soft);

    font-size: .86rem;

    line-height: 1.6;
}

.ec-runasimi-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 8px 13px;

    color: #fff;

    background: var(--primary-dark);

    border-radius: 30px;

    font-size: .78rem;
    font-weight: 700;
}


/* ============================================================
   EMPLEOS
   ============================================================ */

.ec-info-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;
}

.ec-info-card {
    display: flex;
    flex-direction: column;

    overflow: hidden;

    background: #fff;

    border:
        1px solid var(--border);

    border-top:
        4px solid var(--primary);

    border-radius: var(--radius);

    box-shadow: var(--shadow);

    transition: var(--transition);
}

.ec-info-card:hover {
    transform: translateY(-4px);

    box-shadow: var(--shadow-hover);
}

.ec-info-image {
    width: 100%;
    height: 175px;

    display: block;

    object-fit: cover;

    background:
        linear-gradient(
            135deg,
            #7F1D1D,
            #A30826
        );
}

.ec-info-body {
    display: flex;
    flex-direction: column;

    flex: 1;

    padding: 21px;
}

.ec-info-title {
    margin: 10px 0 7px;

    color: var(--text);

    font-size: 1.12rem;
}

.ec-info-description {
    margin: 0;

    color: var(--text-soft);

    font-size: .87rem;

    line-height: 1.6;
}

.ec-info-list {
    padding: 0;
    margin: 18px 0;

    list-style: none;
}

.ec-info-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;

    margin-bottom: 9px;

    color: #475569;

    font-size: .84rem;
}

.ec-info-list li i {
    margin-top: 3px;

    color: var(--primary);
}


/* ============================================================
   IDEAS DE NEGOCIO
   ============================================================ */

.ec-ideas-guide {
    display: flex;
    align-items: flex-start;
    gap: 15px;

    max-width: 950px;

    margin: 0 auto 30px;

    padding: 18px;

    background: #FFF8E1;

    border-left:
        5px solid var(--gold);

    border-radius: 13px;
}

.ec-guide-icon {
    width: 50px;
    height: 50px;

    flex-shrink: 0;

    display: grid;
    place-items: center;

    color: var(--primary);

    background: #fff;

    border-radius: 50%;

    font-size: 1.25rem;
}

.ec-ideas-guide h3 {
    margin: 0 0 5px;

    color: var(--primary-dark);

    font-size: 1.05rem;
}

.ec-ideas-guide p {
    margin: 0;

    color: #5F6368;

    font-size: .87rem;

    line-height: 1.6;
}

.ec-project-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;
}

.ec-project-card {
    display: flex;
    flex-direction: column;

    overflow: hidden;

    background: #fff;

    border:
        1px solid var(--border);

    border-radius: var(--radius);

    box-shadow: var(--shadow);

    transition: var(--transition);
}

.ec-project-card:hover {
    transform: translateY(-5px);

    border-color:
        rgba(139,0,0,.25);

    box-shadow: var(--shadow-hover);
}

.ec-project-image-box {
    position: relative;

    height: 185px;

    overflow: hidden;

    background: #E2E8F0;
}

.ec-category {
    position: absolute;

    top: 13px;
    left: 13px;

    padding: 6px 10px;

    color: #fff;

    background: var(--primary);

    border-radius: 20px;

    font-size: .68rem;
    font-weight: 800;

    letter-spacing: .4px;
}

.ec-project-content {
    display: flex;
    flex-direction: column;

    flex: 1;

    padding: 20px;
}

.ec-project-title {
    margin: 0 0 8px;

    color: var(--text);

    font-size: 1.08rem;

    line-height: 1.35;
}

.ec-project-description {
    margin: 0 0 15px;

    color: var(--text-soft);

    font-size: .85rem;

    line-height: 1.55;
}

.ec-extra {
    margin-top: 12px;

    padding: 12px;

    background: #F8FAFC;

    border-radius: 10px;

    font-size: .79rem;

    line-height: 1.5;
}

.ec-extra strong {
    color: var(--primary-dark);
}

.ec-extra p {
    margin: 3px 0 9px;

    color: #64748B;
}

.ec-project-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 12px;

    margin-top: auto;
    padding-top: 15px;

    border-top:
        1px solid #F1F5F9;
}

.ec-rating {
    color: #334155;

    font-size: .82rem;
}

.ec-view-link {
    color: var(--primary);

    text-decoration: none;

    font-size: .82rem;
    font-weight: 800;
}

.ec-view-link:hover {
    color: var(--primary-light);
    text-decoration: underline;
}


/* ============================================================
   ESTILOS PARA PASOS + CREAR MI IDEA EN PARALELO
   ============================================================ */

.ec-steps-and-idea-container {
    display: grid;
    grid-template-columns: 2.2fr 1fr;
    gap: 25px;
    align-items: center;
    margin-top: 30px;
}

.ec-steps-card {
    padding: 28px 20px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.ec-steps-title {
    margin: 0 0 20px;
    color: var(--primary-dark);
    text-align: left;
    font-size: 1.3rem;
    font-weight: 800;
}

.ec-steps-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

.ec-step {
    padding: 12px 8px;
    text-align: center;
    background: #F8FAFC;
    border-radius: 14px;
}

.ec-step-number {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    margin: 0 auto 10px;
    color: #fff;
    background: var(--primary);
    border-radius: 50%;
    font-weight: 800;
    font-size: 0.95rem;
}

.ec-step strong {
    display: block;
    margin-bottom: 4px;
    color: var(--primary-dark);
    font-size: 0.85rem;
}

.ec-step p {
    margin: 0;
    color: var(--text-soft);
    font-size: 0.74rem;
    line-height: 1.3;
}

.ec-create-idea-card {
    text-align: center;
    padding: 20px;
}

.ec-create-idea-card h3 {
    margin: 0 0 8px;
    color: #1F2937;
    font-size: 1.35rem;
    font-weight: 800;
}

.ec-create-idea-card p {
    margin: 0 0 20px;
    color: var(--text-soft);
    font-size: 0.92rem;
    line-height: 1.5;
}

.ec-btn-idea {
    width: 100%;
    padding: 13px 20px;
    font-size: 0.95rem;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(139, 0, 0, 0.25);
}


/* ============================================================
   SOBRE LA PLATAFORMA
   ============================================================ */

.ec-about-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}

.ec-about-card {
    padding: 25px;

    text-align: center;

    background: #F8FAFC;

    border:
        1px solid #EEF2F7;

    border-radius: 15px;
}

.ec-about-icon {
    margin-bottom: 13px;

    color: var(--primary);

    font-size: 2.2rem;
}

.ec-about-card h3 {
    margin: 0 0 7px;

    font-size: 1rem;
}

.ec-about-card p {
    margin: 0;

    color: var(--text-soft);

    font-size: .84rem;

    line-height: 1.6;
}


/* ESTADÍSTICAS */

.ec-stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-top: 35px;

    padding: 30px;

    color: #fff;

    background:
        linear-gradient(
            135deg,
            var(--primary-dark),
            var(--primary)
        );

    border-radius: var(--radius);

    text-align: center;
}

.ec-stat-number {
    display: block;

    margin-bottom: 5px;

    color: var(--gold);

    font-size: 2.1rem;

    font-weight: 850;
}

.ec-stat-label {
    font-size: .82rem;

    color: #F8FAFC;
}


/* ============================================================
   CTA
   ============================================================ */

.ec-cta {
    padding: 65px 0;

    color: #fff;

    background:
        linear-gradient(
            135deg,
            var(--primary-dark),
            var(--primary)
        );
}

.ec-cta-content {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    flex-wrap: wrap;
}

.ec-cta h2 {
    margin: 0 0 7px;

    color: #fff;

    font-size:
        clamp(1.5rem, 4vw, 2.1rem);
}

.ec-cta p {
    margin: 0;

    color: #F3F4F6;

    line-height: 1.6;
}

.ec-cta-actions {
    display: flex;

    flex-wrap: wrap;

    gap: 10px;
}

.ec-cta-primary {
    color: var(--primary);

    background: #fff;
}

.ec-cta-primary:hover {
    color: var(--primary-dark);

    background: #FDECEC;
}

.ec-cta-secondary {
    color: #fff;

    text-decoration: none;

    padding: 11px 16px;

    font-weight: 700;
}

.ec-cta-secondary:hover {
    color: var(--gold);
}


/* ============================================================
   MODAL FLAYER
   ============================================================ */

.ec-modal {
    position: fixed;

    inset: 0;

    z-index: 99999;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background:
        rgba(0,0,0,.78);

    backdrop-filter:
        blur(5px);
}

.ec-modal.active {
    display: flex;
}

.ec-modal-dialog {
    position: relative;

    width: min(520px, 100%);

    max-height: 92vh;

    overflow: hidden;

    background: #fff;

    border-radius: 18px;

    box-shadow:
        0 25px 70px rgba(0,0,0,.45);

    animation:
        ecModalIn .25s ease;
}

@keyframes ecModalIn {
    from {
        opacity: 0;
        transform: scale(.94);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.ec-modal-close {
    position: absolute;

    top: 12px;
    right: 12px;

    z-index: 2;

    width: 36px;
    height: 36px;

    display: grid;
    place-items: center;

    color: #fff;

    background:
        rgba(0,0,0,.65);

    border: 0;

    border-radius: 50%;

    cursor: pointer;
}

.ec-modal-close:hover {
    background: var(--primary);
}

.ec-modal-image {
    width: 100%;

    max-height: 70vh;

    display: block;

    object-fit: contain;

    background: #F1F5F9;
}

.ec-modal-footer {
    padding: 15px;

    background: #FAFAFA;

    border-top:
        1px solid var(--border);
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 1000px) {

    .ec-project-grid,
    .ec-info-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .ec-steps-grid {
        grid-template-columns:
            repeat(3, 1fr);
    }

    .ec-stats {
        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 900px) {
    .ec-steps-and-idea-container {
        grid-template-columns: 1fr;
    }

    .ec-steps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}


@media (max-width: 800px) {

    .ec-hero {
        min-height: 560px;
    }

    .ec-carousel-btn {
        display: none;
    }

    .ec-quick-grid {
        grid-template-columns: 1fr;
    }

    .ec-video-grid {
        grid-template-columns: 1fr;
    }

    .ec-about-grid {
        grid-template-columns: 1fr;
    }
}


@media (max-width: 650px) {

    .ec-container {
        width:
            min(100% - 28px, 1200px);
    }

    .ec-section {
        padding: 52px 0;
    }

    .ec-hero-content {
        padding: 75px 0 80px;
    }

    .ec-hero-title {
        font-size: 2rem;
    }

    .ec-hero-subtitle {
        font-size: .96rem;
    }

    .ec-hero-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .ec-hero-actions .ec-btn {
        width: 100%;
    }

    .ec-hero-features {
        flex-direction: column;
        gap: 10px;
    }

    .ec-project-grid,
    .ec-info-grid {
        grid-template-columns: 1fr;
    }

    .ec-stats {
        grid-template-columns: 1fr 1fr;

        padding: 20px 12px;
    }

    .ec-stat-number {
        font-size: 1.7rem;
    }

    .ec-cta-content {
        align-items: stretch;
    }

    .ec-cta-actions {
        width: 100%;
    }

    .ec-cta-actions .ec-btn {
        flex: 1;
    }

    .ec-carousel-indicators {
        right: 50%;

        transform:
            translateX(50%);
    }
}


@media (max-width: 550px) {
    .ec-steps-grid {
        grid-template-columns: 1fr;
    }
}


@media (max-width: 430px) {

    .ec-stats {
        grid-template-columns: 1fr;
    }

    .ec-ideas-guide {
        flex-direction: column;
    }

    .ec-project-footer {
        align-items: flex-start;

        flex-direction: column;
    }

    .ec-view-link {
        width: 100%;

        padding: 9px;

        text-align: center;

        background: #FDECEC;

        border-radius: 8px;
    }
}


/* ============================================================
   REDUCCIÓN DE MOVIMIENTO
   ============================================================ */

@media (prefers-reduced-motion: reduce) {

    html {
        scroll-behavior: auto;
    }

    *,
    *::before,
    *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}

</style>

<?php if ($mensajeInicio !== ''): ?>
    <div class="ec-container" style="margin-top:18px;">
        <div role="status" style="padding:14px 18px;border-radius:10px;background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo e($mensajeInicio); ?>
        </div>
    </div>
<?php endif; ?>


<!-- ============================================================
     MODAL FLAYER
     ============================================================ -->

<div
    class="ec-modal"
    id="flayerModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="flayerTitle"
    aria-hidden="true"
>

    <div class="ec-modal-dialog">

        <button
            type="button"
            class="ec-modal-close"
            id="flayerClose"
            aria-label="Cerrar ventana"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 id="flayerTitle" class="sr-only">
            Comunicación del Gobierno Regional del Cusco
        </h2>

        <img
            src="imagenes\q.jpg"
            alt="Comunicación del Gobierno Regional del Cusco"
            class="ec-modal-image"
            loading="lazy"
            onerror="this.style.display='none';"
        >

        <div class="ec-modal-footer">

            <a
                href="registro.php"
                class="ec-btn ec-btn-primary"
                style="width:100%;"
            >
                <i class="fa-solid fa-user-plus"></i>
                Ver comunicación
            </a>

        </div>

    </div>

</div>


<!-- ============================================================
     HERO
     ============================================================ -->

<section
    class="ec-hero"
    id="inicio"
    aria-label="Emprende Cusco"
>

    <div
        class="ec-hero-slides"
        aria-hidden="true"
    >

        <?php foreach ($hero_images as $index => $image): ?>

            <div
                class="ec-hero-slide <?php echo $index === 0 ? 'active' : ''; ?>"
                style="
                    background-image:
                    url('<?php echo e($image); ?>');
                "
            ></div>

        <?php endforeach; ?>

    </div>


    <div class="ec-hero-overlay"></div>


    <!-- ANTERIOR -->

    <button
        type="button"
        class="ec-carousel-btn ec-carousel-prev"
        id="heroPrev"
        aria-label="Imagen anterior"
    >
        <i class="fa-solid fa-chevron-left"></i>
    </button>


    <!-- SIGUIENTE -->

    <button
        type="button"
        class="ec-carousel-btn ec-carousel-next"
        id="heroNext"
        aria-label="Imagen siguiente"
    >
        <i class="fa-solid fa-chevron-right"></i>
    </button>


    <div class="ec-container">

        <div class="ec-hero-content">

            <h1 class="ec-hero-title">
                <?php echo e($txt['hero_title']); ?>
            </h1>

            <p class="ec-hero-subtitle">
                <?php echo e($txt['hero_sub']); ?>
            </p>


            <?php if ($lang === 'qu'): ?>

                <div class="ec-quechua-alert">

                    <i class="fa-solid fa-language"></i>

                    <div>

                        <strong>
                            Allichakuq Runasimipi
                        </strong>

                        <p>
                            Kay portalpiqa llank’anakunata,
                            yachaykunata hinaspa mosoq
                            negociokunata tariyta atinki.
                        </p>

                    </div>

                </div>

            <?php endif; ?>


            <div class="ec-hero-actions">

                <a
                    href="registro.php"
                    class="ec-btn ec-btn-primary"
                >
                    <i class="fa-solid fa-user-plus"></i>

                    <?php echo e($txt['crear_cuenta']); ?>

                </a>


                <a
                    href="login.php"
                    class="ec-btn ec-btn-outline"
                >
                    <i class="fa-solid fa-right-to-bracket"></i>

                    <?php echo e($txt['ingresar']); ?>

                </a>


                <button
                    type="button"
                    id="openFlayer"
                    class="ec-btn ec-btn-outline ec-btn-gold"
                >
                    <i class="fa-solid fa-landmark"></i>

                    Gobierno Regional
                </button>

            </div>


            <div class="ec-hero-features">

                <div class="ec-hero-feature">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Empleo</span>
                </div>

                <div class="ec-hero-feature">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Emprendimiento</span>
                </div>

                <div class="ec-hero-feature">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Capacitación</span>
                </div>

                <div class="ec-hero-feature">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Runasimi</span>
                </div>

            </div>

        </div>

    </div>


    <!-- INDICADORES -->

    <div
        class="ec-carousel-indicators"
        id="heroIndicators"
        aria-label="Seleccionar imagen"
    ></div>

</section>


<!-- ============================================================
     ACCESOS RÁPIDOS
     ============================================================ -->

<section class="ec-quick-section">

    <div class="ec-container">

        <div class="ec-quick-grid">


            <a
                href="empleos.php"
                class="ec-quick-card"
            >

                <div class="ec-quick-icon">
                    <i class="fa-solid fa-briefcase"></i>
                </div>

                <div class="ec-quick-content">

                    <span class="ec-quick-title">
                        Buscar empleo
                    </span>

                    <span class="ec-quick-description">
                        Explora oportunidades laborales
                    </span>

                </div>

                <i class="fa-solid fa-chevron-right ec-quick-arrow"></i>

            </a>


            <a
                href="ideas.php"
                class="ec-quick-card"
            >

                <div class="ec-quick-icon">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>

                <div class="ec-quick-content">

                    <span class="ec-quick-title">
                        Ideas de negocio
                    </span>

                    <span class="ec-quick-description">
                        Descubre opciones para emprender
                    </span>

                </div>

                <i class="fa-solid fa-chevron-right ec-quick-arrow"></i>

            </a>


            <a
                href="registro.php"
                class="ec-quick-card"
            >

                <div class="ec-quick-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>

                <div class="ec-quick-content">

                    <span class="ec-quick-title">
                        Crear mi perfil
                    </span>

                    <span class="ec-quick-description">
                        Regístrate y muestra tus habilidades
                    </span>

                </div>

                <i class="fa-solid fa-chevron-right ec-quick-arrow"></i>

            </a>


        </div>

    </div>

</section>


<!-- ============================================================
     CAPACITACIÓN
     ============================================================ -->

<section class="ec-section ec-section-white">

    <div class="ec-container">

        <div class="ec-section-heading">

            <span class="ec-section-label">

                <i class="fa-solid fa-circle-play"></i>

                Capacitación

            </span>


            <h2 class="ec-section-title">

                <?php echo e($txt['seccion_video']); ?>

            </h2>


            <p class="ec-section-description">

                Aprende sobre empleo, emprendimiento y oportunidades
                mediante recursos audiovisuales.

            </p>


            <div style="margin-top:15px;">

                <span class="ec-runasimi-badge">

                    <i class="fa-solid fa-headphones"></i>

                    Audio & Video Runasimi

                </span>

            </div>

        </div>


        <div class="ec-video-grid">


            <!-- VIDEO 1 -->

            <article class="ec-video-card">

                <div class="ec-video-container">

                    <div
                        class="ec-video-placeholder"
                        data-video="aJtejLrH854"
                        tabindex="0"
                        role="button"
                        aria-label="Reproducir tutorial de Bolsa de Trabajo"
                    >

                        <i class="fa-solid fa-play"></i>

                        <strong>
                            Reproducir tutorial
                        </strong>

                    </div>

                </div>


                <div class="ec-video-info">

                    <span class="ec-video-tag">

                        <i class="fa-solid fa-briefcase"></i>

                        Llank'ana Tariypaq

                    </span>


                    <h3 class="ec-video-title">

                        <?php
                        echo $lang === 'qu'
                            ? 'Imaynam llank’ana tariyta atinki'
                            : 'Bolsa de Trabajo: búsqueda de empleo';
                        ?>

                    </h3>


                    <p class="ec-video-description">

                        Tutorial para conocer herramientas
                        relacionadas con la búsqueda de oportunidades
                        laborales.

                    </p>

                </div>

            </article>


            <!-- VIDEO 2 -->

            <article class="ec-video-card">

                <div class="ec-video-container">

                    <div
                        class="ec-video-placeholder"
                        data-video="IWW0HCGfldA"
                        tabindex="0"
                        role="button"
                        aria-label="Reproducir tutorial de ideas de negocio"
                    >

                        <i class="fa-solid fa-play"></i>

                        <strong>
                            Reproducir tutorial
                        </strong>

                    </div>

                </div>


                <div class="ec-video-info">

                    <span class="ec-video-tag">

                        <i class="fa-solid fa-lightbulb"></i>

                        Mosoq Yuyaykuna

                    </span>


                    <h3 class="ec-video-title">

                        <?php
                        echo $lang === 'qu'
                            ? 'Imaynam mosoq negociota qallarinki'
                            : 'Ideas de negocio en quechua';
                        ?>

                    </h3>


                    <p class="ec-video-description">

                        Aprende conceptos básicos para transformar
                        una idea en una oportunidad de emprendimiento.

                    </p>

                </div>

            </article>


        </div>

    </div>

</section>


<!-- ============================================================
     EMPLEOS
     ============================================================ -->

<section class="ec-section ec-section-light">

    <div class="ec-container">

        <div class="ec-section-heading">

            <span class="ec-section-label">

                <i class="fa-solid fa-magnifying-glass"></i>

                Oportunidades

            </span>


            <h2 class="ec-section-title">

                <?php echo e($txt['empleos']); ?>

            </h2>


            <p class="ec-section-description">

                Conoce sectores donde puedes encontrar oportunidades
                laborales y desarrollar nuevas habilidades.

            </p>

        </div>


        <div class="ec-info-grid">


            <!-- TURISMO -->

            <article class="ec-info-card">

                <img
                    src="hotel.jpg"
                    alt="Sector turismo y hotelería"
                    class="ec-info-image"
                    loading="lazy"
                    onerror="this.style.display='none';"
                >


                <div class="ec-info-body">

                    <span class="ec-video-tag">

                        <i class="fa-solid fa-hotel"></i>

                        Turismo

                    </span>


                    <h3 class="ec-info-title">

                        Hostelería y Turismo

                    </h3>


                    <p class="ec-info-description">

                        Oportunidades relacionadas con hoteles,
                        restaurantes, atención al visitante y
                        servicios turísticos.

                    </p>


                    <ul class="ec-info-list">

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Recepción y atención
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Gastronomía
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Asistencia turística
                        </li>

                    </ul>


                    <a
                        href="empleos.php?sector=turismo"
                        class="ec-btn ec-btn-primary"
                        style="width:100%; margin-top:auto;"
                    >

                        Ver oportunidades

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            </article>


            <!-- COMERCIO -->

            <article class="ec-info-card">

                <img
                    src="comercio y ventas.jpg"
                    alt="Sector comercio y ventas"
                    class="ec-info-image"
                    loading="lazy"
                    onerror="this.style.display='none';"
                >


                <div class="ec-info-body">

                    <span class="ec-video-tag">

                        <i class="fa-solid fa-store"></i>

                        Comercio

                    </span>


                    <h3 class="ec-info-title">

                        Ventas y Atención

                    </h3>


                    <p class="ec-info-description">

                        Oportunidades en tiendas, galerías,
                        establecimientos comerciales y atención
                        al cliente.

                    </p>


                    <ul class="ec-info-list">

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Ventas
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Atención al cliente
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Auxiliar de almacén
                        </li>

                    </ul>


                    <a
                        href="empleos.php?sector=comercio"
                        class="ec-btn ec-btn-primary"
                        style="width:100%; margin-top:auto;"
                    >

                        Ver oportunidades

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            </article>


            <!-- TECNOLOGÍA -->

            <article class="ec-info-card">

                <img
                    src="tecnologia.jpg"
                    alt="Sector tecnología y servicios digitales"
                    class="ec-info-image"
                    loading="lazy"
                    onerror="this.style.display='none';"
                >


                <div class="ec-info-body">

                    <span class="ec-video-tag">

                        <i class="fa-solid fa-laptop"></i>

                        Digital

                    </span>


                    <h3 class="ec-info-title">

                        Tecnología y Servicios Digitales

                    </h3>


                    <p class="ec-info-description">

                        Actividades relacionadas con herramientas
                        digitales, contenido, diseño y soporte
                        tecnológico.

                    </p>


                    <ul class="ec-info-list">

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Redes sociales
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Diseño gráfico
                        </li>

                        <li>
                            <i class="fa-solid fa-check-circle"></i>
                            Soporte y digitación
                        </li>

                    </ul>


                    <a
                        href="empleos.php?sector=tecnologia"
                        class="ec-btn ec-btn-primary"
                        style="width:100%; margin-top:auto;"
                    >

                        Ver oportunidades

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            </article>


        </div>

    </div>

</section>


<!-- ============================================================
     SOBRE EMPRENDE CUSCO
     ============================================================ -->

<section class="ec-section ec-section-white">

    <div class="ec-container">

        <div class="ec-section-heading">

            <span class="ec-section-label">

                <i class="fa-solid fa-circle-info"></i>

                Nuestra plataforma

            </span>


            <h2 class="ec-section-title">

                Impulsamos el talento y la innovación cusqueña

            </h2>


            <p class="ec-section-description">

                Emprende Cusco integra información sobre empleo,
                emprendimiento y capacitación en una sola plataforma,
                buscando facilitar el acceso a oportunidades y recursos.

            </p>

        </div>


        <div class="ec-about-grid">


            <article class="ec-about-card">

                <div class="ec-about-icon">

                    <i class="fa-solid fa-language"></i>

                </div>

                <h3>
                    Inclusión bilingüe
                </h3>

                <p>
                    Información disponible en español y recursos
                    audiovisuales en Runasimi.
                </p>

            </article>


            <article class="ec-about-card">

                <div class="ec-about-icon">

                    <i class="fa-solid fa-seedling"></i>

                </div>

                <h3>
                    Emprendimiento
                </h3>

                <p>
                    Ideas de negocio, orientación inicial y pasos
                    prácticos para desarrollar un proyecto.
                </p>

            </article>


            <article class="ec-about-card">

                <div class="ec-about-icon">

                    <i class="fa-solid fa-handshake"></i>

                </div>

                <h3>
                    Conexión
                </h3>

                <p>
                    Un espacio digital para acercar a las personas
                    a oportunidades laborales y de emprendimiento.
                </p>

            </article>


        </div>


        <div class="ec-stats">

            <div>

                <span class="ec-stat-number">
                    +150
                </span>

                <span class="ec-stat-label">
                    Empleos publicados
                </span>

            </div>


            <div>

                <span class="ec-stat-number">
                    +45
                </span>

                <span class="ec-stat-label">
                    Ideas de emprendimiento
                </span>

            </div>


            <div>

                <span class="ec-stat-number">
                    100%
                </span>

                <span class="ec-stat-label">
                    Acceso gratuito
                </span>

            </div>


            <div>

                <span class="ec-stat-number">
                    13
                </span>

                <span class="ec-stat-label">
                    Provincias del Cusco
                </span>

            </div>

        </div>

    </div>

</section>


<!-- ============================================================
     IDEAS DE NEGOCIO Y PASOS PARA EMPRENDER
     ============================================================ -->

<section class="ec-section ec-section-light" id="ideas">

    <div class="ec-container">

        <div class="ec-section-heading">

            <span class="ec-section-label">

                <i class="fa-solid fa-lightbulb"></i>

                Emprende

            </span>


            <h2 class="ec-section-title">

                <?php echo e($txt['ideas']); ?>

            </h2>


            <p class="ec-section-description">

                Conoce diferentes alternativas de emprendimiento,
                sus posibles clientes, recursos iniciales y formas
                de promoción.

            </p>

        </div>


        <!-- GUÍA -->

        <div class="ec-ideas-guide">

            <div class="ec-guide-icon">

                <i class="fa-solid fa-route"></i>

            </div>


            <div>

                <h3>
                    ¿Cómo convertir una idea en un negocio?
                </h3>

                <p>

                    Identifica una necesidad, define tu producto
                    o servicio, conoce a tus clientes, calcula
                    tus costos, establece un precio y promociona
                    tu propuesta mediante canales adecuados.

                </p>

            </div>

        </div>

        <?php if (!empty($ideasPublicadas)): ?>
            <div style="margin:30px 0 18px;">
                <h3 style="margin:0 0 8px;color:var(--primary-dark);font-size:1.35rem;">
                    Ideas publicadas por emprendedores
                </h3>
                <p style="margin:0;color:var(--text-soft);font-size:.9rem;">
                    Estas ideas se cargan automáticamente desde los planes guardados en la plataforma.
                </p>
            </div>

            <div class="ec-project-grid" style="margin-bottom:35px;">
                <?php foreach ($ideasPublicadas as $ideaPublicada): ?>
                    <article class="ec-project-card">
                        <div class="ec-project-image-box" style="display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#5C0000,#A30826);">
                            <i class="fa-solid fa-lightbulb" style="font-size:3.5rem;color:#fff;opacity:.9;"></i>
                            <span class="ec-category">Idea de negocio · <?php echo e($ideaPublicada['categoria'] ?? 'Emprendimiento'); ?></span>
                        </div>

                        <div class="ec-project-content">
                            <h3 class="ec-project-title"><?php echo e($ideaPublicada['titulo'] ?? 'Idea de negocio'); ?></h3>

                            <p class="ec-project-description">
                                <?php
                                $descripcionIdea = trim((string)($ideaPublicada['descripcion'] ?? ''));
                                echo e($descripcionIdea !== '' ? $descripcionIdea : 'Idea de negocio propuesta por un emprendedor de Cusco.');
                                ?>
                            </p>

                            <div class="ec-extra">
                                <strong>Emprendedor</strong>
                                <p><?php echo e($ideaPublicada['nombre_emprendedor'] ?? 'Emprendedor de Cusco'); ?></p>
                                <?php if (!empty($ideaPublicada['creado_en'])): ?>
                                    <strong>Fecha de publicación</strong>
                                    <p><?php echo e(date('d/m/Y', strtotime($ideaPublicada['creado_en']))); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="ec-project-footer">
                                <span class="ec-rating"><i class="fa-solid fa-circle-check" style="color:#15803D;"></i> Idea de negocio publicada</span>
                                <a href="ideas.php" class="ec-view-link">Ver ideas y crear plan →</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <!-- ============================================================
             PASOS E IDEA DE NEGOCIO ALINEADOS HORIZONTALMENTE
             ============================================================ -->

        <div class="ec-steps-and-idea-container">

            <!-- PASOS PARA INICIAR -->
            <div class="ec-steps-card">

                <h3 class="ec-steps-title">
                    🚀 Pasos para iniciar tu emprendimiento
                </h3>

                <div class="ec-steps-grid">

                    <div class="ec-step">
                        <span class="ec-step-number">1</span>
                        <strong>Identifica</strong>
                        <p>Encuentra una necesidad o problema.</p>
                    </div>

                    <div class="ec-step">
                        <span class="ec-step-number">2</span>
                        <strong>Propón</strong>
                        <p>Define tu producto o servicio.</p>
                    </div>

                    <div class="ec-step">
                        <span class="ec-step-number">3</span>
                        <strong>Calcula</strong>
                        <p>Estima costos e inversión.</p>
                    </div>

                    <div class="ec-step">
                        <span class="ec-step-number">4</span>
                        <strong>Promociona</strong>
                        <p>Utiliza medios digitales.</p>
                    </div>

                    <div class="ec-step">
                        <span class="ec-step-number">5</span>
                        <strong>Evalúa</strong>
                        <p>Consulta a tus posibles clientes.</p>
                    </div>

                </div>

            </div>

            <!-- BLOQUE CREAR IDEA -->
            <div class="ec-create-idea-card">

                <h3>¿Tienes una idea propia?</h3>

                <p>
                    Regístrala y empieza a convertirla en un
                    proyecto de emprendimiento.
                </p>

                <a href="registro.php" class="ec-btn ec-btn-primary ec-btn-idea">
                    <i class="fa-solid fa-lightbulb"></i>
                    Crear mi idea de negocio
                </a>

            </div>

        </div>

    </div>

</section>


<!-- ============================================================
     CTA
     ============================================================ -->

<section class="ec-cta">

    <div class="ec-container">

        <div class="ec-cta-content">

            <div>

                <h2>
                    ¿Listo para dar el siguiente paso?
                </h2>

                <p>
                    Crea tu perfil y comienza a explorar las
                    oportunidades disponibles.
                </p>

            </div>


            <div class="ec-cta-actions">

                <a
                    href="registro.php"
                    class="ec-btn ec-cta-primary"
                >

                    <i class="fa-solid fa-rocket"></i>

                    Registrarme ahora

                </a>


                <a
                    href="contacto.php"
                    class="ec-cta-secondary"
                >
                    Contacto y ayuda
                </a>

            </div>

        </div>

    </div>

</section>


<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->

<script>

(function () {

    'use strict';


    /* ========================================================
       MODAL FLAYER
       ======================================================== */

    const modal =
        document.getElementById('flayerModal');

    const openButton =
        document.getElementById('openFlayer');

    const closeButton =
        document.getElementById('flayerClose');


    function openFlayer() {

        if (!modal) return;

        modal.classList.add('active');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.style.overflow = 'hidden';

        if (closeButton) {
            closeButton.focus();
        }
    }


    function closeFlayer() {

        if (!modal) return;

        modal.classList.remove('active');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.style.overflow = '';

        if (openButton) {
            openButton.focus();
        }
    }


    if (openButton) {
        openButton.addEventListener(
            'click',
            openFlayer
        );
    }


    if (closeButton) {
        closeButton.addEventListener(
            'click',
            closeFlayer
        );
    }


    if (modal) {

        modal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target === modal
                ) {
                    closeFlayer();
                }

            }
        );

    }


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal &&
                modal.classList.contains('active')
            ) {
                closeFlayer();
            }

        }
    );


    /* ========================================================
       CARRUSEL
       ======================================================== */

    const slides =
        Array.from(
            document.querySelectorAll(
                '.ec-hero-slide'
            )
        );

    const indicatorsContainer =
        document.getElementById(
            'heroIndicators'
        );

    const previousButton =
        document.getElementById(
            'heroPrev'
        );

    const nextButton =
        document.getElementById(
            'heroNext'
        );


    let currentSlide = 0;

    let carouselTimer = null;


    if (slides.length > 0) {


        /* CREAR INDICADORES */

        slides.forEach(
            function (_, index) {

                const indicator =
                    document.createElement(
                        'button'
                    );

                indicator.type = 'button';

                indicator.className =
                    'ec-indicator';

                indicator.setAttribute(
                    'aria-label',
                    'Mostrar imagen ' +
                    (index + 1)
                );

                indicator.setAttribute(
                    'aria-current',
                    index === 0
                        ? 'true'
                        : 'false'
                );

                indicator.addEventListener(
                    'click',
                    function () {

                        showSlide(index);

                        restartCarousel();

                    }
                );

                indicatorsContainer.appendChild(
                    indicator
                );

            }
        );


        const indicators =
            Array.from(
                document.querySelectorAll(
                    '.ec-indicator'
                )
            );


        function showSlide(index) {

            if (
                index < 0
            ) {
                index =
                    slides.length - 1;
            }

            if (
                index >= slides.length
            ) {
                index = 0;
            }

            currentSlide = index;


            slides.forEach(
                function (slide, i) {

                    slide.classList.toggle(
                        'active',
                        i === currentSlide
                    );

                }
            );


            indicators.forEach(
                function (indicator, i) {

                    const active =
                        i === currentSlide;

                    indicator.classList.toggle(
                        'active',
                        active
                    );

                    indicator.setAttribute(
                        'aria-current',
                        active
                            ? 'true'
                            : 'false'
                    );

                }
            );

        }


        function nextSlide() {

            showSlide(
                currentSlide + 1
            );

        }


        function previousSlide() {

            showSlide(
                currentSlide - 1
            );

        }


        function startCarousel() {

            if (
                slides.length <= 1
            ) return;

            clearInterval(
                carouselTimer
            );

            carouselTimer =
                setInterval(
                    nextSlide,
                    6000
                );

        }


        function restartCarousel() {

            clearInterval(
                carouselTimer
            );

            startCarousel();

        }


        if (nextButton) {

            nextButton.addEventListener(
                'click',
                function () {

                    nextSlide();

                    restartCarousel();

                }
            );

        }


        if (previousButton) {

            previousButton.addEventListener(
                'click',
                function () {

                    previousSlide();

                    restartCarousel();

                }
            );

        }


        /* PAUSAR AL PASAR EL MOUSE */

        const hero =
            document.querySelector(
                '.ec-hero'
            );


        if (hero) {

            hero.addEventListener(
                'mouseenter',
                function () {

                    clearInterval(
                        carouselTimer
                    );

                }
            );


            hero.addEventListener(
                'mouseleave',
                function () {

                    startCarousel();

                }
            );

        }


        showSlide(0);

        startCarousel();

    }


    /* ========================================================
       VIDEOS DE YOUTUBE - CARGA DIFERIDA
       ======================================================== */

    const videoButtons =
        document.querySelectorAll(
            '.ec-video-placeholder'
        );


    videoButtons.forEach(
        function (placeholder) {

            function loadVideo() {

                if (
                    placeholder.dataset.loaded === 'true'
                ) {
                    return;
                }

                const videoId =
                    placeholder.dataset.video;

                if (!videoId) {
                    return;
                }


                const iframe =
                    document.createElement(
                        'iframe'
                    );


                iframe.src =
                    'https://www.youtube.com/embed/' +
                    encodeURIComponent(videoId) +
                    '?rel=0';


                iframe.title =
                    'Video de capacitación Emprende Cusco';


                iframe.loading =
                    'lazy';


                iframe.allowFullscreen =
                    true;


                iframe.setAttribute(
                    'allow',
                    'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
                );


                placeholder.parentNode.replaceChild(
                    iframe,
                    placeholder
                );


                placeholder.dataset.loaded =
                    'true';

            }


            placeholder.addEventListener(
                'click',
                loadVideo
            );


            placeholder.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Enter' ||
                        event.key === ' '
                    ) {

                        event.preventDefault();

                        loadVideo();

                    }

                }
            );

        }
    );


})();

</script>


<?php

/* ============================================================
   FOOTER
   ============================================================ */

include 'includes/footer.php';

ob_end_flush();