<?php
// Marcar un vehiculo como roto o disponible. Solo administrador y por POST.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/TrasladoDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ambulancias.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$estado = $_POST['estado'] ?? '';
$resultado = 'error';

if ($id > 0 && in_array($estado, ['disponible', 'roto'])) {
    try {
        $dao = new TrasladoDAO();
        $dao->cambiarEstadoVehiculo($id, $estado);
        $resultado = 'vehiculo';
    } catch (PDOException $ex) {
        $resultado = 'error';
    }
}

header('Location: ambulancias.php?msg=' . $resultado);
exit;
