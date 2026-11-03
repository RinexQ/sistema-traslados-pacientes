<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Solicitud.php';
require_once __DIR__ . '/../datos/SolicitudDAO.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

// Pueden entrar el administrador y los usuarios basicos
Auth::exigirPersonal();
$usuario = Auth::usuarioActual();

$errores = [];
$datos = [
    'tipo' => 'paciente', 'prioridad' => 'sin_prioridad', 'ci_paciente' => '', 'origen' => 'Hospital de Clínicas', 'destino' => '',
    'domicilio' => '', 'telefono' => '', 'diagnostico' => '', 'motivo' => '',
    'aislamiento_detalle' => '', 'rodado' => 'ninguno',
];
$marcados = ['aislamiento' => false, 'oxigeno' => false, 'verificado' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();
    $solicitud = new Solicitud($_POST);
    $errores = $solicitud->validar();

    if (count($errores) === 0) {
        try {
            $dao = new SolicitudDAO();
            $dao->crear($solicitud, (int) $usuario['identificador']);
            header('Location: solicitudes.php?msg=creada');
            exit;
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo guardar la solicitud. Intentá de nuevo.';
        }
    }

    // Si hubo errores, el formulario conserva lo que se escribio
    foreach ($datos as $campo => $valor) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }
    foreach ($marcados as $campo => $valor) {
        $marcados[$campo] = isset($_POST[$campo]);
    }
}

$pacientes = (new UsuarioDAO())->listarPacientes();

$tituloPagina = 'Nueva solicitud de traslado';
$paginaActiva = 'solicitudes';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Nueva solicitud de traslado</h2>
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

            <div class="card shadow-sm" style="max-width: 800px;">
                <div class="card-body d-block">
                    <form method="post" id="form-solicitud" novalidate>
                        <?= Csrf::campo() ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tipo">Qué se traslada</label>
                                <select id="tipo" name="tipo" class="form-select">
                                    <?php foreach (Solicitud::GRUPOS as $grupo => $claves): ?>
                                        <optgroup label="<?= e($grupo) ?>">
                                            <?php foreach ($claves as $clave): ?>
                                                <option value="<?= e($clave) ?>" <?= $datos['tipo'] === $clave ? 'selected' : '' ?>>
                                                    <?= e(Solicitud::TIPOS[$clave]) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="prioridad">Prioridad</label>
                                <select id="prioridad" name="prioridad" class="form-select">
                                    <?php foreach (Solicitud::PRIORIDADES as $clave => $nombre): ?>
                                        <option value="<?= e($clave) ?>" <?= $datos['prioridad'] === $clave ? 'selected' : '' ?>>
                                            <?= e($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Los traslados de órganos siempre tienen prioridad de órganos.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="origen">Origen</label>
                                <input type="text" id="origen" name="origen" class="form-control" maxlength="200"
                                       value="<?= e($datos['origen']) ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="destino">Destino</label>
                                <input type="text" id="destino" name="destino" class="form-control" maxlength="200"
                                       value="<?= e($datos['destino']) ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="motivo">Motivo del traslado</label>
                            <input type="text" id="motivo" name="motivo" class="form-control" maxlength="255"
                                   value="<?= e($datos['motivo']) ?>">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div id="bloque-paciente">
                            <hr>
                            <h5>Datos del paciente</h5>

                            <div class="mb-3">
                                <label class="form-label" for="ci_paciente">Paciente</label>
                                <select id="ci_paciente" name="ci_paciente" class="form-select">
                                    <option value="">-- Elegí un paciente --</option>
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?= e($p['ci']) ?>" <?= $datos['ci_paciente'] === $p['ci'] ? 'selected' : '' ?>>
                                            <?= e($p['nombre']) ?> (<?= e($p['ci']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label" for="domicilio">Domicilio del paciente</label>
                                    <input type="text" id="domicilio" name="domicilio" class="form-control" maxlength="200"
                                           value="<?= e($datos['domicilio']) ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" maxlength="20"
                                           value="<?= e($datos['telefono']) ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="diagnostico">Diagnóstico</label>
                                <input type="text" id="diagnostico" name="diagnostico" class="form-control" maxlength="255"
                                       value="<?= e($datos['diagnostico']) ?>">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="rodado">Rodado para el traslado</label>
                                    <select id="rodado" name="rodado" class="form-select">
                                        <option value="ninguno" <?= $datos['rodado'] === 'ninguno' ? 'selected' : '' ?>>Ninguno</option>
                                        <option value="silla" <?= $datos['rodado'] === 'silla' ? 'selected' : '' ?>>Silla de ruedas</option>
                                        <option value="camilla" <?= $datos['rodado'] === 'camilla' ? 'selected' : '' ?>>Camilla</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" id="oxigeno" name="oxigeno" class="form-check-input"
                                               <?= $marcados['oxigeno'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="oxigeno">Necesita oxígeno</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" id="aislamiento" name="aislamiento" class="form-check-input"
                                           <?= $marcados['aislamiento'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="aislamiento">Requiere aislamiento</label>
                                </div>
                                <input type="text" id="aislamiento_detalle" name="aislamiento_detalle"
                                       class="form-control mt-2" maxlength="150" placeholder="Especificar el tipo de aislamiento"
                                       value="<?= e($datos['aislamiento_detalle']) ?>">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" id="verificado" name="verificado" class="form-check-input"
                                           <?= $marcados['verificado'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="verificado">
                                        Lista de verificación: certifico que el paciente requiere traslado
                                    </label>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                        <a href="solicitudes.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
