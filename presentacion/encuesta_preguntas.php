<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

// Preguntas de una encuesta: agregar, editar, ordenar y eliminar (solo el administrador)
Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$encuesta = $id > 0 ? $dao->obtener($id) : null;
if ($encuesta === null) {
    header('Location: encuestas.php?msg=error');
    exit;
}

$preguntas = $dao->preguntas($id);
$envios = $dao->contarEnvios($id);
$editable = $dao->esEditable($encuesta);
$mensaje = $_GET['msg'] ?? '';

$mensajes = [
    'creada'          => 'Encuesta creada. Agregá las preguntas.',
    'editada'         => 'Los datos de la encuesta se actualizaron.',
    'duplicada'       => 'Esta es la copia: ya podés modificarla.',
    'version'         => 'Esta es la nueva versión: ya podés modificarla y publicarla.',
    'pregunta_creada' => 'La pregunta se agregó.',
    'pregunta_editada' => 'La pregunta se actualizó.',
    'pregunta_baja'   => 'La pregunta se eliminó.',
    'orden'           => 'Se cambió el orden de las preguntas.',
];
$coloresEstado = ['borrador' => 'bg-secondary', 'publicada' => 'bg-success', 'cerrada' => 'bg-dark'];

$tituloPagina = 'Preguntas de la encuesta';
$paginaActiva = 'encuestas';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4 d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                    <h2 class="mb-1"><?= e($encuesta['titulo']) ?></h2>
                    <span class="badge <?= e($coloresEstado[$encuesta['estado']] ?? 'bg-secondary') ?>"><?= e(Encuesta::ESTADOS[$encuesta['estado']] ?? $encuesta['estado']) ?></span>
                    <span class="badge bg-info text-dark">Versión <?= (int) $encuesta['version'] ?></span>
                    <span class="badge bg-light text-dark border">
                        <?= $encuesta['servicio'] === null ? 'General' : e($encuesta['servicio']) ?>
                    </span>
                    <?php if (!empty($encuesta['descripcion'])): ?>
                        <p class="text-muted mt-2 mb-0"><?= e($encuesta['descripcion']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-nowrap">
                    <a href="encuestas.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                    <a href="encuesta_vista_previa.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> Vista previa</a>
                    <?php if ($envios > 0): ?>
                        <a href="encuesta_resultados.php?id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="bi bi-bar-chart"></i> Resultados</a>
                    <?php endif; ?>
                    <?php if ($editable): ?>
                        <a href="encuesta_form.php?id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Editar datos</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($mensajes[$mensaje])): ?>
                <div class="alert alert-success mt-3"><?= e($mensajes[$mensaje]) ?></div>
            <?php endif; ?>

            <?php if (!$editable): ?>
                <div class="alert alert-warning mt-3">
                    <?php if ($encuesta['estado'] === 'cerrada'): ?>
                        Esta encuesta está cerrada y no se puede modificar.
                    <?php else: ?>
                        Esta encuesta ya tiene <?= (int) $envios ?> respuesta(s) y no se puede modificar para no alterar los resultados.
                    <?php endif; ?>
                    Para hacer cambios, creá una <strong>nueva versión</strong> desde el listado de encuestas.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Preguntas (<?= count($preguntas) ?>)</h5>
                    <?php if ($editable): ?>
                        <a href="pregunta_form.php?encuesta=<?= $id ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg"></i> Agregar pregunta
                        </a>
                    <?php endif; ?>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (count($preguntas) === 0): ?>
                        <div class="list-group-item text-muted">Todavía no hay preguntas.</div>
                    <?php endif; ?>

                    <?php foreach ($preguntas as $i => $p): ?>
                        <?php $idP = (int) $p['id_pregunta']; ?>
                        <div class="list-group-item">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold"><?= (int) ($i + 1) ?>. <?= e($p['texto']) ?></div>
                                    <span class="badge bg-primary"><?= e(Encuesta::TIPOS[$p['tipo']] ?? $p['tipo']) ?></span>
                                    <?php if ((int) $p['obligatoria'] === 1): ?>
                                        <span class="badge bg-danger">Obligatoria</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Opcional</span>
                                    <?php endif; ?>
                                    <?php if (count($p['opciones']) > 0): ?>
                                        <ol class="small text-muted mb-0 mt-2">
                                            <?php foreach ($p['opciones'] as $o): ?>
                                                <li><?= e($o['texto']) ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                </div>

                                <?php if ($editable): ?>
                                <div class="text-nowrap">
                                    <?php foreach ([['subir', 'bi-arrow-up', $i === 0], ['bajar', 'bi-arrow-down', $i === count($preguntas) - 1]] as $mov): ?>
                                        <form method="post" action="pregunta_accion.php" class="d-inline">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= $idP ?>">
                                            <input type="hidden" name="accion" value="<?= e($mov[0]) ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" title="<?= $mov[0] === 'subir' ? 'Subir' : 'Bajar' ?>" <?= $mov[2] ? 'disabled' : '' ?>>
                                                <i class="bi <?= e($mov[1]) ?>"></i>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                    <a href="pregunta_form.php?id=<?= $idP ?>" class="btn btn-primary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="pregunta_accion.php" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar esta pregunta y sus opciones?');">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="id" value="<?= $idP ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
