<?php
// Encuestas de satisfaccion (publicas). No piden sesion ni datos personales:
// la respuesta guarda solo la calificacion y la fecha.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/negocio/Recaptcha.php';
require_once __DIR__ . '/negocio/RespuestaEncuesta.php';
require_once __DIR__ . '/datos/EncuestaDAO.php';
require_once __DIR__ . '/datos/ServicioDAO.php';

function esc($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$busqueda   = trim($_GET['q'] ?? '');
$idServicio = (int) ($_GET['servicio'] ?? 0);
$gracias    = isset($_GET['gracias']);

$dao = new EncuestaDAO();
$errores = [];
$encuesta = null;
$encuestas = [];
$servicios = [];

try {
    if ($id > 0) {
        $encuesta = $dao->obtenerPublica($id);
        if ($encuesta === null) {
            $errores[] = 'La encuesta no está disponible.';
        }
    }
    if ($encuesta === null) {
        $servicios = (new ServicioDAO())->listarActivos();
        $encuestas = $dao->listarPublicas($busqueda, $idServicio);
    }
} catch (PDOException $ex) {
    $errores[] = 'No se pudo cargar la encuesta en este momento.';
}

$calificacion = 0;
$usaRecaptcha = Recaptcha::activo();

if ($encuesta !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();

    $respuesta = new RespuestaEncuesta($id, $_POST);
    $errores = $respuesta->validar();

    // Verificacion anti-robots: casilla "No soy un robot" de Google
    if ($usaRecaptcha && !Recaptcha::verificar($_POST['g-recaptcha-response'] ?? '')) {
        $errores[] = 'Marcá la casilla "No soy un robot" para poder enviar.';
    }

    if (count($errores) === 0) {
        try {
            $dao->guardarRespuesta($respuesta);
            // Redirigimos para que recargar la pagina no vuelva a enviar la respuesta
            header('Location: encuesta.php?id=' . $id . '&gracias=1');
            exit;
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo guardar tu respuesta. Intentá de nuevo.';
        }
    }
    $calificacion = $respuesta->calificacion;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuestas de satisfacción - <?= esc(SIGLAS_SISTEMA) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="presentacion/css/estilos.css">
</head>
<body>
    <div class="bg-primary text-white py-4 cabecera-publica">
        <div class="container">
            <h1 class="h3 mb-1">Encuestas de satisfacción</h1>
            <p class="mb-0"><?= esc(SUBTITULO_SISTEMA) ?></p>
        </div>
    </div>

    <div class="container py-4" style="max-width: 700px;">
        <p><a href="portal.php"><i class="bi bi-arrow-left"></i> Volver a la portada</a></p>

        <?php if ($gracias && $encuesta !== null): ?>
            <div class="alert alert-success">
                ¡Gracias por responder! Tu opinión es anónima y nos ayuda a mejorar la atención.
            </div>
            <a href="encuesta.php" class="btn btn-primary">Ver otras encuestas</a>

        <?php elseif ($encuesta === null): ?>

            <?php if (count($errores) > 0): ?>
                <div class="alert alert-danger"><?= esc($errores[0]) ?></div>
            <?php endif; ?>

            <form method="get" class="row g-2 mb-4">
                <div class="col-12 col-md-5">
                    <label class="form-label visually-hidden" for="q">Buscar por título</label>
                    <input class="form-control" type="search" id="q" name="q" maxlength="200"
                           placeholder="Buscar por título..." value="<?= esc($busqueda) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label visually-hidden" for="servicio">Servicio</label>
                    <select class="form-select" id="servicio" name="servicio">
                        <option value="0">Todos los servicios</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= (int) $s['id_servicio'] ?>" <?= (int) $s['id_servicio'] === $idServicio ? 'selected' : '' ?>>
                                <?= esc($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Buscar</button>
                    <a href="encuesta.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                </div>
            </form>

            <h2 class="h5 mb-3">Elegí la encuesta que querés responder</h2>
            <div class="list-group">
                <?php foreach ($encuestas as $e): ?>
                    <a class="list-group-item list-group-item-action" href="encuesta.php?id=<?= (int) $e['id_encuesta'] ?>">
                        <?= esc($e['titulo']) ?>
                        <?php if (!empty($e['servicio'])): ?>
                            <span class="badge bg-secondary ms-2"><?= esc($e['servicio']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($encuestas) === 0 && count($errores) === 0): ?>
                    <div class="list-group-item text-muted">No se encontraron encuestas.</div>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <h2 class="h5 mb-3"><?= esc($encuesta['titulo']) ?></h2>
            <p class="text-muted small">
                Esta encuesta es anónima: no te pedimos ningún dato personal.
            </p>

            <?php if (count($errores) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?= esc($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body d-block">
                    <form method="post" id="form-encuesta" novalidate>
                        <?= Csrf::campo() ?>

                        <div class="mb-3" id="grupo-calificacion">
                            <label class="form-label d-block fw-semibold"><?= esc($encuesta['pregunta']) ?></label>
                            <?php $etiquetas = [1 => 'Muy mala', 2 => 'Mala', 3 => 'Regular', 4 => 'Buena', 5 => 'Excelente']; ?>
                            <?php for ($n = 1; $n <= 5; $n++): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="calificacion"
                                           id="calificacion<?= $n ?>" value="<?= $n ?>"
                                           <?= $calificacion === $n ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="calificacion<?= $n ?>">
                                        <?= $n ?> - <?= esc($etiquetas[$n]) ?>
                                    </label>
                                </div>
                            <?php endfor; ?>
                            <div class="text-danger small mt-1" id="error-calificacion"></div>
                        </div>

                        <?php if ($usaRecaptcha): ?>
                            <div class="mb-3">
                                <?= Recaptcha::casilla() ?>
                                <div class="text-danger small mt-1" id="error-recaptcha"></div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Enviar respuesta</button>
                        <a href="encuesta.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($usaRecaptcha): ?>
        <?= Recaptcha::script() ?>
    <?php endif; ?>
    <script src="presentacion/js/validaciones.js"></script>
</body>
</html>
