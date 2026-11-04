<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Filtros.php';
require_once __DIR__ . '/../negocio/Paginacion.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

// Todas las respuestas de texto de una pregunta (sin datos de quien las escribio)
Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$idPregunta = isset($_GET['pregunta']) ? (int) $_GET['pregunta'] : 0;
$pregunta = $idPregunta > 0 ? $dao->obtenerPregunta($idPregunta) : null;
$encuesta = $pregunta !== null ? $dao->obtener((int) $pregunta['id_encuesta']) : null;
if ($encuesta === null || $pregunta['tipo'] !== 'texto') {
    header('Location: encuestas.php?msg=error');
    exit;
}

$desde = Filtros::fecha($_GET['desde'] ?? '');
$hasta = Filtros::fecha($_GET['hasta'] ?? '');

$paginacion = new Paginacion($dao->contarTextos($idPregunta, $desde, $hasta), Paginacion::paginaPedida(), 15);
$lista = $dao->textos($idPregunta, $desde, $hasta, $paginacion->limite(), $paginacion->desplazamiento());

$tituloPagina = 'Respuestas de texto';
$paginaActiva = 'encuestas';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Respuestas de texto</h2>
                <p class="text-muted mb-1">Encuesta: <strong><?= e($encuesta['titulo']) ?></strong></p>
                <p class="mb-0">Pregunta: <strong><?= e($pregunta['texto']) ?></strong></p>
            </div>

            <form method="get" class="card card-body d-block shadow-sm mt-3">
                <input type="hidden" name="pregunta" value="<?= (int) $idPregunta ?>">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                        <a href="encuesta_textos.php?pregunta=<?= (int) $idPregunta ?>" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm mt-3">
                <div class="list-group list-group-flush">
                    <?php if (count($lista) === 0): ?>
                        <div class="list-group-item text-muted">No hay respuestas para mostrar.</div>
                    <?php endif; ?>
                    <?php foreach ($lista as $t): ?>
                        <div class="list-group-item">
                            <?= e($t['texto']) ?>
                            <div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($t['fecha']))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php require __DIR__ . '/paginacion.php'; ?>

            <div class="mt-3">
                <a href="encuesta_resultados.php?id=<?= (int) $encuesta['id_encuesta'] ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a los resultados
                </a>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
