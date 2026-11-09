<?php
session_start();

require_once __DIR__ . '/config/conexion.php';

/* =========================================================
   VERIFICAR CONEXIÓN
========================================================= */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Error: no se pudo conectar con la base de datos.");
}

$conn->set_charset("utf8mb4");

/* =========================================================
   FUNCIONES
========================================================= */
function e($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   VERIFICAR SESIÓN
========================================================= */
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$mensaje = '';
$tipo_mensaje = '';

/* =========================================================
   PROCESAR SUBIDA DE CV
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {

        $archivo = $_FILES['cv'];
        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
        $tipo = $archivo['type'];
        $tmp = $archivo['tmp_name'];

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        $extensiones_permitidas = ['pdf', 'doc', 'docx'];
        $tamano_maximo = 5 * 1024 * 1024; // 5 MB

        if (!in_array($extension, $extensiones_permitidas)) {
            $mensaje = 'Solo se permiten archivos PDF, DOC o DOCX.';
            $tipo_mensaje = 'error';
        } elseif ($tamano > $tamano_maximo) {
            $mensaje = 'El archivo no debe superar los 5 MB.';
            $tipo_mensaje = 'error';
        } else {

            $directorio = __DIR__ . '/uploads/cv/';

            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }

            $nombre_nuevo = 'cv_' . $usuario_id . '_' . time() . '.' . $extension;
            $ruta_destino = $directorio . $nombre_nuevo;

            if (move_uploaded_file($tmp, $ruta_destino)) {

                $ruta_bd = 'uploads/cv/' . $nombre_nuevo;

                // Eliminar CV anterior si existe
                $sql_anterior = "SELECT cv FROM usuarios WHERE id = ? LIMIT 1";
                $stmt_ant = $conn->prepare($sql_anterior);
                if ($stmt_ant) {
                    $stmt_ant->bind_param('i', $usuario_id);
                    $stmt_ant->execute();
                    $res_ant = $stmt_ant->get_result();
                    if ($fila = $res_ant->fetch_assoc()) {
                        if (!empty($fila['cv']) && file_exists(__DIR__ . '/' . $fila['cv'])) {
                            unlink(__DIR__ . '/' . $fila['cv']);
                        }
                    }
                    $stmt_ant->close();
                }

                // Actualizar en la base de datos
                $sql = "UPDATE usuarios SET cv = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param('si', $ruta_bd, $usuario_id);

                    if ($stmt->execute()) {
                        $mensaje = '¡Tu CV se subió correctamente!';
                        $tipo_mensaje = 'exito';
                    } else {
                        $mensaje = 'Error al guardar en la base de datos.';
                        $tipo_mensaje = 'error';
                    }
                    $stmt->close();
                } else {
                    $mensaje = 'Error al preparar la consulta.';
                    $tipo_mensaje = 'error';
                }

            } else {
                $mensaje = 'Error al mover el archivo. Verifica permisos.';
                $tipo_mensaje = 'error';
            }
        }

    } else {
        $mensaje = 'Debes seleccionar un archivo.';
        $tipo_mensaje = 'error';
    }
}

/* =========================================================
   OBTENER CV ACTUAL
========================================================= */
$cv_actual = '';
$sql_cv = "SELECT cv FROM usuarios WHERE id = ? LIMIT 1";
$stmt_cv = $conn->prepare($sql_cv);

if ($stmt_cv) {
    $stmt_cv->bind_param('i', $usuario_id);
    $stmt_cv->execute();
    $res_cv = $stmt_cv->get_result();
    if ($fila_cv = $res_cv->fetch_assoc()) {
        $cv_actual = $fila_cv['cv'] ?? '';
    }
    $stmt_cv->close();
}

/* =========================================================
   HEADER
========================================================= */
include __DIR__ . '/includes/header.php';
?>

<style>
.cv-page {
    width: 100%;
    background: #f5f6f8;
    padding: 40px 5% 60px;
    min-height: 70vh;
}

.cv-contenedor {
    width: 700px;
    max-width: 100%;
    margin: auto;
}

.cv-card {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,.05);
}

.cv-card h1 {
    margin: 0 0 6px;
    font-size: 26px;
    color: #252525;
}

.cv-card > p {
    margin: 0 0 22px;
    color: #777;
    font-size: 13px;
}

.cv-alerta {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 18px;
    font-weight: 600;
}

.cv-alerta.exito {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.cv-alerta.error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.cv-actual {
    background: #f7f7f8;
    border: 1px solid #eee;
    border-radius: 9px;
    padding: 14px 16px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.cv-actual-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #444;
}

.cv-actual-info i {
    color: #a90000;
    font-size: 20px;
}

.cv-actual a {
    background: #a90000;
    color: white;
    text-decoration: none;
    padding: 8px 13px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 800;
}

.cv-actual a:hover {
    background: #790000;
}

.cv-form label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #555;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.cv-archivo {
    width: 100%;
    padding: 14px;
    border: 2px dashed #ddd;
    border-radius: 10px;
    background: #fafafa;
    font-size: 13px;
    cursor: pointer;
    transition: .2s;
}

.cv-archivo:hover {
    border-color: #a90000;
    background: #fff8f8;
}

.cv-ayuda {
    font-size: 11px;
    color: #888;
    margin: 8px 0 20px;
}

.cv-btn {
    background: #a90000;
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.cv-btn:hover {
    background: #790000;
}

.cv-volver {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #666;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    margin-top: 20px;
}

.cv-volver:hover {
    color: #a90000;
}
</style>

<div class="cv-page">
    <div class="cv-contenedor">

        <div class="cv-card">

            <h1>
                <i class="fas fa-file-arrow-up" style="color:#a90000;"></i>
                Subir mi CV
            </h1>

            <p>
                Carga tu currículum en formato PDF, DOC o DOCX (máximo 5 MB).
            </p>

            <?php if ($mensaje !== ''): ?>
                <div class="cv-alerta <?= e($tipo_mensaje) ?>">
                    <?= e($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($cv_actual !== ''): ?>
                <div class="cv-actual">
                    <div class="cv-actual-info">
                        <i class="fas fa-file-pdf"></i>
                        <div>
                            <strong>CV actual:</strong><br>
                            <small><?= e(basename($cv_actual)) ?></small>
                        </div>
                    </div>
                    <a href="<?= e($cv_actual) ?>" target="_blank">
                        <i class="fas fa-eye"></i> Ver CV
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="cv-form">

                <label for="cv">Selecciona tu archivo</label>

                <input
                    type="file"
                    name="cv"
                    id="cv"
                    class="cv-archivo"
                    accept=".pdf,.doc,.docx"
                    required
                >

                <div class="cv-ayuda">
                    <i class="fas fa-circle-info"></i>
                    Formatos permitidos: PDF, DOC, DOCX. Tamaño máximo: 5 MB.
                </div>

                <button type="submit" class="cv-btn">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Subir CV
                </button>

            </form>

            <a href="empleos.php" class="cv-volver">
                <i class="fas fa-arrow-left"></i>
                Volver a la bolsa de empleo
            </a>

        </div>

    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>