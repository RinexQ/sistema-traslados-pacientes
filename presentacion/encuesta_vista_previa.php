<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

// Vista previa: muestra la encuesta como la va a ver el publico, sin guardar nada
Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$encuesta = $id > 0 ? $dao->obtener($id) : null;
if ($encuesta === null) {
    header('Location: encuestas.php?msg=error');
    exit;
}
$preguntas = $dao->preguntas($id);
$deshabilitado = true;
$valores = [];

$tituloPagina = 'Vista previa';
$paginaActiva = 'encuestas';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Vista previa</h2>
                <div class="alert alert-info">
                    <i class="bi bi-eye"></i> Así se ve la encuesta en el portal. Es solo una vista previa: no se guarda ninguna respuesta.
                    Estado actual: <strong><?= e(Encuesta::ESTADOS[$encuesta['estado']] ?? $encuesta['estado']) ?></strong>.
                </div>
            </div>

            <div class="card shadow-sm" style="max-width: 700px;">
                <div class="card-body d-block">
                    <h3 class="h5"><?= e($encuesta['titulo']) ?></h3>
                    <?php if (!empty($encuesta['descripcion'])): ?>
                        <p><?= e($encuesta['descripcion']) ?></p>
                    <?php endif; ?>
                    <p class="text-muted small">Esta encuesta es anónima. Las preguntas con * son obligatorias.</p>

                    <?php if (count($preguntas) === 0): ?>
                        <p class="text-muted">La encuesta todavía no tiene preguntas.</p>
                    <?php endif; ?>

                    <?php require __DIR__ . '/encuesta_campos.php'; ?>

                    <button type="button" class="btn btn-primary" disabled>Enviar respuestas</button>
                </div>
            </div>

            <div class="mt-3">
                <a href="encuesta_preguntas.php?id=<?= (int) $id ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a las preguntas</a>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
