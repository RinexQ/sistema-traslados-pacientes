<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Documento.php';
require_once __DIR__ . '/../negocio/ArchivoPdf.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/DocumentoDAO.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

// Solo el administrador carga y edita documentos
Auth::exigirAdministrador();
$usuario = Auth::usuarioActual();
$dao = new DocumentoDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$esEdicion = $id !== null;

$titulo = '';
$descripcion = '';
$idServicio = 0;
$publicado = true;
$historial = [];
$errores = [];
$existente = null;

// Si es edicion, cargamos los datos que ya tiene el documento
if ($esEdicion) {
    $existente = $dao->obtenerPorId($id);
    if ($existente === null || (int) $existente['activo'] !== 1) {
        // un documento dado de baja primero hay que reactivarlo
        header('Location: documentos_listar.php?msg=error');
        exit;
    }
    $titulo = $existente['titulo'];
    $descripcion = (string) $existente['descripcion'];
    $idServicio = (int) $existente['id_servicio'];
    $publicado = (int) $existente['visible'] === 1;
    $historial = $dao->historial($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si el archivo supera post_max_size, PHP descarta todo el formulario
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errores[] = 'El archivo supera el tamaño máximo permitido por el servidor.';
    } else {
        Csrf::exigir();

        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $idServicio = (int) ($_POST['id_servicio'] ?? 0);
        $publicado = isset($_POST['publicado']);

        $documento = new Documento($id, $titulo, $descripcion, $idServicio, $publicado);
        $errores = $documento->validar();

        $archivo = $_FILES['pdf'] ?? null;
        $hayArchivo = $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        // Al crear el PDF es obligatorio; al editar solo si se quiere reemplazar
        $errores = array_merge($errores, ArchivoPdf::validar($archivo, !$esEdicion));

        // El servicio tiene que existir y estar activo (o ser el que el documento ya tenia)
        if ($idServicio > 0) {
            $serv = (new ServicioDAO())->obtenerPorId($idServicio);
            $mismoQueAntes = $esEdicion && $idServicio === (int) $existente['id_servicio'];
            if ($serv === null || ((int) $serv['activo'] !== 1 && !$mismoQueAntes)) {
                $errores[] = 'El servicio elegido no está disponible.';
            }
        }

        if (count($errores) === 0) {
            $rutaNueva = null;
            try {
                if ($hayArchivo) {
                    $rutaNueva = ArchivoPdf::guardar($archivo);
                }
                if ($esEdicion) {
                    $rutaVieja = $dao->actualizar($id, $documento->titulo, $documento->descripcion,
                        $idServicio, $publicado, $rutaNueva, (int) $usuario['identificador']);
                    if ($rutaVieja !== null) {
                        ArchivoPdf::eliminar($rutaVieja); // el PDF reemplazado se borra del servidor
                    }
                    Auditoria::registrar('documento_edicion', 'documento', $id,
                        'Documento editado: «' . $documento->titulo . '»' . ($rutaVieja !== null ? ' (PDF reemplazado)' : ''));
                    header('Location: documentos_listar.php?msg=editado');
                } else {
                    $nuevoId = $dao->crear($documento->titulo, $documento->descripcion, $rutaNueva,
                        (int) $usuario['identificador'], $idServicio, $publicado);
                    Auditoria::registrar('documento_alta', 'documento', $nuevoId, 'Documento cargado: «' . $documento->titulo . '»');
                    header('Location: documentos_listar.php?msg=creado');
                }
                exit;
            } catch (Throwable $ex) {
                // Si fallo la base, no dejamos el PDF huerfano en el servidor
                if ($rutaNueva !== null) {
                    ArchivoPdf::eliminar($rutaNueva);
                }
                $errores[] = 'No se pudo guardar el documento. Intentá de nuevo.';
            }
        }
        $titulo = $documento->titulo;
        $descripcion = $documento->descripcion;
    }
}

// Servicios activos; al editar tambien el que ya tiene el documento (aunque este inactivo)
$servicios = (new ServicioDAO())->listarTodos('', 'activos');
if ($esEdicion && (int) $existente['servicio_activo'] === 0) {
    $servicios[] = ['id_servicio' => $existente['id_servicio'], 'nombre' => $existente['servicio'] . ' (inactivo)'];
}

$nombresAccion = [
    'alta' => 'Alta', 'edicion' => 'Edición', 'reemplazo' => 'Reemplazo de PDF',
    'baja' => 'Baja', 'reactivacion' => 'Reactivación',
];

$tituloPagina = $esEdicion ? 'Editar documento' : 'Nuevo documento';
$paginaActiva = 'documentos';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2><?= $esEdicion ? 'Editar documento' : 'Nuevo documento' ?></h2>
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

            <div class="card shadow-sm" style="max-width: 650px;">
                <div class="card-body d-block">
                    <form method="post" id="form-documento" enctype="multipart/form-data" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="titulo">Título</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="200"
                                   value="<?= e($titulo) ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="descripcion">Descripción (opcional)</label>
                            <textarea id="descripcion" name="descripcion" class="form-control" rows="3"
                                      maxlength="500"><?= e($descripcion) ?></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="id_servicio">Servicio</label>
                            <select id="id_servicio" name="id_servicio" class="form-select" required>
                                <option value="">-- Elegí un servicio --</option>
                                <?php foreach ($servicios as $serv): ?>
                                    <option value="<?= (int) $serv['id_servicio'] ?>"
                                        <?= (int) $serv['id_servicio'] === $idServicio ? 'selected' : '' ?>>
                                        <?= e($serv['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pdf">
                                <?= $esEdicion ? 'Reemplazar el PDF (opcional)' : 'Archivo PDF' ?>
                            </label>
                            <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf,.pdf"
                                   <?= $esEdicion ? '' : 'required' ?>>
                            <div class="form-text">Solo PDF, hasta 5 MB.</div>
                            <div class="invalid-feedback"></div>
                            <?php if ($esEdicion): ?>
                                <div class="form-text">
                                    PDF actual:
                                    <a href="<?= e(Auth::url('documento_abrir.php')) ?>?id=<?= (int) $id ?>" target="_blank" rel="noopener">abrir</a>.
                                    Si elegís uno nuevo, el anterior se elimina del servidor.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="publicado" name="publicado"
                                   <?= $publicado ? 'checked' : '' ?>>
                            <label class="form-check-label" for="publicado">Publicado (si se desmarca queda oculto y solo lo ve el administrador)</label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?= $esEdicion ? 'Guardar cambios' : 'Cargar documento' ?>
                        </button>
                        <a href="documentos_listar.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

            <?php if ($esEdicion): ?>
            <div class="card shadow-sm mt-4" style="max-width: 650px;">
                <div class="card-header bg-white"><h5 class="mb-0">Historial de modificaciones</h5></div>
                <div class="list-group list-group-flush">
                    <?php if (count($historial) === 0): ?>
                        <div class="list-group-item text-muted">Sin registros.</div>
                    <?php endif; ?>
                    <?php foreach ($historial as $h): ?>
                        <div class="list-group-item">
                            <span class="badge bg-secondary"><?= e($nombresAccion[$h['accion']] ?? $h['accion']) ?></span>
                            <?= e($h['detalle']) ?>
                            <div class="small text-muted">
                                <?= e(date('d/m/Y H:i', strtotime($h['fecha']))) ?> · <?= e($h['admin_nombre'] ?? 'Usuario eliminado') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
