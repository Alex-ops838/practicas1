
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$password = "";
$database = "emprende_cusco_db";

try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión: " . $e->getMessage());
    exit("No se pudo conectar con la base de datos. Revisa config/conexion.php.");
}
