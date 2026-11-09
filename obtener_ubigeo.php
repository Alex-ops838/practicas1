<?php
// obtener_ubigeo.php
header('Content-Type: application/json; charset=utf-8');
require_once 'config/conexion.php';

$tipo = trim($_GET['tipo'] ?? '');
$departamento = trim($_GET['departamento'] ?? '');
$provincia = trim($_GET['provincia'] ?? '');

$valores = [];

if ($conn instanceof mysqli) {
    if ($tipo === 'provincias' && !empty($departamento)) {
        $stmt = $conn->prepare("SELECT DISTINCT provincia FROM ubigeo_peru WHERE departamento = ? AND provincia <> '' ORDER BY provincia ASC");
        if ($stmt) {
            $stmt->bind_param('s', $departamento);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $valores[] = $row['provincia'];
            }
            $stmt->close();
        }
    } elseif ($tipo === 'distritos' && !empty($departamento) && !empty($provincia)) {
        $stmt = $conn->prepare("SELECT DISTINCT distrito FROM ubigeo_peru WHERE departamento = ? AND provincia = ? AND distrito <> '' ORDER BY distrito ASC");
        if ($stmt) {
            $stmt->bind_param('ss', $departamento, $provincia);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $valores[] = $row['distrito'];
            }
            $stmt->close();
        }
    }
}

echo json_encode($valores, JSON_UNESCAPED_UNICODE);
exit;