
<?php
/* =========================================================
   EMPRENDE CUSCO - HEADER PRINCIPAL
   Compatible con login.php y logout.php
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   IDIOMA
========================================================= */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] === 'qu') ? 'qu' : 'es';
}

$lang = $_SESSION['lang'] ?? 'es';

/* =========================================================
   TEXTOS DE NAVEGACIÓN
========================================================= */
$nav_text = [
    'es' => [
        'inicio'          => 'Inicio',
        'ideas'           => 'Ideas de Negocio',
        'empleos'         => 'Empleos',
        'publicar_idea'   => 'Publicar Idea',
        'publicar_empleo' => 'Publicar Empleo',
        'crear_cuenta'    => 'Crear cuenta',
        'ingresar'        => 'Ingresar',
        'salir'           => 'Cerrar sesión',
        'bienvenido'      => 'Bienvenido',
        'perfil'          => 'Mi Perfil'
    ],
    'qu' => [
        'inicio'          => 'Qallariy',
        'ideas'           => 'Mosoq Yuyaykuna',
        'empleos'         => "Llank'anakuna",
        'publicar_idea'   => 'Yuyaychay Churay',
        'publicar_empleo' => "Llank'ana Churay",
        'crear_cuenta'    => 'Qillqakuy',
        'ingresar'        => 'Yaykuy',
        'salir'           => 'Lluqsay',
        'bienvenido'      => 'Allin hamusqa',
        'perfil'          => 'Ñuqa Kani'
    ]
];

$t_nav = $nav_text[$lang];

/* =========================================================
   USUARIO: MISMAS VARIABLES QUE login.php
========================================================= */
$usuarioLogueado = !empty($_SESSION['usuario_id']);

$nombreUsuario = '';

if ($usuarioLogueado) {
    $nombreUsuario = trim(
        (string)($_SESSION['usuario_nombre'] ?? '')
    );

    if ($nombreUsuario === '') {
        $nombreUsuario = trim(
            (string)($_SESSION['usuario_email'] ?? '')
        );
    }

    if ($nombreUsuario === '') {
        $nombreUsuario = 'Mi cuenta';
    }
}

$nombreUsuarioSeguro = htmlspecialchars(
    $nombreUsuario,
    ENT_QUOTES,
    'UTF-8'
);

$inicialUsuario = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombreUsuario, 0, 1));

$inicialUsuario = htmlspecialchars(
    $inicialUsuario,
    ENT_QUOTES,
    'UTF-8'
);

/* =========================================================
   TÍTULO DE LA PÁGINA
========================================================= */
$tituloPagina = $pageTitle
    ?? 'Emprende Cusco - Plataforma de Empleabilidad y Emprendimiento';
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Emprende Cusco: plataforma de empleo, emprendimiento y oportunidades laborales."
    >

    <meta name="theme-color" content="#8B0000">

    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        :root {
            --rojo: #8B0000;
            --rojo-oscuro: #620000;
            --rojo-claro: #B21F2D;
            --rojo-suave: #FFF5F5;
            --dorado: #D4AF37;
            --texto: #1F2937;
            --gris: #6B7280;
            --borde: #E5E7EB;
            --blanco: #FFFFFF;
            --sombra: 0 4px 18px rgba(0, 0, 0, .08);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #F7F7F7;
            color: var(--texto);
        }

        a {
            text-decoration: none;
        }

        button,
        input {
            font-family: inherit;
        }

        /* HEADER */
        .main-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            background: rgba(255, 255, 255, .98);
            border-bottom: 3px solid var(--rojo);
            box-shadow: var(--sombra);
            backdrop-filter: blur(10px);
        }

        /* BARRA SUPERIOR */
        .top-bar {
            background: linear-gradient(90deg, #650000, #8B0000, #650000);
            color: white;
        }

        .top-container {
            width: 100%;
            max-width: 1250px;
            min-height: 32px;
            margin: 0 auto;
            padding: 5px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            font-size: 12px;
        }

        .top-left,
        .top-left span,
        .top-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-left {
            gap: 20px;
        }

        .top-container i {
            color: var(--dorado);
        }

        /* CONTENEDOR PRINCIPAL */
        .header-container {
            width: 100%;
            max-width: 1250px;
            min-height: 76px;
            margin: 0 auto;
            padding: 9px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* LOGO */
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 230px;
            max-width: 370px;
            color: var(--rojo);
            flex-shrink: 1;
        }

        .brand-img {
            width: auto;
            height: 48px;
            max-width: 120px;
            object-fit: contain;
            flex-shrink: 0;
            border-radius: 4px;
        }

        .brand-name {
            font-size: 15px;
            line-height: 1.2;
            font-weight: 800;
            color: var(--rojo);
        }

        .brand-subtitle {
            display: block;
            margin-top: 4px;
            color: var(--gris);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        /* NAVEGACIÓN */
        .main-nav {
            margin-left: auto;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 3px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 9px;
            border-radius: 8px;
            color: var(--texto);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            transition: .2s ease;
        }

        .nav-link i {
            color: var(--rojo);
        }

        .nav-link:hover {
            background: var(--rojo-suave);
            color: var(--rojo);
        }

        /* IDIOMA */
        .lang-switch {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 3px;
            flex-shrink: 0;
            background: #F5F5F5;
            border: 1px solid var(--borde);
            border-radius: 8px;
        }

        .lang-btn {
            display: block;
            padding: 6px 8px;
            border-radius: 6px;
            color: #555;
            font-size: 10px;
            font-weight: 700;
        }

        .lang-btn:hover {
            color: var(--rojo);
            background: white;
        }

        .lang-btn.active {
            background: var(--rojo);
            color: white;
        }

        /* BOTONES */
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            padding: 8px 11px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-outline {
            background: white;
            color: var(--texto);
            border: 1px solid #D1D5DB;
        }

        .btn-outline:hover {
            color: var(--rojo);
            border-color: var(--rojo);
            background: var(--rojo-suave);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--rojo), var(--rojo-claro));
            color: white;
            border: 1px solid var(--rojo);
            box-shadow: 0 4px 10px rgba(139, 0, 0, .15);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--rojo-oscuro), var(--rojo));
        }

        /* USUARIO */
        .user-dropdown {
            position: relative;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 5px 9px;
            border-radius: 22px;
            background: #F3F4F6;
            cursor: pointer;
            border: 0;
            color: var(--texto);
        }

        .user-box:hover {
            background: #E5E7EB;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--rojo), var(--rojo-claro));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .user-name {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 700;
        }

        /* MENÚ DEL USUARIO */
        .user-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 195px;
            overflow: hidden;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .12);
            z-index: 1001;
        }

        .user-menu.show {
            display: block;
        }

        .user-menu a {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 12px 14px;
            color: var(--texto);
            font-size: 13px;
            font-weight: 600;
        }

        .user-menu a i {
            width: 18px;
            color: var(--rojo);
        }

        .user-menu a:hover {
            background: var(--rojo-suave);
            color: var(--rojo);
        }

        .user-menu .logout-link {
            color: #B91C1C;
            border-top: 1px solid var(--borde);
        }

        .user-menu .logout-link i {
            color: #B91C1C;
        }

        /* BOTÓN MÓVIL */
        .mobile-menu-btn {
            display: none;
            width: 38px;
            height: 38px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            background: var(--rojo-suave);
            color: var(--rojo);
            cursor: pointer;
            font-size: 17px;
            align-items: center;
            justify-content: center;
        }

        /* MENÚ MÓVIL */
        .mobile-menu {
            display: none;
            width: 100%;
            background: white;
            border-top: 1px solid var(--borde);
            box-shadow: 0 7px 15px rgba(0, 0, 0, .08);
        }

        .mobile-menu.show {
            display: block;
        }

        .mobile-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 20px;
            color: var(--texto);
            border-bottom: 1px solid #F1F1F1;
            font-size: 14px;
            font-weight: 600;
        }

        .mobile-menu a i {
            width: 20px;
            color: var(--rojo);
        }

        .mobile-menu a:hover {
            background: var(--rojo-suave);
            color: var(--rojo);
        }

        .mobile-menu .mobile-highlight {
            background: #FFF0F0;
            color: var(--rojo);
            font-weight: 700;
        }

        .mobile-user {
            padding: 13px 20px;
            background: #F9FAFB;
            border-bottom: 1px solid var(--borde);
            font-size: 13px;
            font-weight: 700;
            color: var(--rojo);
        }

        /* TABLET */
        @media (max-width: 1100px) {
            .nav-menu {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .main-nav {
                margin-left: auto;
            }
        }

        /* CELULAR */
        @media (max-width: 700px) {
            .top-bar {
                display: none;
            }

            .header-container {
                min-height: 65px;
                padding: 8px 12px;
                gap: 8px;
            }

            .brand {
                min-width: 0;
                margin-right: auto;
                gap: 8px;
            }

            .brand-img {
                height: 38px;
                max-width: 75px;
            }

            .brand-name {
                font-size: 12px;
                max-width: 165px;
            }

            .brand-subtitle {
                display: none;
            }

            .auth-buttons .btn {
                display: none;
            }

            .user-dropdown {
                display: none;
            }

            .lang-switch {
                margin-right: 3px;
            }
        }

        @media (max-width: 430px) {
            .brand-name {
                font-size: 11px;
                max-width: 125px;
            }

            .brand-img {
                height: 34px;
                max-width: 60px;
            }

            .lang-btn {
                padding: 5px 6px;
            }

            .header-container {
                gap: 6px;
            }
        }
    </style>
</head>

<body>

<header class="main-header">

    <!-- BARRA SUPERIOR -->
    <div class="top-bar">
        <div class="top-container">
            <div class="top-left">
                <span>
                    <i class="fa-solid fa-location-dot"></i>
                    Cusco, Perú
                </span>

                <span>
                    <i class="fa-solid fa-briefcase"></i>
                    Oportunidades laborales
                </span>
            </div>

            <div class="top-right">
                <i class="fa-solid fa-circle-check"></i>
                Portal Regional
            </div>
        </div>
    </div>

    <!-- CABECERA -->
    <div class="header-container">

        <!-- LOGO -->
        <a href="index.php" class="brand">
            <img
                src="imagenes/logo.jpg"
                alt="Emprende Cusco"
                class="brand-img"
            >

            <div>
                <div class="brand-name">
                    Emprende Cusco
                </div>

                <span class="brand-subtitle">
                    Empleo y emprendimiento
                </span>
            </div>
        </a>

        <!-- MENÚ ESCRITORIO -->
        <nav class="main-nav" aria-label="Navegación principal">
            <ul class="nav-menu">
                <li>
                    <a href="index.php" class="nav-link">
                        <i class="fa-solid fa-house"></i>
                        <?= htmlspecialchars($t_nav['inicio'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>

                <li>
                    <a href="ideas.php" class="nav-link">
                        <i class="fa-solid fa-lightbulb"></i>
                        <?= htmlspecialchars($t_nav['ideas'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>

                <li>
                    <a href="empleos.php" class="nav-link">
                        <i class="fa-solid fa-briefcase"></i>
                        <?= htmlspecialchars($t_nav['empleos'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- IDIOMA -->
        <div class="lang-switch">
            <a
                href="?lang=es"
                class="lang-btn <?= $lang === 'es' ? 'active' : '' ?>"
            >ES</a>

            <a
                href="?lang=qu"
                class="lang-btn <?= $lang === 'qu' ? 'active' : '' ?>"
            >QU</a>
        </div>

        <!-- SESIÓN DE ESCRITORIO -->
        <div class="auth-buttons">
            <?php if ($usuarioLogueado): ?>

                <div class="user-dropdown">
                    <button
                        type="button"
                        class="user-box"
                        id="userMenuBtn"
                        aria-expanded="false"
                        aria-controls="userMenu"
                    >
                        <span class="user-avatar"><?= $inicialUsuario ?></span>

                        <span class="user-name"><?= $nombreUsuarioSeguro ?></span>

                        <i class="fa-solid fa-chevron-down"
                           style="font-size:10px;color:var(--gris);"></i>
                    </button>

                    <div class="user-menu" id="userMenu">
                        <a href="panel.php">
                            <i class="fa-solid fa-house-user"></i>
                            Mi cuenta
                        </a>

                        <a href="perfil.php">
                            <i class="fa-solid fa-id-card"></i>
                            <?= htmlspecialchars($t_nav['perfil'], ENT_QUOTES, 'UTF-8') ?>
                        </a>

                        <a href="logout.php" class="logout-link">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <?= htmlspecialchars($t_nav['salir'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <a href="registro.php" class="btn btn-outline">
                    <i class="fa-solid fa-user-plus"></i>
                    <?= htmlspecialchars($t_nav['crear_cuenta'], ENT_QUOTES, 'UTF-8') ?>
                </a>

                <a href="login.php" class="btn btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <?= htmlspecialchars($t_nav['ingresar'], ENT_QUOTES, 'UTF-8') ?>
                </a>

            <?php endif; ?>
        </div>

        <!-- BOTÓN MÓVIL -->
        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-label="Abrir menú"
            aria-expanded="false"
            aria-controls="mobileMenu"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

    </div>

    <!-- MENÚ MÓVIL -->
    <div class="mobile-menu" id="mobileMenu">

        <?php if ($usuarioLogueado): ?>
            <div class="mobile-user">
                <i class="fa-solid fa-user-circle"></i>
                Bienvenido, <?= $nombreUsuarioSeguro ?>
            </div>
        <?php endif; ?>

        <a href="index.php">
            <i class="fa-solid fa-house"></i>
            <?= htmlspecialchars($t_nav['inicio'], ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="ideas.php">
            <i class="fa-solid fa-lightbulb"></i>
            <?= htmlspecialchars($t_nav['ideas'], ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="empleos.php">
            <i class="fa-solid fa-briefcase"></i>
            <?= htmlspecialchars($t_nav['empleos'], ENT_QUOTES, 'UTF-8') ?>
        </a>

        <?php if ($usuarioLogueado): ?>

            <a href="panel.php">
                <i class="fa-solid fa-house-user"></i>
                Mi cuenta
            </a>

            <a href="perfil.php">
                <i class="fa-solid fa-id-card"></i>
                <?= htmlspecialchars($t_nav['perfil'], ENT_QUOTES, 'UTF-8') ?>
            </a>

            <a href="logout.php" class="mobile-highlight">
                <i class="fa-solid fa-right-from-bracket"></i>
                <?= htmlspecialchars($t_nav['salir'], ENT_QUOTES, 'UTF-8') ?>
            </a>

        <?php else: ?>

            <a href="registro.php">
                <i class="fa-solid fa-user-plus"></i>
                <?= htmlspecialchars($t_nav['crear_cuenta'], ENT_QUOTES, 'UTF-8') ?>
            </a>

            <a href="login.php" class="mobile-highlight">
                <i class="fa-solid fa-right-to-bracket"></i>
                <?= htmlspecialchars($t_nav['ingresar'], ENT_QUOTES, 'UTF-8') ?>
            </a>

        <?php endif; ?>

    </div>
</header>

<script>
(function () {
    const mobileButton = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileButton && mobileMenu) {
        mobileButton.addEventListener('click', function () {
            const abierto = mobileMenu.classList.toggle('show');

            mobileButton.setAttribute(
                'aria-expanded',
                abierto ? 'true' : 'false'
            );

            mobileButton.innerHTML = abierto
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';
        });
    }

    const userButton = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    if (userButton && userMenu) {
        userButton.addEventListener('click', function (event) {
            event.stopPropagation();

            const abierto = userMenu.classList.toggle('show');

            userButton.setAttribute(
                'aria-expanded',
                abierto ? 'true' : 'false'
            );
        });

        document.addEventListener('click', function (event) {
            if (!userMenu.contains(event.target) &&
                !userButton.contains(event.target)) {
                userMenu.classList.remove('show');
                userButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
</script>

