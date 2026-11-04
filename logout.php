<?php
require_once __DIR__ . '/negocio/Auth.php';
require_once __DIR__ . '/negocio/Auditoria.php';

if (Auth::haySesion()) {
    $u = Auth::usuarioActual();
    Auditoria::registrar('logout', 'sesion', (int) $u['identificador'], 'Cierre de sesión de ' . $u['nombre']);
}
Auth::cerrarSesion();
header('Location: ' . Auth::url('login.php'));
exit;
