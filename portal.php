<?php
// Portada del portal publico. Es la pagina que abre el codigo QR.
// No pide sesion a proposito: desde aca cualquiera llega a los documentos y a las encuestas.
require_once __DIR__ . '/config.php';

function esc($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal para pacientes - <?= esc(SIGLAS_SISTEMA) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="presentacion/css/estilos.css">
</head>
<body>
    <div class="bg-primary text-white py-5 cabecera-publica">
        <div class="container text-center">
            <h1 class="h2 mb-2"><i class="bi bi-hospital"></i> <?= esc(SUBTITULO_SISTEMA) ?></h1>
            <p class="mb-0 fs-5">Portal para pacientes y familiares</p>
        </div>
    </div>

    <div class="container py-4" style="max-width: 800px;">
        <p class="text-center text-muted mb-4">Elegí lo que querés consultar:</p>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <a href="documentos_publicos.php" class="btn btn-primary w-100 py-4 fs-5">
                    <i class="bi bi-file-earmark-text d-block fs-1 mb-2"></i>
                    Documentos e indicaciones
                </a>
            </div>
            <div class="col-12 col-md-6">
                <a href="encuesta.php" class="btn btn-success w-100 py-4 fs-5">
                    <i class="bi bi-chat-square-heart d-block fs-1 mb-2"></i>
                    Encuestas de satisfacción
                </a>
            </div>
        </div>

        <p class="text-center mt-5">
            <a href="login.php">Ingreso para el personal</a>
        </p>
    </div>
</body>
</html>
