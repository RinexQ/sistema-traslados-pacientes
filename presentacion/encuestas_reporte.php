<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

// Los resultados de las encuestas son solo para el administrador
Auth::exigirAdministrador();

$busqueda   = trim($_GET['q'] ?? '');
$idServicio = (int) ($_GET['servicio'] ?? 0);

$errorBD = '';
$resumen = [];
$servicios = [];

try {
    $servicios = (new ServicioDAO())->listarTodos();
    $resumen = (new EncuestaDAO())->reporte($busqueda, $idServicio);
} catch (PDOException $ex) {
    $errorBD = 'No se pudo cargar el reporte.';
}

$tituloPagina = 'Encuestas';
$paginaActiva = 'encuestas';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4 mb-3">
                <h2>Encuestas de satisfacción</h2>
                <p class="text-muted">
                    Las personas responden desde el portal (<a href="../encuesta.php" target="_blank">ver encuestas</a>).
                    Las respuestas son anónimas: no se guarda quién respondió.
                </p>
            </div>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mb-3">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
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
                    <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Buscar</button>
                        <a href="encuestas_reporte.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Resultados por encuesta</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Encuesta</th>
                                <th>Servicio</th>
                                <th class="text-center">Respuestas</th>
                                <th class="text-center">Promedio</th>
                                <?php for ($n = 1; $n <= 5; $n++): ?>
                                    <th class="text-center" title="Cantidad de respuestas con <?= $n ?>">Opción <?= $n ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($resumen) === 0): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No hay encuestas para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($resumen as $fila): ?>
                            <tr>
                                <td><?= e($fila['titulo']) ?></td>
                                <td>
                                    <?= $fila['servicio'] === null ? '<span class="text-muted">General</span>' : e($fila['servicio']) ?>
                                    <?php if ($fila['servicio'] !== null && (int) $fila['servicio_activo'] === 0): ?>
                                        <span class="badge bg-warning text-dark">Servicio inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= (int) $fila['total'] ?></td>
                                <td class="text-center"><?= $fila['promedio'] === null ? '-' : e(number_format((float) $fila['promedio'], 2)) ?></td>
                                <?php for ($n = 1; $n <= 5; $n++): ?>
                                    <td class="text-center"><?= (int) $fila['c' . $n] ?></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
