<?php
// Barra de paginas que se incluye debajo de un listado.
// Necesita la variable $paginacion (objeto Paginacion). Conserva los filtros de la direccion.
if (!function_exists('e')) {
    function e($texto) { return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8'); }
}
$enlacePagina = function (int $n): string {
    $consulta = $_GET;
    $consulta['pagina'] = $n;
    return '?' . http_build_query($consulta);
};
$primeraFila = $paginacion->total === 0 ? 0 : $paginacion->desplazamiento() + 1;
$ultimaFila  = min($paginacion->total, $paginacion->desplazamiento() + $paginacion->porPagina);
$ventanaDesde = max(1, $paginacion->pagina - 2);
$ventanaHasta = min($paginacion->paginas, $paginacion->pagina + 2);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
    <div class="small text-muted">
        <?php if ($paginacion->total > 0): ?>
            Mostrando <?= (int) $primeraFila ?> a <?= (int) $ultimaFila ?> de <?= (int) $paginacion->total ?>
        <?php else: ?>
            Sin resultados
        <?php endif; ?>
    </div>

    <?php if ($paginacion->paginas > 1): ?>
    <nav aria-label="Páginas del listado">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $paginacion->pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($enlacePagina($paginacion->pagina - 1)) ?>">Anterior</a>
            </li>
            <?php for ($n = $ventanaDesde; $n <= $ventanaHasta; $n++): ?>
                <li class="page-item <?= $n === $paginacion->pagina ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($enlacePagina($n)) ?>"><?= (int) $n ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $paginacion->pagina >= $paginacion->paginas ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($enlacePagina($paginacion->pagina + 1)) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
