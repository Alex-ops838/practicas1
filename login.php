
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

$error = '';
$emailIngresado = trim($_POST['email'] ?? '');

// Detectar la conexión configurada en conexion.php
$db = null;

if (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $db = $conexion;
}

if (!$db) {
    die('Error: revisa la conexión en config/conexion.php.');
}

$db->set_charset('utf8mb4');

// Si ya inició sesión, ir al panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower($emailIngresado, 'UTF-8');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Completa el correo y la contraseña.';
    } else {
        /*
         * Detectar las columnas reales de la tabla usuarios.
         */
        $columnas = [];
        $consultaColumnas = $db->query("SHOW COLUMNS FROM usuarios");

        if ($consultaColumnas) {
            while ($fila = $consultaColumnas->fetch_assoc()) {
                $columnas[] = $fila['Field'];
            }
            $consultaColumnas->free();
        }

        $buscarColumna = function (array $opciones) use ($columnas) {
            foreach ($opciones as $opcion) {
                if (in_array($opcion, $columnas, true)) {
                    return $opcion;
                }
            }
            return null;
        };

        $colEmail = $buscarColumna(['correo', 'email']);
        $colPassword = $buscarColumna([
            'password',
            'contrasena_hash',
            'contrasena'
        ]);
        $colId = $buscarColumna(['id', 'usuario_id']);

        if (!$colEmail || !$colPassword || !$colId) {
            $error = 'La tabla usuarios no tiene las columnas necesarias. Revisa su estructura en phpMyAdmin.';
        } else {
            // Los nombres se eligen exclusivamente de columnas detectadas.
            $sql = "SELECT * FROM usuarios
                    WHERE `$colEmail` = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                $error = 'No se pudo preparar la consulta de inicio de sesión.';
            } else {
                $stmt->bind_param('s', $email);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $usuario = $resultado ? $resultado->fetch_assoc() : null;

                if (!$usuario) {
                    $error = 'No se encontró una cuenta con ese correo. Comprueba que el registro se guardó en esta base de datos.';
                } else {
                    $hashGuardado = (string)($usuario[$colPassword] ?? '');

                    if ($hashGuardado === '' ||
                        !password_verify($password, $hashGuardado)) {
                        $error = 'El correo o la contraseña son incorrectos.';
                    } else {
                        session_regenerate_id(true);

                        $_SESSION['usuario_id'] = $usuario[$colId];

                        $nombre = trim(
                            (string)($usuario['nombres'] ?? '') . ' ' .
                            (string)($usuario['apellidos'] ?? '')
                        );

                        if ($nombre === '') {
                            $nombre = trim((string)($usuario['nombre'] ?? ''));
                        }

                        if ($nombre === '') {
                            $nombre = $email;
                        }

                        $_SESSION['usuario_nombre'] = $nombre;
                        $_SESSION['usuario_email'] = $usuario[$colEmail];
                        $_SESSION['usuario_rol'] = $usuario['rol'] ?? 'usuario';

                        $stmt->close();

                        header('Location: panel.php');
                        exit;
                    }
                }

                $stmt->close();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container section"
      style="max-width:500px; margin:3rem auto;">

    <div style="
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        padding:2.5rem;
        box-shadow:0 10px 25px rgba(0,0,0,.05);
    ">
        <div class="text-center" style="margin-bottom:2rem;">
            <span class="badge-pill">
                <i class="fa-solid fa-right-to-bracket"></i>
                BIENVENIDO DE NUEVO
            </span>

            <h2>Iniciar sesión</h2>

            <p style="color:#64748b;">
                Ingresa a tu cuenta de Emprende Cusco
            </p>
        </div>

        <?php if ($error !== ''): ?>
            <div role="alert" style="
                background:#fee2e2;
                color:#991b1b;
                padding:1rem;
                border-radius:8px;
                margin-bottom:1.5rem;
            ">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST"
              style="display:flex; flex-direction:column; gap:1.2rem;">

            <div>
                <label for="email" style="display:block; font-weight:700; margin-bottom:.4rem;">
                    Correo electrónico
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                    value="<?= htmlspecialchars($emailIngresado, ENT_QUOTES, 'UTF-8') ?>"
                    style="box-sizing:border-box; width:100%; padding:.75rem; border:1px solid #cbd5e1; border-radius:8px;"
                >
            </div>

            <div>
                <label for="password" style="display:block; font-weight:700; margin-bottom:.4rem;">
                    Contraseña
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    style="box-sizing:border-box; width:100%; padding:.75rem; border:1px solid #cbd5e1; border-radius:8px;"
                >
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width:100%; justify-content:center; padding:.8rem;">
                <i class="fa-solid fa-right-to-bracket"></i>
                Ingresar
            </button>
        </form>

        <div style="text-align:center; margin-top:1.5rem; color:#64748b;">
            ¿Aún no tienes una cuenta?
            <a href="registro.php"
               style="color:#2563eb; font-weight:700; text-decoration:none;">
                Regístrate aquí
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
