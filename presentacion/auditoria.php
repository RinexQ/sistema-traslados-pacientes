<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Filtros.php';
require_once __DIR__ . '/../negocio/Paginacion.php';
require_once __DIR__ . '/../datos/AuditoriaDAO.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

// Registro de acciones importantes: quien hizo que y cuando (solo administrador)
Auth::exigirAdministrador();

$filtros = [
    'usuario' => (int) ($_GET['usuario'] ?? 0),
    'accion'  => trim($_GET['accion'] ?? ''),
    'entidad' => trim($_GET['entidad'] ?? ''),
    'desde'   => Filtros::fecha($_GET['desde'] ?? ''),
    'hasta'   => Filtros::fecha($_GET['hasta'] ?? ''),
];

// nombres legibles de las acciones (si aparece una que no esta, se muestra tal cual)
$nombresAccion = [
    'login' => 'Inicio de sesión', 'logout' => 'Cierre de sesión',
    'documento_alta' => 'Documento cargado', 'documento_edicion' => 'Documento editado',
    'documento_baja' => 'Documento dado de baja', 'documento_reactivacion' => 'Documento reactivado',
    'usuario_alta' => 'Usuario creado', 'usuario_edicion' => 'Usuario editado',
    'usuario_baja' => 'Usuario dado de baja', 'usuario_reactivacion' => 'Usuario reactivado',
    'password_cambio' => 'Contraseña cambiada',
    'servicio_alta' => 'Servicio creado', 'servicio_edicion' => 'Servicio editado',
    'servicio_baja' => 'Servicio dado de baja', 'servicio_reactivacion' => 'Servicio reactivado',
    'encuesta_alta' => 'Encuesta creada', 'encuesta_edicion' => 'Encuesta editada',
    'encuesta_publicacion' => 'Encuesta publicada', 'encuesta_cierre' => 'Encuesta cerrada',
    'encuesta_duplicacion' => 'Encuesta duplicada', 'encuesta_version' => 'Nueva versión de encuesta',
    'encuesta_exportacion' => 'Resultados exportados',
    'pregunta_alta' => 'Pregunta agregada', 'pregunta_edicion' => 'Pregunta editada',
    'pregunta_baja' => 'Pregunta eliminada', 'pregunta_orden' => 'Pregunta reordenada',
];

$errorBD = '';
$registros = [];
$acciones = [];
$entidades = [];
$usuarios = [];
$paginacion = new Paginacion(0, 1);
try {
    $dao = new AuditoriaDAO();
    $acciones = $dao->acciones();
    $entidades = $dao->entidades();
    $usuarios = (new UsuarioDAO())->listarAdministrativos('', 'todos');
    $paginacion = new Paginacion($dao->contar($filtros), Paginacion::paginaPedida(), 15);
    $registros = $dao->listar($filtros, $paginacion->limite(), $paginacion->desplazamiento());
} catch (PDOException $ex) {
    $errorBD = 'No se pudo cargar el registro de acciones.';
}

$tituloPagina = 'Registro de acciones';
$paginaActiva = 'auditoria';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Registro de acciones</h2>
                <p class="text-muted">Quién hizo qué y cuándo: altas, bajas, ediciones, publicaciones y exportaciones importantes.</p>
            </div>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mb-3">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1" for="usuario">Usuario</label>
                        <select class="form-select" id="usuario" name="usuario">
                            <option value="0">Todos</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= (int) $u['id_admin'] ?>" <?= (int) $u['id_admin'] === $filtros['usuario'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="accion">Acción</label>
                        <select class="form-select" id="accion" name="accion">
                            <option value="">Todas</option>
                            <?php foreach ($acciones as $a): ?>
                                <option value="<?= e($a) ?>" <?= $a === $filtros['accion'] ? 'selected' : '' ?>><?= e($nombresAccion[$a] ?? $a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="entidad">Sobre qué</label>
                        <select class="form-select" id="entidad" name="entidad">
                            <option value="">Todo</option>
                            <?php foreach ($entidades as $en): ?>
                                <option value="<?= e($en) ?>" <?= $en === $filtros['entidad'] ? 'selected' : '' ?>><?= e(ucfirst($en)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                        <a href="auditoria.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr>
                        </thead>
                        <tbody>
                        <?php if (count($registros) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay acciones registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td class="text-nowrap small"><?= e(date('d/m/Y H:i', strtotime($r['fecha']))) ?></td>
                                <td><?= e($r['usuario'] ?? 'Sistema') ?></td>
                                <td><span class="badge bg-secondary"><?= e($nombresAccion[$r['accion']] ?? $r['accion']) ?></span></td>
                                <td class="small"><?= e($r['detalle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require __DIR__ . '/paginacion.php'; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
