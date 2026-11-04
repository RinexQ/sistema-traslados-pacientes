<?php
// Subir, bajar o eliminar una pregunta (solo administrador, solo por POST con token CSRF).
// Solo se puede mientras la encuesta no tenga respuestas ni este cerrada.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

Auth::exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: encuestas.php');
    exit;
}
Csrf::exigir();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';
$destino = 'encuestas.php?msg=error';

try {
    $dao = new EncuestaDAO();
    $pregunta = $id > 0 ? $dao->obtenerPregunta($id) : null;
    $encuesta = $pregunta !== null ? $dao->obtener((int) $pregunta['id_encuesta']) : null;

    if ($encuesta === null || !in_array($accion, ['subir', 'bajar', 'eliminar'], true)) {
        // destino de error por defecto
    } elseif (!$dao->esEditable($encuesta)) {
        $destino = 'encuestas.php?msg=noeditable';
    } else {
        $base = 'encuesta_preguntas.php?id=' . (int) $encuesta['id_encuesta'];
        if ($accion === 'eliminar') {
            $dao->eliminarPregunta($id);
            Auditoria::registrar('pregunta_baja', 'pregunta', $id, 'Pregunta eliminada de «' . $encuesta['titulo'] . '»: ' . $pregunta['texto']);
            $destino = $base . '&msg=pregunta_baja';
        } else {
            $dao->moverPregunta($id, $accion);
            Auditoria::registrar('pregunta_orden', 'pregunta', $id, 'Se movió una pregunta de «' . $encuesta['titulo'] . '» (' . $accion . ')');
            $destino = $base . '&msg=orden';
        }
    }
} catch (PDOException $ex) {
    $destino = 'encuestas.php?msg=error';
}

header('Location: ' . $destino);
exit;
