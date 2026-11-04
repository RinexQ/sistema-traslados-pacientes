<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Encuesta.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/EncuestaDAO.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

// Datos generales de una encuesta (titulo, descripcion y servicio): solo el administrador
Auth::exigirAdministrador();
$usuario = Auth::usuarioActual();
$dao = new EncuestaDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$esEdicion = $id !== null;

$titulo = '';
$descripcion = '';
$idServicio = 0;
$errores = [];
$existente = null;

if ($esEdicion) {
    $existente = $dao->obtener($id);
    if ($existente === null) {
        header('Location: encuestas.php?msg=error');
        exit;
    }
    // si ya tiene respuestas o esta cerrada, no se puede modificar: hay que crear una version nueva
    if (!$dao->esEditable($existente)) {
        header('Location: encuestas.php?msg=noeditable');
        exit;
    }
    $titulo = $existente['titulo'];
    $descripcion = (string) $existente['descripcion'];
    $idServicio = (int) $existente['id_servicio'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $idServicio = (int) ($_POST['id_servicio'] ?? 0);

    $errores = Encuesta::validarDatos($titulo, $descripcion);

    try {
        // el servicio es opcional (sin servicio = encuesta general); si se elige, tiene que estar disponible
        if ($idServicio > 0) {
            $serv = (new ServicioDAO())->obtenerPorId($idServicio);
            $mismoQueAntes = $esEdicion && $idServicio === (int) $existente['id_servicio'];
            if ($serv === null || ((int) $serv['activo'] !== 1 && !$mismoQueAntes)) {
                $errores[] = 'El servicio elegido no está disponible.';
            }
        }

        if (count($errores) === 0) {
            if ($esEdicion) {
                $dao->actualizarDatos($id, $titulo, $descripcion, $idServicio);
                Auditoria::registrar('encuesta_edicion', 'encuesta', $id, 'Datos de la encuesta «' . $titulo . '» modificados');
                header('Location: encuesta_preguntas.php?id=' . $id . '&msg=editada');
            } else {
                $nuevo = $dao->crear($titulo, $descripcion, $idServicio, (int) $usuario['identificador']);
                Auditoria::registrar('encuesta_alta', 'encuesta', $nuevo, 'Encuesta creada como borrador: «' . $titulo . '»');
                // se va directo a cargar las preguntas
                header('Location: encuesta_preguntas.php?id=' . $nuevo . '&msg=creada');
            }
            exit;
        }
    } catch (PDOException $ex) {
        $errores[] = 'No se pudo guardar la encuesta. Intentá de nuevo.';
    }
}

// Servicios activos; al editar tambien el que ya tiene (aunque este inactivo)
$servicios = (new ServicioDAO())->listarTodos('', 'activos');
if ($esEdicion && $existente['id_servicio'] !== null && (int) $existente['servicio_activo'] === 0) {
    $servicios[] = ['id_servicio' => $existente['id_servicio'], 'nombre' => $existente['servicio'] . ' (inactivo)'];
}

$tituloPagina = $esEdicion ? 'Editar encuesta' : 'Nueva encuesta';
$paginaActiva = 'encuestas';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2><?= $esEdicion ? 'Editar datos de la encuesta' : 'Nueva encuesta' ?></h2>
                <?php if (!$esEdicion): ?>
                    <p class="text-muted">Primero se completan los datos generales y después se agregan las preguntas. La encuesta queda como borrador hasta que la publiques.</p>
                <?php endif; ?>
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
                    <form method="post" id="form-encuesta-datos" novalidate>
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
                            <select id="id_servicio" name="id_servicio" class="form-select">
                                <option value="0">General (no pertenece a un servicio)</option>
                                <?php foreach ($servicios as $serv): ?>
                                    <option value="<?= (int) $serv['id_servicio'] ?>" <?= (int) $serv['id_servicio'] === $idServicio ? 'selected' : '' ?>>
                                        <?= e($serv['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Crear y agregar preguntas' ?></button>
                        <a href="<?= $esEdicion ? 'encuesta_preguntas.php?id=' . (int) $id : 'encuestas.php' ?>" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
