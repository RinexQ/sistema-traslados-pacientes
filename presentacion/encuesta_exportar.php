<?php
// Exporta los resultados de una encuesta a CSV (solo administrador).
//   tipo=envios  -> una fila por persona que respondio (sin ningun dato personal)
//   tipo=resumen -> cantidades y porcentajes por pregunta y opcion
// Acepta el filtro por fecha (desde / hasta). El archivo se abre bien en Excel.
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Filtros.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';

Auth::exigirAdministrador();
$dao = new EncuestaDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tipo = Filtros::opcion($_GET['tipo'] ?? '', ['envios', 'resumen'], 'envios');
$desde = Filtros::fecha($_GET['desde'] ?? '');
$hasta = Filtros::fecha($_GET['hasta'] ?? '');

try {
    $encuesta = $id > 0 ? $dao->obtener($id) : null;
    if ($encuesta === null) {
        header('Location: encuestas.php?msg=error');
        exit;
    }
    $preguntas = $dao->preguntas($id);
    $filas = [];

    if ($tipo === 'envios') {
        $titulos = ['Número de envío', 'Fecha'];
        foreach ($preguntas as $i => $p) {
            $titulos[] = ($i + 1) . '. ' . $p['texto'];
        }
        $filas[] = $titulos;
        foreach ($dao->enviosParaExportar($id, $desde, $hasta) as $envio) {
            $fila = [$envio['id_envio'], $envio['fecha']];
            foreach ($preguntas as $p) {
                $fila[] = implode(' | ', $envio['respuestas'][(int) $p['id_pregunta']] ?? []);
            }
            $filas[] = $fila;
        }
    } else {
        $filas[] = ['Pregunta', 'Tipo', 'Opción o valor', 'Cantidad', 'Porcentaje'];
        $resultado = $dao->resultados($id, $desde, $hasta);
        foreach ($resultado['preguntas'] as $i => $p) {
            $nombre = ($i + 1) . '. ' . $p['texto'];
            if ($p['tipo'] === 'unica' || $p['tipo'] === 'multiple') {
                foreach ($p['opciones'] as $o) {
                    $filas[] = [$nombre, $p['tipo'], $o['texto'], $o['cantidad'], $o['porcentaje'] . '%'];
                }
            } elseif ($p['tipo'] === 'valoracion') {
                foreach ($p['valores'] as $valor => $d) {
                    $filas[] = [$nombre, $p['tipo'], $valor, $d['cantidad'], $d['porcentaje'] . '%'];
                }
                $filas[] = [$nombre, $p['tipo'], 'Promedio', $p['promedio'] === null ? '' : $p['promedio'], ''];
            } else {
                $filas[] = [$nombre, $p['tipo'], '(respuestas de texto)', $dao->contarTextos((int) $p['id_pregunta'], $desde, $hasta), ''];
            }
        }
    }
} catch (PDOException $ex) {
    http_response_code(500);
    echo 'No se pudo generar el archivo.';
    exit;
}

Auditoria::registrar('encuesta_exportacion', 'encuesta', $id,
    'Se exportó a CSV (' . $tipo . ') la encuesta «' . $encuesta['titulo'] . '»');

// Una celda que empieza con = + - @ se interpretaria como formula en Excel: se le antepone una comilla
function celdaSegura($valor): string {
    $texto = (string) $valor;
    if ($texto !== '' && strpos("=+-@\t\r", $texto[0]) !== false) {
        return "'" . $texto;
    }
    return $texto;
}

$nombreArchivo = 'encuesta_' . $id . '_' . $tipo . '_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('X-Content-Type-Options: nosniff');

echo "\xEF\xBB\xBF"; // marca para que Excel lea bien los acentos
$salida = fopen('php://output', 'w');
foreach ($filas as $fila) {
    fputcsv($salida, array_map('celdaSegura', $fila), ';', '"', '');
}
fclose($salida);
exit;
