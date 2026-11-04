<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

// Alta y edicion de una pregunta (y de sus opciones): solo el administrador
Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$idPregunta = isset($_GET['id']) ? (int) $_GET['id'] : null;
$esEdicion = $idPregunta !== null;

$texto = '';
$tipo = 'unica';
$obligatoria = true;
$opcionesTexto = '';
$errores = [];

if ($esEdicion) {
    $pregunta = $dao->obtenerPregunta($idPregunta);
    if ($pregunta === null) {
        header('Location: encuestas.php?msg=error');
        exit;
    }
    $idEncuesta = (int) $pregunta['id_encuesta'];
    $texto = $pregunta['texto'];
    $tipo = $pregunta['tipo'];
    $obligatoria = (int) $pregunta['obligatoria'] === 1;
    $opcionesTexto = implode("\n", array_column($pregunta['opciones'], 'texto'));
} else {
    $idEncuesta = (int) ($_GET['encuesta'] ?? 0);
}

$encuesta = $idEncuesta > 0 ? $dao->obtener($idEncuesta) : null;
if ($encuesta === null) {
    header('Location: encuestas.php?msg=error');
    exit;
}
// una encuesta con respuestas o cerrada no se modifica
if (!$dao->esEditable($encuesta)) {
    header('Location: encuestas.php?msg=noeditable');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();
    $texto = trim($_POST['texto'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $obligatoria = isset($_POST['obligatoria']);
    $opcionesTexto = (string) ($_POST['opciones'] ?? '');

    [$errores, $opciones] = Encuesta::validarPregunta($texto, $tipo, $opcionesTexto);

    if (count($errores) === 0) {
        try {
            if ($esEdicion) {
                $dao->actualizarPregunta($idPregunta, $texto, $tipo, $obligatoria, $opciones);
                Auditoria::registrar('pregunta_edicion', 'pregunta', $idPregunta, 'Pregunta modificada en «' . $encuesta['titulo'] . '»: ' . $texto);
                header('Location: encuesta_preguntas.php?id=' . $idEncuesta . '&msg=pregunta_editada');
            } else {
                $nueva = $dao->crearPregunta($idEncuesta, $texto, $tipo, $obligatoria, $opciones);
                Auditoria::registrar('pregunta_alta', 'pregunta', $nueva, 'Pregunta agregada a «' . $encuesta['titulo'] . '»: ' . $texto);
                header('Location: encuesta_preguntas.php?id=' . $idEncuesta . '&msg=pregunta_creada');
            }
            exit;
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo guardar la pregunta. Intentá de nuevo.';
        }
    }
}

$tituloPagina = $esEdicion ? 'Editar pregunta' : 'Nueva pregunta';
$paginaActiva = 'encuestas';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2><?= $esEdicion ? 'Editar pregunta' : 'Nueva pregunta' ?></h2>
                <p class="text-muted">Encuesta: <strong><?= e($encuesta['titulo']) ?></strong></p>
            </div>

            <?php if (count($errores) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm" style="max-width: 650px;">
                <div class="card-body d-block">
                    <form method="post" id="form-pregunta" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="texto">Pregunta</label>
                            <input type="text" id="texto" name="texto" class="form-control" maxlength="255"
                                   value="<?= e($texto) ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="tipo">Tipo de pregunta</label>
                            <select id="tipo" name="tipo" class="form-select">
                                <?php foreach (Encuesta::TIPOS as $valor => $nombre): ?>
                                    <option value="<?= e($valor) ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <strong>Opción única:</strong> se elige una. <strong>Opciones múltiples:</strong> se eligen varias.
                                <strong>Valoración:</strong> del 1 al 5. <strong>Texto breve:</strong> hasta 500 caracteres.
                            </div>
                        </div>
                        <div class="mb-3" id="bloque-opciones">
                            <label class="form-label" for="opciones">Opciones (una por línea)</label>
                            <textarea id="opciones" name="opciones" class="form-control" rows="5"><?= e($opcionesTexto) ?></textarea>
                            <div class="form-text">Mínimo 2 y máximo <?= (int) Encuesta::MAXIMO_OPCIONES ?>. El orden de las líneas es el orden en que se muestran.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="obligatoria" name="obligatoria" <?= $obligatoria ? 'checked' : '' ?>>
                            <label class="form-check-label" for="obligatoria">Pregunta obligatoria</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Agregar pregunta' ?></button>
                        <a href="encuesta_preguntas.php?id=<?= (int) $idEncuesta ?>" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
