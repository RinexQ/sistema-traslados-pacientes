<?php
// Aprobar o rechazar una solicitud. Solo el administrador, y solo por POST.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/SolicitudDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: solicitudes.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$estado = $_POST['estado'] ?? '';
$resultado = 'error';

if ($id > 0 && in_array($estado, ['aprobada', 'rechazada'])) {
    try {
        $dao = new SolicitudDAO();
        $dao->cambiarEstado($id, $estado);
        $resultado = 'estado';
    } catch (PDOException $ex) {
        $resultado = 'error';
    }
}

header('Location: solicitudes.php?msg=' . $resultado);
exit;
