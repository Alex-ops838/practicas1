<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/conexion.php';
if (empty($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }
$pageTitle='Mi Perfil | Emprende Cusco';
$id=(int)$_SESSION['usuario_id'];
$datos=['nombre'=>'','email'=>'','celular'=>'','edad'=>'','rol'=>'Usuario','provincia'=>'','colegio_nombre'=>''];
$stmt=$conn->prepare("SELECT u.nombre,u.email,u.celular,u.edad,u.rol,c.nombre AS colegio_nombre,c.provincia FROM usuarios u LEFT JOIN colegios c ON u.colegio_id=c.id WHERE u.id=? LIMIT 1");
if($stmt){$stmt->bind_param('i',$id);$stmt->execute();$r=$stmt->get_result()->fetch_assoc();if($r)$datos=array_merge($datos,$r);$stmt->close();}
function pe($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
include __DIR__.'/includes/header.php';
?>
<main class="container section" style="max-width:900px;margin:40px auto;">
  <div style="background:linear-gradient(135deg,#650000,#8B0000);color:#fff;border-radius:22px;padding:30px;margin-bottom:22px;">
    <span style="font-size:12px;font-weight:800;opacity:.85;">CUENTA PERSONAL</span>
    <h1 style="margin:8px 0;font-size:32px;">Mi perfil</h1>
    <p style="opacity:.9;">Gestiona la información de tu cuenta en Emprende Cusco.</p>
  </div>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:24px;display:grid;grid-template-columns:repeat(2,1fr);gap:15px;box-shadow:0 10px 30px rgba(0,0,0,.06);">
    <?php foreach ([['Nombre',$datos['nombre']],['Correo',$datos['email']],['Celular',$datos['celular']],['Edad',$datos['edad']],['Rol',$datos['rol']],['Provincia',$datos['provincia']],['Institución educativa',$datos['colegio_nombre']]] as $item): ?>
      <div style="background:#fafafa;border-radius:12px;padding:14px;"><small style="display:block;color:#777;font-weight:700;margin-bottom:4px;"><?=pe($item[0])?></small><strong><?=pe($item[1] ?: 'No registrado')?></strong></div>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:18px;"><a href="logout.php" class="btn btn-primary">Cerrar sesión</a></div>
</main>
<?php include __DIR__.'/includes/footer.php'; ?>
