<?php
// Baja logica y reactivacion de documentos (solo administrador, solo por POST con token CSRF).
// El documento no se borra: queda registrado y su PDF se conserva, pero deja de poder abrirse.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/DocumentoDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documentos_listar.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';
$resultado = 'error';

if ($id > 0 && in_array($accion, ['baja', 'reactivar'], true)) {
    try {
        $dao = new DocumentoDAO();
        if ($dao->obtenerPorId($id) !== null) {
            $usuario = Auth::usuarioActual();
            $dao->cambiarEstado($id, $accion === 'reactivar', (int) $usuario['identificador']);
            $resultado = $accion === 'reactivar' ? 'reactivado' : 'baja';
        }
    } catch (PDOException $ex) {
        $resultado = 'error';
    }
}

header('Location: documentos_listar.php?msg=' . $resultado);
exit;
