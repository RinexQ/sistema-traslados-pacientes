<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../negocio/Filtros.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

// Resultados de una encuesta: cantidades, porcentajes, promedio, graficos y respuestas de texto
Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$encuesta = $id > 0 ? $dao->obtener($id) : null;
if ($encuesta === null) {
    header('Location: encuestas.php?msg=error');
    exit;
}

$desde = Filtros::fecha($_GET['desde'] ?? '');
$hasta = Filtros::fecha($_GET['hasta'] ?? '');

$errorBD = '';
$resultado = ['envios' => 0, 'preguntas' => []];
$textos = []; // ultimas respuestas de texto por pregunta
$totalTextos = [];
try {
    $resultado = $dao->resultados($id, $desde, $hasta);
    foreach ($resultado['preguntas'] as $p) {
        if ($p['tipo'] === 'texto') {
            $textos[(int) $p['id_pregunta']] = $dao->textos((int) $p['id_pregunta'], $desde, $hasta, 5, 0);
            $totalTextos[(int) $p['id_pregunta']] = $dao->contarTextos((int) $p['id_pregunta'], $desde, $hasta);
        }
    }
} catch (PDOException $ex) {
    $errorBD = 'No se pudieron cargar los resultados.';
}

// promedio general de todas las preguntas de valoracion
$suma = 0;
$cantidad = 0;
foreach ($resultado['preguntas'] as $p) {
    if ($p['tipo'] === 'valoracion') {
        foreach ($p['valores'] as $valor => $d) {
            $suma += $valor * $d['cantidad'];
            $cantidad += $d['cantidad'];
        }
    }
}
$promedioGeneral = $cantidad > 0 ? $suma / $cantidad : null;

$filtros = http_build_query(array_filter(['desde' => $desde, 'hasta' => $hasta]));
$enlaceFiltros = $filtros !== '' ? '&' . $filtros : '';
$coloresEstado = ['borrador' => 'bg-secondary', 'publicada' => 'bg-success', 'cerrada' => 'bg-dark'];

$tituloPagina = 'Resultados';
$paginaActiva = 'encuestas';
$scriptsExtra = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js', 'js/graficos.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4 d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                    <h2 class="mb-1">Resultados: <?= e($encuesta['titulo']) ?></h2>
                    <span class="badge <?= e($coloresEstado[$encuesta['estado']] ?? 'bg-secondary') ?>"><?= e(Encuesta::ESTADOS[$encuesta['estado']] ?? $encuesta['estado']) ?></span>
                    <span class="badge bg-info text-dark">Versión <?= (int) $encuesta['version'] ?></span>
                    <span class="badge bg-light text-dark border"><?= $encuesta['servicio'] === null ? 'General' : e($encuesta['servicio']) ?></span>
                </div>
                <div class="text-nowrap">
                    <a href="encuestas.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                    <a href="encuesta_exportar.php?id=<?= $id ?>&tipo=resumen<?= e($enlaceFiltros) ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-filetype-csv"></i> Exportar resumen
                    </a>
                    <a href="encuesta_exportar.php?id=<?= $id ?>&tipo=envios<?= e($enlaceFiltros) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-download"></i> Exportar respuestas (CSV)
                    </a>
                </div>
            </div>

            <?php if ($errorBD !== ''): ?>
                <div class="alert alert-danger mt-3"><?= e($errorBD) ?></div>
            <?php endif; ?>

            <form method="get" class="card card-body d-block shadow-sm mt-3">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1" for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Filtrar por fecha</button>
                        <a href="encuesta_resultados.php?id=<?= $id ?>" class="btn btn-outline-secondary flex-fill">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="row g-3 mt-1">
                <div class="col-6 col-md-4">
                    <div class="indicador">
                        <div class="indicador-icono bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                        <div><div class="indicador-valor"><?= (int) $resultado['envios'] ?></div><div class="indicador-etiqueta">Respuestas recibidas</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="indicador">
                        <div class="indicador-icono bg-warning-subtle text-warning"><i class="bi bi-star"></i></div>
                        <div><div class="indicador-valor"><?= $promedioGeneral === null ? '-' : e(number_format($promedioGeneral, 2)) ?></div><div class="indicador-etiqueta">Promedio de valoraciones</div></div>
                    </div>
                </div>
            </div>

            <?php if ($resultado['envios'] === 0): ?>
                <div class="alert alert-secondary mt-3">Todavía no hay respuestas<?= ($desde !== '' || $hasta !== '') ? ' en ese período' : '' ?>.</div>
            <?php endif; ?>

            <?php foreach ($resultado['preguntas'] as $i => $p): ?>
                <?php $idP = (int) $p['id_pregunta']; ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-1"><?= (int) ($i + 1) ?>. <?= e($p['texto']) ?></h5>
                        <span class="badge bg-primary"><?= e(Encuesta::TIPOS[$p['tipo']] ?? $p['tipo']) ?></span>
                        <span class="small text-muted"><?= (int) $p['respondieron'] ?> persona(s) respondieron esta pregunta</span>
                    </div>
                    <div class="card-body d-block">

                        <?php if ($p['tipo'] === 'unica' || $p['tipo'] === 'multiple'): ?>
                            <?php
                            $etiquetas = array_column($p['opciones'], 'texto');
                            $valoresGrafico = array_map('intval', array_column($p['opciones'], 'cantidad'));
                            ?>
                            <div class="row g-3">
                                <div class="col-12 col-lg-7">
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead><tr><th>Opción</th><th class="text-center">Cantidad</th><th class="text-center">%</th><th style="min-width:120px"></th></tr></thead>
                                            <tbody>
                                            <?php foreach ($p['opciones'] as $o): ?>
                                                <tr>
                                                    <td><?= e($o['texto']) ?></td>
                                                    <td class="text-center"><?= (int) $o['cantidad'] ?></td>
                                                    <td class="text-center"><?= e(number_format((float) $o['porcentaje'], 1, ',', '')) ?>%</td>
                                                    <td><div class="progress" style="height:10px"><div class="progress-bar" style="width: <?= e(number_format((float) $o['porcentaje'], 1, '.', '')) ?>%"></div></div></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($p['tipo'] === 'multiple'): ?>
                                        <p class="small text-muted mb-0">Cada persona puede elegir varias opciones, por eso los porcentajes pueden sumar más de 100%.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-lg-5">
                                    <div class="contenedor-grafico">
                                        <canvas class="grafico" data-etiquetas="<?= e(json_encode($etiquetas, JSON_UNESCAPED_UNICODE)) ?>"
                                                data-valores="<?= e(json_encode($valoresGrafico)) ?>"></canvas>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($p['tipo'] === 'valoracion'): ?>
                            <?php
                            $etiquetas = ['1', '2', '3', '4', '5'];
                            $valoresGrafico = [];
                            for ($n = 1; $n <= 5; $n++) { $valoresGrafico[] = (int) $p['valores'][$n]['cantidad']; }
                            ?>
                            <div class="row g-3">
                                <div class="col-12 col-lg-7">
                                    <p class="mb-2">Promedio:
                                        <strong class="fs-5"><?= $p['promedio'] === null ? '-' : e(number_format((float) $p['promedio'], 2)) ?></strong> de 5</p>
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead><tr><th>Valoración</th><th class="text-center">Cantidad</th><th class="text-center">%</th><th style="min-width:120px"></th></tr></thead>
                                            <tbody>
                                            <?php for ($n = 1; $n <= 5; $n++): ?>
                                                <?php $d = $p['valores'][$n]; ?>
                                                <tr>
                                                    <td><?= $n ?></td>
                                                    <td class="text-center"><?= (int) $d['cantidad'] ?></td>
                                                    <td class="text-center"><?= e(number_format((float) $d['porcentaje'], 1, ',', '')) ?>%</td>
                                                    <td><div class="progress" style="height:10px"><div class="progress-bar bg-warning" style="width: <?= e(number_format((float) $d['porcentaje'], 1, '.', '')) ?>%"></div></div></td>
                                                </tr>
                                            <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-5">
                                    <div class="contenedor-grafico">
                                        <canvas class="grafico" data-etiquetas="<?= e(json_encode($etiquetas)) ?>"
                                                data-valores="<?= e(json_encode($valoresGrafico)) ?>"></canvas>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <?php $lista = $textos[$idP] ?? []; $totalT = $totalTextos[$idP] ?? 0; ?>
                            <?php if (count($lista) === 0): ?>
                                <p class="text-muted mb-0">Nadie escribió una respuesta todavía.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush w-100">
                                    <?php foreach ($lista as $t): ?>
                                        <li class="list-group-item px-0">
                                            <?= e($t['texto']) ?>
                                            <div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($t['fecha']))) ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                    <?php if ($p['tipo'] === 'texto' && ($totalTextos[$idP] ?? 0) > 0): ?>
                        <div class="card-footer bg-white">
                            <a href="encuesta_textos.php?pregunta=<?= $idP ?><?= e($enlaceFiltros) ?>">
                                Ver las <?= (int) $totalTextos[$idP] ?> respuestas de texto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
