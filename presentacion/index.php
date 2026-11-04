<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../datos/PanelDAO.php';

Auth::exigirSesion();
if (!Auth::esAdministrador()) {
    header('Location: documentos_listar.php');
    exit;
}

$dao = new PanelDAO();
$traslados        = $dao->trasladosRecientes(5);
$actividad        = $dao->actividadReciente(5);
$ind              = $dao->indicadores();

// Indicadores generales: [valor, etiqueta, icono, colores del icono]
$promedio = $ind['satisfaccion'] !== null && $ind['satisfaccion'] !== false
    ? number_format((float) $ind['satisfaccion'], 2) : '-';
$indicadores = [
    [$ind['documentos_activos'],   'Documentos activos',         'bi-file-earmark-text', 'bg-warning-subtle text-warning'],
    [$ind['documentos_ocultos'],   'Documentos ocultos',         'bi-eye-slash',         'bg-secondary-subtle text-secondary'],
    [$ind['documentos_baja'],      'Documentos dados de baja',   'bi-archive',           'bg-danger-subtle text-danger'],
    [$ind['servicios_activos'],    'Servicios activos',          'bi-diagram-3',         'bg-info-subtle text-info'],
    [$ind['usuarios_activos'],     'Usuarios activos',           'bi-person-badge',      'bg-primary-subtle text-primary'],
    [$ind['encuestas_publicadas'], 'Encuestas publicadas',       'bi-clipboard-check',   'bg-success-subtle text-success'],
    [$ind['encuestas_borrador'],   'Encuestas en borrador',      'bi-pencil-square',     'bg-secondary-subtle text-secondary'],
    [$ind['respuestas_total'],     'Respuestas recibidas',       'bi-chat-dots',         'bg-primary-subtle text-primary'],
    [$ind['respuestas_semana'],    'Respuestas (últimos 7 días)', 'bi-calendar-week',   'bg-success-subtle text-success'],
    [$promedio,                    'Satisfacción promedio (1 a 5)', 'bi-star',          'bg-warning-subtle text-warning'],
    [$ind['ambulancias'],          'Ambulancias',                'bi-truck',             'bg-success-subtle text-success'],
    [$ind['traslados_activos'],    'Traslados activos',          'bi-calendar-check',    'bg-danger-subtle text-danger'],
];

$coloresEstado = [
    'programado' => 'bg-warning text-dark',
    'en_curso'   => 'bg-primary',
    'finalizado' => 'bg-success',
    'cancelado'  => 'bg-danger',
];
$iconosActividad = [
    'documento' => 'bi-file-earmark-check text-warning',
    'traslado'  => 'bi-truck text-primary',
    'respuesta' => 'bi-chat-dots text-success',
];

$tituloPagina = 'Dashboard';
$paginaActiva = 'index';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Dashboard</h2>
                <p class="text-muted">Bienvenido al panel administrativo.</p>
            </div>

            <!-- Indicadores generales del sistema -->
            <div class="row g-3">
                <?php foreach ($indicadores as $i): ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="indicador">
                            <div class="indicador-icono <?= e($i[3]) ?>"><i class="bi <?= e($i[2]) ?>"></i></div>
                            <div>
                                <div class="indicador-valor"><?= e($i[0]) ?></div>
                                <div class="indicador-etiqueta"><?= e($i[1]) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-4">

                <div class="col-12 col-xl-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Traslados recientes</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Trayecto</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (count($traslados) === 0): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No hay traslados registrados.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($traslados as $t): ?>
                                    <tr>
                                        <td><?= e($t['origen']) ?> → <?= e($t['destino']) ?></td>
                                        <td>
                                            <span class="badge <?= e($coloresEstado[$t['estado']] ?? 'bg-secondary') ?>">
                                                <?= e(ucfirst(str_replace('_', ' ', $t['estado']))) ?>
                                            </span>
                                        </td>
                                        <td><?= e(date('d/m/Y H:i', strtotime($t['fecha_salida']))) ?></td>
                                        <td>
                                            <a href="ambulancias.php" class="btn btn-primary btn-sm">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Actividad reciente</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($actividad as $a): ?>
                                <div class="list-group-item">
                                    <i class="bi <?= e($iconosActividad[$a['tipo']] ?? 'bi-circle') ?> me-2"></i>
                                    <?= e($a['texto']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
