<?php
// Muestra las preguntas de una encuesta. Se usa en la pagina publica y en la vista previa.
// Variables: $preguntas (con sus opciones), $valores (respuestas ya elegidas, para no perderlas
// si hay un error) y $deshabilitado (true en la vista previa).
$h = function ($texto): string {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};
$valores = $valores ?? [];
$deshabilitado = $deshabilitado ?? false;
$atributoOff = $deshabilitado ? 'disabled' : '';
$etiquetasValor = [1 => 'Muy mala', 2 => 'Mala', 3 => 'Regular', 4 => 'Buena', 5 => 'Excelente'];
?>
<?php foreach ($preguntas as $i => $p): ?>
    <?php
    $id = (int) $p['id_pregunta'];
    $elegido = $valores[$id] ?? null;
    $obligatoria = (int) $p['obligatoria'] === 1;
    ?>
    <div class="pregunta mb-4" data-tipo="<?= $h($p['tipo']) ?>" data-obligatoria="<?= $obligatoria ? '1' : '0' ?>">
        <label class="form-label fw-semibold d-block">
            <?= (int) ($i + 1) ?>. <?= $h($p['texto']) ?>
            <?php if ($obligatoria): ?>
                <span class="text-danger" title="Obligatoria">*</span>
            <?php else: ?>
                <small class="text-muted fw-normal">(opcional)</small>
            <?php endif; ?>
        </label>

        <?php if ($p['tipo'] === 'unica'): ?>
            <?php foreach ($p['opciones'] as $o): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="p[<?= $id ?>]"
                           id="p<?= $id ?>o<?= (int) $o['id_opcion'] ?>" value="<?= (int) $o['id_opcion'] ?>"
                           <?= (string) $elegido === (string) $o['id_opcion'] ? 'checked' : '' ?> <?= $atributoOff ?>>
                    <label class="form-check-label" for="p<?= $id ?>o<?= (int) $o['id_opcion'] ?>"><?= $h($o['texto']) ?></label>
                </div>
            <?php endforeach; ?>

        <?php elseif ($p['tipo'] === 'multiple'): ?>
            <?php $marcadas = is_array($elegido) ? array_map('strval', $elegido) : []; ?>
            <?php foreach ($p['opciones'] as $o): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="p[<?= $id ?>][]"
                           id="p<?= $id ?>o<?= (int) $o['id_opcion'] ?>" value="<?= (int) $o['id_opcion'] ?>"
                           <?= in_array((string) $o['id_opcion'], $marcadas, true) ? 'checked' : '' ?> <?= $atributoOff ?>>
                    <label class="form-check-label" for="p<?= $id ?>o<?= (int) $o['id_opcion'] ?>"><?= $h($o['texto']) ?></label>
                </div>
            <?php endforeach; ?>

        <?php elseif ($p['tipo'] === 'valoracion'): ?>
            <?php for ($n = 1; $n <= 5; $n++): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="p[<?= $id ?>]"
                           id="p<?= $id ?>v<?= $n ?>" value="<?= $n ?>"
                           <?= (string) $elegido === (string) $n ? 'checked' : '' ?> <?= $atributoOff ?>>
                    <label class="form-check-label" for="p<?= $id ?>v<?= $n ?>"><?= $n ?> - <?= $h($etiquetasValor[$n]) ?></label>
                </div>
            <?php endfor; ?>

        <?php else: ?>
            <textarea class="form-control" name="p[<?= $id ?>]" rows="3" maxlength="500"
                      placeholder="Escribí tu respuesta (máximo 500 caracteres)" <?= $atributoOff ?>><?= $h(is_string($elegido) ? $elegido : '') ?></textarea>
        <?php endif; ?>

        <div class="text-danger small mt-1 error-pregunta"></div>
    </div>
<?php endforeach; ?>
