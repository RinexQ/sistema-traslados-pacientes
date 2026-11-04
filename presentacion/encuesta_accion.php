<?php
// Publicar, cerrar, duplicar o crear una nueva version de una encuesta
// (solo administrador, solo por POST con token CSRF). Cada accion queda registrada.
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
$usuario = Auth::usuarioActual();
$destino = 'encuestas.php?msg=error';

try {
    $dao = new EncuestaDAO();
    $encuesta = $id > 0 ? $dao->obtener($id) : null;

    if ($encuesta === null) {
        // destino de error por defecto

    } elseif ($accion === 'publicar' && $encuesta['estado'] === 'borrador') {
        // para publicar tiene que tener preguntas y las de opciones, al menos 2 opciones
        if (count($dao->erroresDePublicacion($id)) > 0) {
            $destino = 'encuestas.php?msg=sinpreguntas';
        } else {
            $dao->publicar($id);
            Auditoria::registrar('encuesta_publicacion', 'encuesta', $id,
                'Encuesta publicada: «' . $encuesta['titulo'] . '» (versión ' . $encuesta['version'] . ')');
            $destino = 'encuestas.php?msg=publicada';
        }

    } elseif ($accion === 'cerrar' && $encuesta['estado'] === 'publicada') {
        $dao->cerrar($id);
        Auditoria::registrar('encuesta_cierre', 'encuesta', $id,
            'Encuesta cerrada: «' . $encuesta['titulo'] . '» (versión ' . $encuesta['version'] . ')');
        $destino = 'encuestas.php?msg=cerrada';

    } elseif ($accion === 'duplicar') {
        $nuevo = $dao->duplicar($id, (int) $usuario['identificador'], false);
        Auditoria::registrar('encuesta_duplicacion', 'encuesta', $nuevo, 'Copia creada a partir de «' . $encuesta['titulo'] . '»');
        $destino = 'encuesta_preguntas.php?id=' . $nuevo . '&msg=duplicada';

    } elseif ($accion === 'version' && $encuesta['estado'] !== 'borrador') {
        $nuevo = $dao->duplicar($id, (int) $usuario['identificador'], true);
        Auditoria::registrar('encuesta_version', 'encuesta', $nuevo,
            'Nueva versión de «' . $encuesta['titulo'] . '» creada a partir de la versión ' . $encuesta['version']);
        $destino = 'encuesta_preguntas.php?id=' . $nuevo . '&msg=version';
    }
} catch (Throwable $ex) {
    $destino = 'encuestas.php?msg=error';
}

header('Location: ' . $destino);
exit;
