<?php
// Documentos para pacientes (publico). Se busca por titulo y se filtra por servicio.
// Solo aparecen los documentos activos y publicados de servicios activos.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/datos/DocumentoDAO.php';
require_once __DIR__ . '/datos/ServicioDAO.php';

function esc($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

$busqueda   = trim($_GET['q'] ?? '');
$idServicio = (int) ($_GET['servicio'] ?? 0);
$documentos = [];
$servicios  = [];
$error = '';

try {
    $servicios  = (new ServicioDAO())->listarActivos();
    $documentos = (new DocumentoDAO())->listarPublicos($busqueda, $idServicio);
} catch (PDOException $ex) {
    $error = 'No se pudo cargar el material en este momento.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - <?= esc(SIGLAS_SISTEMA) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="presentacion/css/estilos.css">
</head>
<body>
    <div class="bg-primary text-white py-4 cabecera-publica">
        <div class="container">
            <h1 class="h3 mb-1">Documentos e indicaciones</h1>
            <p class="mb-0"><?= esc(SUBTITULO_SISTEMA) ?></p>
        </div>
    </div>

    <div class="container py-4" style="max-width: 800px;">
        <p><a href="portal.php"><i class="bi bi-arrow-left"></i> Volver a la portada</a></p>

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
                <a href="documentos_publicos.php" class="btn btn-outline-secondary flex-fill">Limpiar</a>
            </div>
        </form>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php elseif (count($documentos) === 0): ?>
            <div class="alert alert-secondary">No se encontraron documentos.</div>
        <?php endif; ?>

        <div class="list-group">
            <?php foreach ($documentos as $d): ?>
                <div class="list-group-item">
                    <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold"><?= esc($d['titulo']) ?></div>
                            <span class="badge bg-secondary"><?= esc($d['servicio']) ?></span>
                            <?php if (!empty($d['descripcion'])): ?>
                                <div class="small text-muted mt-1"><?= esc($d['descripcion']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-nowrap">
                            <a href="documento_abrir.php?id=<?= (int) $d['id_documento'] ?>" target="_blank" rel="noopener"
                               class="btn btn-primary btn-sm">Abrir</a>
                            <a href="descargar.php?id=<?= (int) $d['id_documento'] ?>"
                               class="btn btn-outline-secondary btn-sm">Descargar</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-primary mt-4 mb-0">
            ¿Te atendiste en el hospital? Contanos cómo fue tu experiencia:
            <a href="encuesta.php">responder la encuesta de satisfacción</a>.
        </div>
    </div>
</body>
</html>
