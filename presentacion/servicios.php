<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

Auth::exigirAdministrador();

$busqueda = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? 'activos';
if (!in_array($estado, ['activos', 'baja', 'todos'], true)) {
    $estado = 'activos';
}
$mensaje = $_GET['msg'] ?? '';

$errorBD = '';
$servicios = [];
try {
    $servicios = (new ServicioDAO())->listarTodos($busqueda, $estado);
} catch (PDOException $ex) {
    $errorBD = 'No se pudieron cargar los servicios.';
}

$tituloPagina = 'Servicios';
$paginaActiva = 'servicios';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Servicios</h2>
                <p class="text-muted">
                    Cuando un servicio se da de baja, sus documentos y encuestas dejan de mostrarse en el portal
                    y no se pueden cargar documentos nuevos en él. Al reactivarlo vuelven a mostrarse.
                </p>
            </div>

            <?php if ($mensaje === 'creado'): ?>
                <div class="alert alert-success">Servicio creado correctamente.</div>
            <?php elseif ($mensaje === 'editado'): ?>
                <div class="alert alert-success">Servicio actualizado correctamente.</div>
            <?php elseif ($mensaje === 'baja'): ?>
                <div class="alert alert-success">El servicio se dio de baja.</div>
            <?php elseif ($mensaje === 'reactivado'): ?>
                <div class="alert alert-success">El servicio se reactivó.</div>
            <?php elseif ($mensaje === 'error'): ?>
                <div class="alert alert-danger">No se pudo completar la operación.</div>
            <?php endif; ?>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mb-3">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <label class="form-label small mb-1" for="q">Buscar por nombre</label>
                        <input class="form-control" type="search" id="q" name="q" maxlength="100" value="<?= e($busqueda) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="estado">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                            <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Dados de baja</option>
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Buscar</button>
                        <a href="servicios.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Servicios del hospital</h5>
                    <a href="servicio_form.php" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Nuevo servicio</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Nombre</th><th class="text-center">Documentos activos</th><th class="text-center">Encuestas</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                        <?php if (count($servicios) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay servicios para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($servicios as $s): ?>
                            <?php
                            $activo = (int) $s['activo'] === 1;
                            $aviso = $activo
                                ? '¿Dar de baja este servicio? Sus ' . (int) $s['documentos'] . ' documentos y ' . (int) $s['encuestas'] . ' encuestas dejarán de mostrarse en el portal.'
                                : '¿Reactivar este servicio? Sus documentos y encuestas volverán a mostrarse.';
                            ?>
                            <tr class="<?= $activo ? '' : 'table-secondary' ?>">
                                <td><?= e($s['nombre']) ?></td>
                                <td class="text-center"><?= (int) $s['documentos'] ?></td>
                                <td class="text-center"><?= (int) $s['encuestas'] ?></td>
                                <td>
                                    <span class="badge <?= $activo ? 'bg-success' : 'bg-danger' ?>"><?= $activo ? 'Activo' : 'Dado de baja' ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <a href="servicio_form.php?id=<?= (int) $s['id_servicio'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="servicio_estado.php" class="d-inline"
                                          onsubmit="return confirm(<?= e(json_encode($aviso, JSON_UNESCAPED_UNICODE)) ?>);">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id_servicio'] ?>">
                                        <input type="hidden" name="accion" value="<?= $activo ? 'baja' : 'reactivar' ?>">
                                        <?php if ($activo): ?>
                                            <button type="submit" class="btn btn-danger btn-sm" title="Dar de baja"><i class="bi bi-trash"></i></button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success btn-sm" title="Reactivar"><i class="bi bi-arrow-counterclockwise"></i> Reactivar</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
