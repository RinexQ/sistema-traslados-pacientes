<?php
// Encuestas de satisfaccion (publicas). No piden sesion ni datos personales:
// del envio solo se guarda la fecha y las respuestas.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/negocio/Auth.php';
require_once __DIR__ . '/negocio/Recaptcha.php';
require_once __DIR__ . '/negocio/EnvioEncuesta.php';
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
$preguntas = [];
$encuestas = [];
$servicios = [];
$usaRecaptcha = Recaptcha::activo();

Auth::iniciar();

try {
    if ($id > 0 && !$gracias) {
        $encuesta = $dao->obtenerPublica($id);
        if ($encuesta === null) {
            $existente = $dao->obtener($id);
            $errores[] = ($existente !== null && $existente['estado'] === 'cerrada')
                ? 'Esta encuesta ya está cerrada y no recibe más respuestas.'
                : 'La encuesta no está disponible.';
        } else {
            $preguntas = $dao->preguntas($id);
        }
    }
    if ($encuesta === null && !$gracias) {
        $servicios = (new ServicioDAO())->listarActivos();
        $encuestas = $dao->listarPublicas($busqueda, $idServicio);
    }
} catch (PDOException $ex) {
    $encuesta = null;
    $errores[] = 'No se pudo cargar la encuesta en este momento.';
}

$valores = [];
$tokenEnvio = '';

if ($encuesta !== null) {
    $tokenPedido = (string) ($_POST['token_envio'] ?? '');
    $tokenVigente = preg_match('/^[a-f0-9]{32}$/', $tokenPedido)
        && (int) ($_SESSION['envio_tokens'][$tokenPedido] ?? 0) === $id;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Csrf::exigir();
        $valores = (isset($_POST['p']) && is_array($_POST['p'])) ? $_POST['p'] : [];

        if (!$tokenVigente) {
            $errores[] = 'Este formulario ya fue enviado o venció. Volvé a abrir la encuesta para responderla.';
        } else {
            // Se valida que las preguntas y opciones sean de esta encuesta y el formato de cada tipo
            [$errores, $respuestas] = EnvioEncuesta::validar($preguntas, $_POST);

            if (count($errores) === 0 && count($respuestas) === 0) {
                $errores[] = 'Respondé al menos una pregunta antes de enviar.';
            }
            // Verificacion anti-robots: casilla "No soy un robot" de Google
            if ($usaRecaptcha && !Recaptcha::verificar($_POST['g-recaptcha-response'] ?? '')) {
                $errores[] = 'Marcá la casilla "No soy un robot" para poder enviar.';
            }

            if (count($errores) === 0) {
                try {
                    $guardado = $dao->guardarEnvio($id, $tokenPedido, $respuestas);
                    unset($_SESSION['envio_tokens'][$tokenPedido]);
                    if ($guardado) {
                        // Se redirige para que recargar la pagina no vuelva a enviar la respuesta
                        header('Location: encuesta.php?id=' . $id . '&gracias=1');
                        exit;
                    }
                    $errores[] = 'Esta respuesta ya se había enviado o la encuesta se cerró.';
                } catch (PDOException $ex) {
                    $errores[] = 'No se pudo guardar tu respuesta. Intentá de nuevo.';
                }
            }
        }
    }

    // Cada vez que se muestra el formulario hay un token que solo sirve para un envio
    if ($tokenVigente && count($errores) > 0 && isset($_SESSION['envio_tokens'][$tokenPedido])) {
        $tokenEnvio = $tokenPedido;
    } else {
        $tokenEnvio = bin2hex(random_bytes(16));
        $_SESSION['envio_tokens'][$tokenEnvio] = $id;
        if (count($_SESSION['envio_tokens']) > 20) {
            array_shift($_SESSION['envio_tokens']); // se descartan los mas viejos
        }
    }
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

        <?php if ($gracias): ?>
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
                        <div class="fw-semibold">
                            <?= esc($e['titulo']) ?>
                            <?php if (!empty($e['servicio'])): ?>
                                <span class="badge bg-secondary ms-2"><?= esc($e['servicio']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($e['descripcion'])): ?>
                            <div class="small text-muted"><?= esc($e['descripcion']) ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($encuestas) === 0 && count($errores) === 0): ?>
                    <div class="list-group-item text-muted">No se encontraron encuestas.</div>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <h2 class="h5 mb-1"><?= esc($encuesta['titulo']) ?></h2>
            <?php if (!empty($encuesta['descripcion'])): ?>
                <p class="mb-1"><?= esc($encuesta['descripcion']) ?></p>
            <?php endif; ?>
            <p class="text-muted small">
                Esta encuesta es anónima: no te pedimos ningún dato personal. Las preguntas con * son obligatorias.
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
                        <input type="hidden" name="token_envio" value="<?= esc($tokenEnvio) ?>">

                        <?php require __DIR__ . '/presentacion/encuesta_campos.php'; ?>

                        <?php if ($usaRecaptcha): ?>
                            <div class="mb-3">
                                <?= Recaptcha::casilla() ?>
                                <div class="text-danger small mt-1" id="error-recaptcha"></div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Enviar respuestas</button>
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
