<?php
// Baja logica y reactivacion de usuarios (solo administrador, solo por POST con token CSRF).
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';
$actual = Auth::usuarioActual();
$resultado = 'error';

if ($id > 0 && in_array($accion, ['baja', 'reactivar'], true)) {
    try {
        $dao = new UsuarioDAO();
        $usuario = $dao->obtenerPorId($id);

        if ($usuario === null) {
            $resultado = 'error';
        } elseif ($accion === 'baja' && $id === (int) $actual['identificador']) {
            $resultado = 'propio'; // nadie se da de baja a si mismo
        } elseif ($accion === 'baja' && $usuario['rol'] === 'administrador'
                  && (int) $usuario['activo'] === 1 && $dao->contarAdministradoresActivos() <= 1) {
            $resultado = 'ultimo'; // siempre tiene que quedar un administrador activo
        } else {
            $dao->cambiarEstado($id, $accion === 'reactivar');
            $resultado = $accion === 'reactivar' ? 'reactivado' : 'baja';
        }
    } catch (PDOException $ex) {
        $resultado = 'error';
    }
}

header('Location: usuarios.php?msg=' . $resultado);
exit;
