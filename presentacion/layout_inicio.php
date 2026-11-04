<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../negocio/Auth.php';

if (!function_exists('e')) {
    function e($texto): string {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}

$usuarioLayout = Auth::usuarioActual();

// Nombres para mostrar de cada rol (en la base se guardan en minuscula)
$nombresRol = [
    'administrador' => 'Administrador',
    'usuario'       => 'Usuario básico',
];
$esAdminLayout = Auth::esAdministrador();

// El menu cambia segun el rol del usuario
$menu = [];
if ($esAdminLayout) {
    $menu[] = ['index', 'index.php', 'bi-speedometer2', 'Dashboard'];
}
$menu[] = ['solicitudes', 'solicitudes.php', 'bi-card-checklist', 'Solicitudes'];
if ($esAdminLayout) {
    $menu[] = ['ambulancias', 'ambulancias.php', 'bi-truck', 'Ambulancias'];
}
$menu[] = ['documentos', 'documentos_listar.php', 'bi-file-earmark-text', 'Documentación'];
if ($esAdminLayout) {
    $menu[] = ['encuestas', 'encuestas.php', 'bi-clipboard-check', 'Encuestas'];
    $menu[] = ['servicios', 'servicios.php', 'bi-diagram-3', 'Servicios'];
    $menu[] = ['usuarios', 'usuarios.php', 'bi-person-badge', 'Usuarios'];
    $menu[] = ['qr', 'qr_portal.php', 'bi-qr-code', 'Código QR'];
    $menu[] = ['auditoria', 'auditoria.php', 'bi-journal-text', 'Registro de acciones'];
}
$menu[] = ['password', 'cambiar_password.php', 'bi-key', 'Mi contraseña'];
// ?salir=1 cierra la sesion (lo atiende Auth::exigirSesion en cada pagina)
$menu[] = ['salir', '?salir=1', 'bi-box-arrow-right', 'Cerrar sesión'];

$busquedaActual = $_GET['q'] ?? '';

// Grupos del menu (los titulos chicos que separan las secciones)
$grupoMenu = [
    'index' => 'Principal', 'solicitudes' => 'Principal', 'ambulancias' => 'Principal', 'documentos' => 'Principal',
    'encuestas' => 'Administración', 'servicios' => 'Administración', 'usuarios' => 'Administración', 'qr' => 'Administración', 'auditoria' => 'Administración',
    'password' => 'Cuenta', 'salir' => 'Cuenta',
];
$nombreUsuario = $usuarioLayout['nombre'] ?? '';
$nombreRol = $nombresRol[$usuarioLayout['rol'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> - <?= e(SUBTITULO_SISTEMA) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<!-- Barra superior: solo en celular y tablet. El boton de tres rayas abre el menu -->
<header class="topbar d-lg-none">
    <button class="btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral"
            aria-controls="menuLateral" aria-label="Abrir el menú">
        <i class="bi bi-list"></i>
    </button>
    <span class="topbar-titulo"><i class="bi bi-hospital"></i> <?= e(SUBTITULO_SISTEMA) ?></span>
    <span class="topbar-seccion"><?= e($tituloPagina) ?></span>
</header>

<!-- Menu: barra lateral fija en computadora y panel deslizable (hamburguesa) en celular -->
<div class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralTitulo">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title" id="menuLateralTitulo"><i class="bi bi-hospital"></i> <?= e(SUBTITULO_SISTEMA) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral"
                aria-label="Cerrar el menú"></button>
    </div>

    <div class="offcanvas-body">
        <div class="sidebar-interno">

            <div class="marca d-none d-lg-flex">
                <i class="bi bi-hospital"></i>
                <span><?= e(SUBTITULO_SISTEMA) ?></span>
            </div>

            <!-- Usuario y buscador: solo dentro del menu del celular -->
            <div class="usuario-menu d-lg-none">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="nombre"><?= e($nombreUsuario) ?></div>
                    <div class="rol"><?= e($nombreRol) ?></div>
                </div>
            </div>
            <form method="get" action="documentos_listar.php" class="d-lg-none mb-2">
                <input class="form-control" type="search" name="q" placeholder="Buscar documentos..."
                       value="<?= e($busquedaActual) ?>">
            </form>

            <nav aria-label="Secciones del sistema">
                <ul class="nav flex-column">
                    <?php $grupoActual = ''; ?>
                    <?php foreach ($menu as $item): ?>
                        <?php $grupo = $grupoMenu[$item[0]] ?? ''; ?>
                        <?php if ($grupo !== $grupoActual): ?>
                            <?php $grupoActual = $grupo; ?>
                            <li class="menu-grupo"><?= e($grupo) ?></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $item[0] === $paginaActiva ? 'active' : '' ?> <?= $item[0] === 'salir' ? 'nav-salir' : '' ?>"
                               href="<?= e($item[1]) ?>">
                                <i class="bi <?= e($item[2]) ?>"></i>
                                <span><?= e($item[3]) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<main class="contenido">

    <!-- Barra superior de computadora: buscador y usuario -->
    <div class="barra-escritorio d-none d-lg-flex">
        <form method="get" action="documentos_listar.php">
            <input class="form-control" type="search" name="q" placeholder="Buscar documentos..."
                   value="<?= e($busquedaActual) ?>">
        </form>
        <div class="usuario-chip">
            <div class="text-end">
                <div class="fw-semibold"><?= e($nombreUsuario) ?></div>
                <div class="small text-muted"><?= e($nombreRol) ?></div>
            </div>
            <i class="bi bi-person-circle"></i>
        </div>
    </div>
