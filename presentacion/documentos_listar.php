<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Paginacion.php';
require_once __DIR__ . '/../datos/DocumentoDAO.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

Auth::exigirSesion();

// El administrador gestiona todos los documentos; el usuario basico solo ve los publicados y activos
$esAdmin = Auth::esAdministrador();

$busqueda   = trim($_GET['q'] ?? '');
$idServicio = (int) ($_GET['servicio'] ?? 0);
$estado     = $_GET['estado'] ?? 'activos';
if (!in_array($estado, ['activos', 'baja', 'todos'], true)) {
    $estado = 'activos';
}
$mensaje = $_GET['msg'] ?? '';

$errorBD = '';
$documentos = [];
$servicios = [];
$paginacion = new Paginacion(0, 1);
try {
    $serviciosDao = new ServicioDAO();
    $servicios = $esAdmin ? $serviciosDao->listarTodos() : $serviciosDao->listarActivos();
    $dao = new DocumentoDAO();
    $total = $esAdmin
        ? $dao->contarAdmin($busqueda, $idServicio, $estado)
        : $dao->contarPublicos($busqueda, $idServicio);
    $paginacion = new Paginacion($total, Paginacion::paginaPedida(), 10);
    $documentos = $esAdmin
        ? $dao->listarAdmin($busqueda, $idServicio, $estado, $paginacion->limite(), $paginacion->desplazamiento())
        : $dao->listarPublicos($busqueda, $idServicio, $paginacion->limite(), $paginacion->desplazamiento());
} catch (PDOException $ex) {
    $errorBD = 'No se pudieron cargar los documentos.';
}

$tituloPagina = 'Documentación';
$paginaActiva = 'documentos';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h2>Documentación</h2>
            </div>

            <?php if ($mensaje === 'creado'): ?>
                <div class="alert alert-success">Documento cargado correctamente.</div>
            <?php elseif ($mensaje === 'editado'): ?>
                <div class="alert alert-success">Documento actualizado correctamente.</div>
            <?php elseif ($mensaje === 'baja'): ?>
                <div class="alert alert-success">El documento se dio de baja. Ya no se puede abrir.</div>
            <?php elseif ($mensaje === 'reactivado'): ?>
                <div class="alert alert-success">El documento se reactivó correctamente.</div>
            <?php elseif ($mensaje === 'error'): ?>
                <div class="alert alert-danger">No se pudo completar la operación.</div>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="servicio">Servicio</label>
                        <select class="form-select" id="servicio" name="servicio">
                            <option value="0">Todos</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= (int) $s['id_servicio'] ?>" <?= (int) $s['id_servicio'] === $idServicio ? 'selected' : '' ?>>
                                    <?= e($s['nombre']) ?><?= isset($s['activo']) && (int) $s['activo'] === 0 ? ' (inactivo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($esAdmin): ?>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1" for="estado">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                            <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Dados de baja</option>
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Buscar</button>
                        <a href="documentos_listar.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Documentos</h5>
                    <?php if ($esAdmin): ?>
                        <a href="documento_form.php" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg"></i> Nuevo documento
                        </a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Cargado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($documentos) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay documentos para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($documentos as $doc): ?>
                            <?php $estaActivo = (int) $doc['activo'] === 1; ?>
                            <tr class="<?= $estaActivo ? '' : 'table-secondary' ?>">
                                <td>
                                    <?= e($doc['titulo']) ?>
                                    <?php if (!empty($doc['descripcion'])): ?>
                                        <div class="small text-muted"><?= e($doc['descripcion']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e($doc['servicio']) ?>
                                    <?php if ((int) $doc['servicio_activo'] === 0): ?>
                                        <span class="badge bg-warning text-dark">Servicio inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$estaActivo): ?>
                                        <span class="badge bg-danger">Dado de baja</span>
                                    <?php elseif ((int) $doc['visible'] === 1): ?>
                                        <span class="badge bg-success">Publicado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Oculto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?= e($doc['fecha_carga']) ?><br>
                                    <span class="text-muted"><?= e($doc['admin_nombre']) ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($estaActivo): ?>
                                        <a href="<?= e(Auth::url('documento_abrir.php')) ?>?id=<?= (int) $doc['id_documento'] ?>"
                                           target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm" title="Abrir">
                                            <i class="bi bi-eye"></i> Abrir
                                        </a>
                                        <a href="<?= e(Auth::url('descargar.php')) ?>?id=<?= (int) $doc['id_documento'] ?>"
                                           class="btn btn-outline-secondary btn-sm" title="Descargar">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($esAdmin): ?>
                                        <?php if ($estaActivo): ?>
                                            <a href="documento_form.php?id=<?= (int) $doc['id_documento'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" action="documento_baja.php" class="d-inline"
                                              onsubmit="return confirm('<?= $estaActivo ? '¿Dar de baja este documento? Dejará de poder abrirse.' : '¿Reactivar este documento?' ?>');">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $doc['id_documento'] ?>">
                                            <input type="hidden" name="accion" value="<?= $estaActivo ? 'baja' : 'reactivar' ?>">
                                            <?php if ($estaActivo): ?>
                                                <button type="submit" class="btn btn-danger btn-sm" title="Dar de baja"><i class="bi bi-trash"></i></button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-success btn-sm" title="Reactivar"><i class="bi bi-arrow-counterclockwise"></i> Reactivar</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require __DIR__ . '/paginacion.php'; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
