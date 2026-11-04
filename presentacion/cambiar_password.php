<?php
require_once __DIR__ . '/../negocio/Auth.php';
require_once __DIR__ . '/../negocio/Usuario.php';
require_once __DIR__ . '/../negocio/Auditoria.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

// Cualquier usuario con sesion puede cambiar su propia contrasena
Auth::exigirSesion();
$usuario = Auth::usuarioActual();
$idUsuario = (int) $usuario['identificador'];

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::exigir();

    $actual    = $_POST['actual'] ?? '';
    $nueva     = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    try {
        $dao = new UsuarioDAO();
        if (!password_verify($actual, $dao->obtenerHash($idUsuario))) {
            $errores[] = 'La contraseña actual no es correcta.';
        }
        $errores = array_merge($errores, Usuario::validarPassword($nueva));
        if ($nueva !== $confirmar) {
            $errores[] = 'La confirmación no coincide con la contraseña nueva.';
        }
        if (count($errores) === 0) {
            // password_hash guarda la clave cifrada; nunca en texto plano
            $dao->cambiarPassword($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));
            Auditoria::registrar('password_cambio', 'usuario', $idUsuario, 'El usuario cambió su propia contraseña');
            $exito = true;
        }
    } catch (PDOException $ex) {
        $errores[] = 'No se pudo cambiar la contraseña. Intentá de nuevo.';
    }
}

$tituloPagina = 'Mi contraseña';
$paginaActiva = 'password';
$scriptsExtra = ['js/validaciones.js'];
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Cambiar mi contraseña</h2>
                <p class="text-muted">Mínimo 8 caracteres, con al menos una letra y un número.</p>
            </div>

            <?php if ($exito): ?>
                <div class="alert alert-success">La contraseña se cambió correctamente.</div>
            <?php endif; ?>

            <?php if (count($errores) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm" style="max-width: 500px;">
                <div class="card-body d-block">
                    <form method="post" id="form-password" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="actual">Contraseña actual</label>
                            <input type="password" id="actual" name="actual" class="form-control" autocomplete="current-password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="nueva">Contraseña nueva</label>
                            <input type="password" id="nueva" name="nueva" class="form-control" maxlength="72" autocomplete="new-password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="confirmar">Repetir la contraseña nueva</label>
                            <input type="password" id="confirmar" name="confirmar" class="form-control" maxlength="72" autocomplete="new-password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                    </form>
                </div>
            </div>

<?php require __DIR__ . '/layout_fin.php'; ?>
