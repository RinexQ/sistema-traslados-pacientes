<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Servicio.php';
require_once __DIR__ . '/../datos/ServicioDAO.php';

// Alta y edicion de servicios (solo administrador)
Auth::exigirAdministrador();
$dao = new ServicioDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$esEdicion = $id !== null;
$nombre = '';
$errores = [];

if ($esEdicion) {
    $existente = $dao->obtenerPorId($id);
    if ($existente === null) {
        header('Location: servicios.php?msg=error');
        exit;
    }
    $nombre = $existente['nombre'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();
    $nombre = trim($_POST['nombre'] ?? '');
    $errores = Servicio::validarNombre($nombre);

    try {
        // Control de nombres duplicados
        if (count($errores) === 0 && $dao->existeNombre($nombre, $esEdicion ? $id : 0)) {
            $errores[] = 'Ya existe un servicio con ese nombre.';
        }
        if (count($errores) === 0) {
            if ($esEdicion) {
                $dao->actualizar($id, $nombre);
                header('Location: servicios.php?msg=editado');
            } else {
                $dao->crear($nombre);
                header('Location: servicios.php?msg=creado');
            }
            exit;
        }
    } catch (PDOException $ex) {
        // Si dos personas crean el mismo nombre a la vez, la restriccion UNIQUE de la base lo frena
        $errores[] = 'No se pudo guardar el servicio. Revisá que el nombre no esté repetido.';
    }
}

$tituloPagina = $esEdicion ? 'Editar servicio' : 'Nuevo servicio';
$paginaActiva = 'servicios';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2><?= $esEdicion ? 'Editar servicio' : 'Nuevo servicio' ?></h2>
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

            <div class="card shadow-sm" style="max-width: 600px;">
                <div class="card-body d-block">
                    <form method="post" id="form-servicio" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nombre">Nombre del servicio</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100"
                                   value="<?= e($nombre) ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Crear servicio' ?></button>
                        <a href="servicios.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
