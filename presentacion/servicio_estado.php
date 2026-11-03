<?php
// Baja logica y reactivacion de servicios (solo administrador, solo por POST con token CSRF).
// Los documentos y encuestas del servicio no se tocan: dejan de mostrarse mientras este dado de baja.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servicios.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';
$resultado = 'error';

if ($id > 0 && in_array($accion, ['baja', 'reactivar'], true)) {
    try {
        $dao = new ServicioDAO();
        if ($dao->obtenerPorId($id) !== null) {
            $dao->cambiarEstado($id, $accion === 'reactivar');
            $resultado = $accion === 'reactivar' ? 'reactivado' : 'baja';
        }
    } catch (PDOException $ex) {
        $resultado = 'error';
    }
}

header('Location: servicios.php?msg=' . $resultado);
exit;
