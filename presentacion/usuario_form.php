<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Usuario.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

// Alta y edicion de usuarios (solo administrador)
Auth::exigirAdministrador();
$dao = new UsuarioDAO();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$esEdicion = $id !== null;

$datos = ['nombre' => '', 'ci' => '', 'email' => '', 'rol' => 'usuario'];
$errores = [];
$existente = null;

if ($esEdicion) {
    $existente = $dao->obtenerPorId($id);
    if ($existente === null) {
        header('Location: usuarios.php?msg=error');
        exit;
    }
    $datos = [
        'nombre' => $existente['nombre'],
        'ci'     => $existente['ci'],
        'email'  => $existente['email'],
        'rol'    => $existente['rol'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();

    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'ci'     => trim($_POST['ci'] ?? ''),
        'email'  => trim($_POST['email'] ?? ''),
        'rol'    => $_POST['rol'] ?? '',
    ];
    $password = $_POST['password'] ?? '';

    $errores = Usuario::validarDatos($datos + ['password' => $password], !$esEdicion);

    try {
        if (count($errores) === 0) {
            $excluir = $esEdicion ? $id : 0;
            if ($dao->existeCi($datos['ci'], $excluir)) {
                $errores[] = 'Ya existe un usuario con esa cédula.';
            }
            if ($dao->existeEmail($datos['email'], $excluir)) {
                $errores[] = 'Ya existe un usuario con ese email.';
            }
            // No dejar el sistema sin administradores
            if ($esEdicion && $existente['rol'] === 'administrador' && $datos['rol'] !== 'administrador'
                && (int) $existente['activo'] === 1 && $dao->contarAdministradoresActivos() <= 1) {
                $errores[] = 'No se puede quitar el rol al último administrador activo.';
            }
        }

        if (count($errores) === 0) {
            $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
            if ($esEdicion) {
                $dao->actualizar($id, $datos['nombre'], $datos['ci'], $datos['email'], $datos['rol'], $hash);
                header('Location: usuarios.php?msg=editado');
            } else {
                $dao->crear($datos['nombre'], $datos['ci'], $datos['email'], $datos['rol'], $hash);
                header('Location: usuarios.php?msg=creado');
            }
            exit;
        }
    } catch (PDOException $ex) {
        $errores[] = 'No se pudo guardar el usuario. Intentá de nuevo.';
    }
}

$tituloPagina = $esEdicion ? 'Editar usuario' : 'Nuevo usuario';
$paginaActiva = 'usuarios';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
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
                    <form method="post" id="form-usuario" data-edicion="<?= $esEdicion ? '1' : '0' ?>" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100"
                                   value="<?= e($datos['nombre']) ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ci">Cédula</label>
                            <input type="text" id="ci" name="ci" class="form-control" maxlength="8" inputmode="numeric"
                                   value="<?= e($datos['ci']) ?>" required>
                            <div class="form-text">Sin puntos ni guión. Es el usuario con el que inicia sesión.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" maxlength="150"
                                   value="<?= e($datos['email']) ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="rol">Rol</label>
                            <select id="rol" name="rol" class="form-select" required>
                                <?php foreach (Usuario::ROLES as $valor => $nombre): ?>
                                    <option value="<?= e($valor) ?>" <?= $datos['rol'] === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">El administrador gestiona todo; el usuario básico consulta documentos y carga solicitudes.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password"><?= $esEdicion ? 'Contraseña nueva (opcional)' : 'Contraseña' ?></label>
                            <input type="password" id="password" name="password" class="form-control" maxlength="72"
                                   autocomplete="new-password" <?= $esEdicion ? '' : 'required' ?>>
                            <div class="form-text">
                                Mínimo 8 caracteres, con al menos una letra y un número.
                                <?= $esEdicion ? 'Dejala vacía para no cambiarla.' : '' ?>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?></button>
                        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
