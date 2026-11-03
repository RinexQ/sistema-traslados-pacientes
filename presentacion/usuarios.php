<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

Auth::exigirAdministrador();

$busqueda = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? 'activos';
if (!in_array($estado, ['activos', 'baja', 'todos'], true)) {
    $estado = 'activos';
}
$mensaje = $_GET['msg'] ?? '';

$errorBD = '';
$usuarios = [];
try {
    $usuarios = (new UsuarioDAO())->listarAdministrativos($busqueda, $estado);
} catch (PDOException $ex) {
    $errorBD = 'No se pudieron cargar los usuarios.';
}

$tituloPagina = 'Usuarios';
$paginaActiva = 'usuarios';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Usuarios</h2>
                <p class="text-muted">
                    Solo los administradores pueden crear cuentas. Una cuenta dada de baja no puede iniciar sesión.
                    Los pacientes no tienen cuenta: acceden al portal escaneando el código QR.
                </p>
            </div>

            <?php if ($mensaje === 'creado'): ?>
                <div class="alert alert-success">Usuario creado correctamente.</div>
            <?php elseif ($mensaje === 'editado'): ?>
                <div class="alert alert-success">Usuario actualizado correctamente.</div>
            <?php elseif ($mensaje === 'baja'): ?>
                <div class="alert alert-success">El usuario se dio de baja.</div>
            <?php elseif ($mensaje === 'reactivado'): ?>
                <div class="alert alert-success">El usuario se reactivó.</div>
            <?php elseif ($mensaje === 'propio'): ?>
                <div class="alert alert-warning">No podés dar de baja tu propia cuenta.</div>
            <?php elseif ($mensaje === 'ultimo'): ?>
                <div class="alert alert-warning">No se puede dar de baja al último administrador activo.</div>
            <?php elseif ($mensaje === 'error'): ?>
                <div class="alert alert-danger">No se pudo completar la operación.</div>
            <?php endif; ?>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mb-3">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <label class="form-label small mb-1" for="q">Buscar (nombre, cédula o email)</label>
                        <input class="form-control" type="search" id="q" name="q" maxlength="150" value="<?= e($busqueda) ?>">
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
                        <a href="usuarios.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cuentas</h5>
                    <a href="usuario_form.php" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Cédula</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                        <?php if (count($usuarios) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($usuarios as $u): ?>
                            <?php $activo = (int) $u['activo'] === 1; ?>
                            <tr class="<?= $activo ? '' : 'table-secondary' ?>">
                                <td><?= e($u['ci']) ?></td>
                                <td><?= e($u['nombre']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($nombresRol[$u['rol']] ?? $u['rol']) ?></td>
                                <td>
                                    <?php if (!$activo): ?>
                                        <span class="badge bg-danger">Dado de baja</span>
                                    <?php elseif ((int) $u['tiene_password'] === 0): ?>
                                        <span class="badge bg-warning text-dark">Sin contraseña</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <a href="usuario_form.php?id=<?= (int) $u['id_admin'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="usuario_estado.php" class="d-inline"
                                          onsubmit="return confirm('<?= $activo ? '¿Dar de baja a este usuario? Ya no podrá iniciar sesión.' : '¿Reactivar a este usuario?' ?>');">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id_admin'] ?>">
                                        <input type="hidden" name="accion" value="<?= $activo ? 'baja' : 'reactivar' ?>">
                                        <?php if ($activo): ?>
                                            <button type="submit" class="btn btn-danger btn-sm" title="Dar de baja"><i class="bi bi-person-dash"></i></button>
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
