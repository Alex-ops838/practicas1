```php
<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi cuenta | Emprende Cusco</title>
<style>
body{font-family:Arial;margin:0;background:#f4f4f4;color:#333}
header{background:#8B0000;color:white;padding:18px}
nav{background:#5C0000;padding:14px}
nav a{color:white;text-decoration:none;margin:0 10px}
main{max-width:800px;margin:35px auto;padding:25px;background:white;
border-radius:10px}
.boton{display:inline-block;background:#8B0000;color:white;
padding:10px 15px;text-decoration:none;border-radius:6px}
</style>
</head>
<body>

<header>
<h2>Emprende Cusco</h2>
<p>Mi cuenta de usuario</p>
</header>

<nav>
<a href="index.php">Inicio</a>
<a href="ideas.php">Ideas de Negocio</a>
<a href="empleos.php">Empleos</a>
<a href="cuenta.php">Mi cuenta</a>
<a href="cerrar_sesion.php">Cerrar sesión</a>
</nav>

<main>
<h2>¡Bienvenido, <?= e($_SESSION["usuario_nombre"]) ?>!</h2>
<p>Has ingresado correctamente a tu cuenta.</p>

<p><strong>Correo:</strong>
<?= e($_SESSION["usuario_email"]) ?></p>

<p><strong>Tipo de usuario:</strong>
<?= e($_SESSION["usuario_rol"]) ?></p>

<a class="boton" href="ideas.php">Ver ideas de negocio</a>
<a class="boton" href="empleos.php">Ver empleos</a>
</main>

</body>
</html>
```