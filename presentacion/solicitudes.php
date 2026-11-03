<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/SolicitudDAO.php';

// Pueden entrar el administrador y los usuarios basicos
Auth::exigirPersonal();
$usuario = Auth::usuarioActual();
$esAdmin = Auth::esAdministrador();
$mensaje = $_GET['msg'] ?? '';

$errorBD = '';
$solicitudes = [];
try {
    $dao = new SolicitudDAO();
    if ($esAdmin) {
        // el administrador ve todas las solicitudes
        $solicitudes = $dao->listar();
    } else {
        // el usuario basico ve solo las solicitudes que cargo el
        $solicitudes = $dao->listar((int) $usuario['identificador']);
    }
} catch (PDOException $ex) {
    $errorBD = 'No se pudieron cargar las solicitudes.';
}

$tipos = Solicitud::TIPOS;
$prioridades = Solicitud::PRIORIDADES;
$rodados = [
    'ninguno' => 'Ninguno',
    'silla'   => 'Silla de ruedas',
    'camilla' => 'Camilla',
];
$colores = [
    'pendiente' => 'bg-warning text-dark',
    'aprobada'  => 'bg-success',
    'rechazada' => 'bg-danger',
];

$tituloPagina = 'Solicitudes de traslado';
$paginaActiva = 'solicitudes';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4 mb-3">
                <h2>Solicitudes de traslado</h2>
                <?php if (!$esAdmin): ?>
                    <p class="text-muted">Acá ves las solicitudes que hiciste vos.</p>
                <?php endif; ?>
            </div>

            <?php if ($mensaje === 'creada'): ?>
                <div class="alert alert-success">Solicitud enviada correctamente.</div>
            <?php elseif ($mensaje === 'estado'): ?>
                <div class="alert alert-success">El estado de la solicitud se actualizó.</div>
            <?php elseif ($mensaje === 'asignado'): ?>
                <div class="alert alert-success">El traslado quedó asignado.</div>
            <?php elseif ($mensaje === 'error'): ?>
                <div class="alert alert-danger">No se pudo completar la operación.</div>
            <?php endif; ?>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Solicitudes</h5>
                    <a href="solicitud_form.php" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-lg"></i> Nueva solicitud
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Tipo</th>
                                <th>Prioridad</th>
                                <th>Paciente</th>
                                <th>Trayecto</th>
                                <th>Solicitó</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                                <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($solicitudes) === 0): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No hay solicitudes para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($solicitudes as $s): ?>
                            <tr>
                                <td><?= e(date('d/m/Y H:i', strtotime($s['fecha']))) ?></td>
                                <td><?= e($tipos[$s['tipo']] ?? $s['tipo']) ?></td>
                                <td>
                                    <?php if ($s['prioridad'] === 'sin_prioridad'): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= e($prioridades[$s['prioridad']] ?? $s['prioridad']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($s['paciente'] ?? '-') ?></td>
                                <td><?= e($s['origen']) ?> → <?= e($s['destino']) ?></td>
                                <td><?= e($s['solicitante']) ?></td>
                                <td>
                                    <span class="badge <?= e($colores[$s['estado']] ?? 'bg-secondary') ?>">
                                        <?= e(ucfirst($s['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <details>
                                        <summary>Ver</summary>
                                        <div class="small mt-2">
                                            <div><b>Motivo:</b> <?= e($s['motivo']) ?></div>
                                            <?php if ($s['tipo'] === 'paciente'): ?>
                                                <div><b>Domicilio:</b> <?= e($s['domicilio']) ?></div>
                                                <div><b>Teléfono:</b> <?= e($s['telefono']) ?></div>
                                                <div><b>Diagnóstico:</b> <?= e($s['diagnostico']) ?></div>
                                                <div><b>Aislamiento:</b>
                                                    <?= $s['aislamiento'] ? 'Sí - ' . e($s['aislamiento_detalle']) : 'No' ?>
                                                </div>
                                                <div><b>Rodado:</b> <?= e($rodados[$s['rodado']] ?? $s['rodado']) ?></div>
                                                <div><b>Oxígeno:</b> <?= $s['oxigeno'] ? 'Sí' : 'No' ?></div>
                                                <div><b>Lista de verificación:</b> <?= $s['verificado'] ? 'Confirmada' : 'Sin confirmar' ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </td>
                                <?php if ($esAdmin): ?>
                                <td>
                                    <?php if ($s['estado'] === 'pendiente'): ?>
                                        <form method="post" action="solicitud_estado.php" class="d-inline">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $s['id_solicitud'] ?>">
                                            <button type="submit" name="estado" value="aprobada" class="btn btn-success btn-sm">Aprobar</button>
                                            <button type="submit" name="estado" value="rechazada" class="btn btn-danger btn-sm">Rechazar</button>
                                        </form>
                                    <?php elseif ($s['estado'] === 'aprobada' && (int) $s['traslados'] === 0): ?>
                                        <a href="traslado_form.php?solicitud=<?= (int) $s['id_solicitud'] ?>" class="btn btn-primary btn-sm">
                                            Asignar traslado
                                        </a>
                                    <?php elseif ((int) $s['traslados'] > 0): ?>
                                        <span class="text-muted">Traslado asignado</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
