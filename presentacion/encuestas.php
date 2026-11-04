<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../negocio/Filtros.php';
require_once __DIR__ . '/../negocio/Paginacion.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

// Constructor de encuestas y resultados: solo el administrador
Auth::exigirAdministrador();

$busqueda   = trim($_GET['q'] ?? '');
$idServicio = (int) ($_GET['servicio'] ?? 0);
$estado     = Filtros::opcion($_GET['estado'] ?? '', ['borrador', 'publicada', 'cerrada']);
$desde      = Filtros::fecha($_GET['desde'] ?? '');
$hasta      = Filtros::fecha($_GET['hasta'] ?? '');
$mensaje    = $_GET['msg'] ?? '';

$mensajes = [
    'creada'       => ['success', 'Encuesta creada. Ahora agregá las preguntas.'],
    'publicada'    => ['success', 'La encuesta se publicó. Ya recibe respuestas desde el portal.'],
    'cerrada'      => ['success', 'La encuesta se cerró: ya no recibe respuestas, pero se conservan todos sus resultados.'],
    'duplicada'    => ['success', 'Se creó una copia como borrador.'],
    'version'      => ['success', 'Se creó una nueva versión como borrador. Al publicarla, la versión anterior se cierra.'],
    'sinpreguntas' => ['warning', 'Para publicar, la encuesta necesita al menos una pregunta, y las de opciones al menos 2 opciones.'],
    'noeditable'   => ['warning', 'La encuesta ya tiene respuestas o está cerrada y no se puede modificar. Creá una nueva versión.'],
    'error'        => ['danger', 'No se pudo completar la operación.'],
];

$errorBD = '';
$encuestas = [];
$servicios = [];
$paginacion = new Paginacion(0, 1);
try {
    $dao = new EncuestaDAO();
    $servicios = (new ServicioDAO())->listarTodos();
    $paginacion = new Paginacion($dao->contarAdmin($busqueda, $idServicio, $estado), Paginacion::paginaPedida(), 10);
    $encuestas = $dao->listarAdmin($busqueda, $idServicio, $estado, $desde, $hasta,
        $paginacion->limite(), $paginacion->desplazamiento());
} catch (PDOException $ex) {
    $errorBD = 'No se pudo cargar el listado de encuestas.';
}

$coloresEstado = ['borrador' => 'bg-secondary', 'publicada' => 'bg-success', 'cerrada' => 'bg-dark'];

$tituloPagina = 'Encuestas';
$paginaActiva = 'encuestas';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Encuestas</h2>
                <p class="text-muted">
                    Armá encuestas con distintos tipos de pregunta, publicalas en el portal y mirá sus resultados.
                    Las respuestas son anónimas.
                </p>
            </div>

            <?php if (isset($mensajes[$mensaje])): ?>
                <div class="alert alert-<?= e($mensajes[$mensaje][0]) ?>"><?= e($mensajes[$mensaje][1]) ?></div>
            <?php endif; ?>
            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mb-3">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1" for="q">Buscar por título</label>
                        <input class="form-control" type="search" id="q" name="q" maxlength="200" value="<?= e($busqueda) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1" for="servicio">Servicio</label>
                        <select class="form-select" id="servicio" name="servicio">
                            <option value="0">Todos</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= (int) $s['id_servicio'] ?>" <?= (int) $s['id_servicio'] === $idServicio ? 'selected' : '' ?>>
                                    <?= e($s['nombre']) ?><?= (int) $s['activo'] === 0 ? ' (inactivo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1" for="estado">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach (Encuesta::ESTADOS as $valor => $nombre): ?>
                                <option value="<?= e($valor) ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="desde">Respuestas desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="hasta">Respuestas hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Buscar</button>
                        <a href="encuestas.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Encuestas</h5>
                    <a href="encuesta_form.php" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Nueva encuesta</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Encuesta</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th class="text-center">Preguntas</th>
                                <th class="text-center">Respuestas</th>
                                <th class="text-center">Promedio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($encuestas) === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay encuestas para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($encuestas as $en): ?>
                            <?php
                            $id = (int) $en['id_encuesta'];
                            $editable = $en['estado'] !== 'cerrada' && (int) $en['envios_total'] === 0;
                            $confirmaciones = [
                                'publicar'  => '¿Publicar esta encuesta? Desde ese momento recibirá respuestas del portal.',
                                'cerrar'    => '¿Cerrar esta encuesta? Dejará de recibir respuestas.',
                                'duplicar'  => '¿Crear una copia de esta encuesta como borrador?',
                                'version'   => '¿Crear una nueva versión? Se copia como borrador y, al publicarla, esta versión se cierra.',
                            ];
                            ?>
                            <tr>
                                <td>
                                    <?= e($en['titulo']) ?>
                                    <span class="badge bg-info text-dark">v<?= (int) $en['version'] ?></span>
                                </td>
                                <td>
                                    <?= $en['servicio'] === null ? '<span class="text-muted">General</span>' : e($en['servicio']) ?>
                                    <?php if ($en['servicio'] !== null && (int) $en['servicio_activo'] === 0): ?>
                                        <span class="badge bg-warning text-dark">Servicio inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= e($coloresEstado[$en['estado']] ?? 'bg-secondary') ?>"><?= e(Encuesta::ESTADOS[$en['estado']] ?? $en['estado']) ?></span></td>
                                <td class="text-center"><?= (int) $en['preguntas'] ?></td>
                                <td class="text-center"><?= (int) $en['envios'] ?></td>
                                <td class="text-center"><?= $en['promedio'] === null ? '-' : e(number_format((float) $en['promedio'], 2)) ?></td>
                                <td class="text-nowrap">
                                    <a href="encuesta_resultados.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm" title="Resultados"><i class="bi bi-bar-chart"></i></a>
                                    <a href="encuesta_vista_previa.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" title="Vista previa"><i class="bi bi-eye"></i></a>
                                    <?php if ($editable): ?>
                                        <a href="encuesta_preguntas.php?id=<?= $id ?>" class="btn btn-primary btn-sm" title="Editar preguntas"><i class="bi bi-pencil"></i></a>
                                    <?php else: ?>
                                        <a href="encuesta_preguntas.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" title="Ver preguntas"><i class="bi bi-list-ul"></i></a>
                                    <?php endif; ?>

                                    <?php
                                    // Acciones que cambian la encuesta: todas piden confirmacion
                                    $acciones = [];
                                    if ($en['estado'] === 'borrador') {
                                        $acciones[] = ['publicar', 'btn-success', 'bi-send', 'Publicar'];
                                    }
                                    if ($en['estado'] === 'publicada') {
                                        $acciones[] = ['cerrar', 'btn-danger', 'bi-lock', 'Cerrar'];
                                    }
                                    if ($en['estado'] !== 'borrador') {
                                        $acciones[] = ['version', 'btn-outline-primary', 'bi-layers', 'Nueva versión'];
                                    }
                                    $acciones[] = ['duplicar', 'btn-outline-secondary', 'bi-files', 'Duplicar'];
                                    ?>
                                    <?php foreach ($acciones as $a): ?>
                                        <form method="post" action="encuesta_accion.php" class="d-inline"
                                              onsubmit="return confirm(<?= e(json_encode($confirmaciones[$a[0]], JSON_UNESCAPED_UNICODE)) ?>);">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <input type="hidden" name="accion" value="<?= e($a[0]) ?>">
                                            <button type="submit" class="btn <?= e($a[1]) ?> btn-sm" title="<?= e($a[3]) ?>"><i class="bi <?= e($a[2]) ?>"></i></button>
                                        </form>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require __DIR__ . '/paginacion.php'; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
