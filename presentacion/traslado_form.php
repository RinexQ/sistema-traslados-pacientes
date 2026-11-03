<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Traslado.php';
require_once __DIR__ . '/../negocio/Solicitud.php';
require_once __DIR__ . '/../datos/TrasladoDAO.php';

// Solo el administrador asigna traslados
Auth::exigirAdministrador();

$idSolicitud = isset($_GET['solicitud']) ? (int) $_GET['solicitud'] : 0;
$dao = new TrasladoDAO();

$solicitud = $idSolicitud > 0 ? $dao->obtenerSolicitud($idSolicitud) : null;
if ($solicitud === null || $solicitud['estado'] !== 'aprobada') {
    header('Location: solicitudes.php?msg=error');
    exit;
}

$errores = [];
$elegido = ['id_conductor' => 0, 'id_vehiculo' => 0, 'id_enfermero' => 0, 'fecha_salida' => date('Y-m-d\TH:i')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();
    $traslado = new Traslado($idSolicitud, $_POST);
    $vehiculo = $traslado->idVehiculo > 0 ? $dao->obtenerVehiculo($traslado->idVehiculo) : null;
    $errores = $traslado->validar($solicitud, $vehiculo);

    if (count($errores) === 0) {
        try {
            $dao->crear($traslado);
            header('Location: solicitudes.php?msg=asignado');
            exit;
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo guardar el traslado. Intentá de nuevo.';
        }
    }

    // Si hubo errores, el formulario conserva lo que se eligio
    $elegido['id_conductor'] = $traslado->idConductor;
    $elegido['id_vehiculo']  = $traslado->idVehiculo;
    $elegido['id_enfermero'] = (int) $traslado->idEnfermero;
    $elegido['fecha_salida'] = str_replace(' ', 'T', substr($traslado->fechaSalida, 0, 16));
}

$conductores = $dao->listarConductores();
$vehiculos = $dao->listarVehiculos();
$enfermeros = $dao->listarEnfermeros();

$tituloPagina = 'Asignar traslado';
$paginaActiva = 'solicitudes';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Asignar traslado</h2>
                <p class="text-muted">
                    <?= e(Solicitud::TIPOS[$solicitud['tipo']] ?? $solicitud['tipo']) ?>:
                    <?= e($solicitud['origen']) ?> → <?= e($solicitud['destino']) ?>
                    (<?= e($solicitud['motivo']) ?>)
                </p>
            </div>

            <?php if (count($errores) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm" style="max-width: 600px;">
                <div class="card-body d-block">
                    <form method="post" id="form-traslado" novalidate>
                        <?= Csrf::campo() ?>

                        <div class="mb-3">
                            <label class="form-label" for="id_conductor">Conductor</label>
                            <select id="id_conductor" name="id_conductor" class="form-select">
                                <option value="">-- Elegí un conductor --</option>
                                <?php foreach ($conductores as $c): ?>
                                    <option value="<?= (int) $c['id_conductor'] ?>"
                                        <?= (int) $c['id_conductor'] === $elegido['id_conductor'] ? 'selected' : '' ?>>
                                        <?= e($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="id_vehiculo">Vehículo</label>
                            <select id="id_vehiculo" name="id_vehiculo" class="form-select">
                                <option value="">-- Elegí un vehículo --</option>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= (int) $v['id_vehiculo'] ?>"
                                        data-requiere="<?= $v['requiere_enfermero'] ? '1' : '0' ?>"
                                        <?= $v['estado'] === 'roto' ? 'disabled' : '' ?>
                                        <?= (int) $v['id_vehiculo'] === $elegido['id_vehiculo'] ? 'selected' : '' ?>>
                                        <?= e($v['patente']) ?> - <?= e($v['tipo']) ?>
                                        <?= $v['requiere_enfermero'] ? '(requiere enfermero)' : '' ?>
                                        <?= $v['estado'] === 'roto' ? '(roto)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="id_enfermero">Enfermero</label>
                            <select id="id_enfermero" name="id_enfermero" class="form-select">
                                <option value="">-- Sin enfermero --</option>
                                <?php foreach ($enfermeros as $en): ?>
                                    <option value="<?= (int) $en['id_enfermero'] ?>"
                                        <?= (int) $en['id_enfermero'] === $elegido['id_enfermero'] ? 'selected' : '' ?>>
                                        <?= e($en['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Es obligatorio si el vehículo lo requiere.</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="fecha_salida">Fecha y hora de salida</label>
                            <input type="datetime-local" id="fecha_salida" name="fecha_salida" class="form-control"
                                   value="<?= e($elegido['fecha_salida']) ?>">
                            <div class="invalid-feedback"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">Asignar traslado</button>
                        <a href="solicitudes.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
