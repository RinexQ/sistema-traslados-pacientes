<?php
// Capa de NEGOCIO
// Sesiones y permisos. Hay dos roles:
//   - administrador: gestiona documentos, servicios, usuarios y encuestas.
//   - usuario (basico): consulta documentos y puede cargar solicitudes de traslado.
// El material general y las encuestas son publicos y no pasan por aca.

require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/../datos/UsuarioDAO.php';

class Auth {

    public static function iniciar(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // La cookie de sesion no se puede leer desde JavaScript ni se envia desde otros sitios
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // Ruta de la carpeta del proyecto dentro del servidor web (vacia si esta en la raiz)
    public static function base(): string {
        $raiz = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
        $doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($doc !== '' && stripos($raiz, $doc) === 0) {
            return rtrim(substr($raiz, strlen($doc)), '/');
        }
        return '';
    }

    public static function url(string $ruta): string {
        return self::base() . '/' . ltrim($ruta, '/');
    }

    public static function guardarUsuario(Usuario $usuario): void {
        self::iniciar();
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']); // token nuevo despues de iniciar sesion
        $_SESSION['usuario'] = [
            'identificador' => $usuario->identificador,
            'nombre'        => $usuario->nombre,
            'email'         => $usuario->email,
            'rol'           => $usuario->rol,
        ];
    }

    public static function haySesion(): bool {
        self::iniciar();
        return isset($_SESSION['usuario']);
    }

    public static function usuarioActual(): ?array {
        self::iniciar();
        return $_SESSION['usuario'] ?? null;
    }

    public static function esAdministrador(): bool {
        $u = self::usuarioActual();
        return $u !== null && $u['rol'] === 'administrador';
    }

    // Cualquier cuenta con sesion iniciada (administrador o usuario basico)
    public static function esPersonal(): bool {
        return self::usuarioActual() !== null;
    }

    // Pide sesion iniciada. Tambien comprueba en la base que la cuenta siga activa
    // y actualiza el rol (si se dio de baja o se cambio el rol mientras estaba conectado).
    public static function exigirSesion(): void {
        self::iniciar();

        // Cerrar sesion: cualquier pagina protegida con ?salir=1
        if (isset($_GET['salir'])) {
            self::cerrarSesion();
            header('Location: ' . self::url('login.php'));
            exit;
        }

        if (!self::haySesion()) {
            header('Location: ' . self::url('login.php'));
            exit;
        }

        try {
            $estado = (new UsuarioDAO())->obtenerEstadoSesion((int) $_SESSION['usuario']['identificador']);
        } catch (PDOException $ex) {
            $estado = ['activo' => 1, 'rol' => $_SESSION['usuario']['rol']];
        }

        if ($estado === null || (int) $estado['activo'] !== 1) {
            self::cerrarSesion();
            header('Location: ' . self::url('login.php?aviso=baja'));
            exit;
        }
        $_SESSION['usuario']['rol'] = $estado['rol'];
    }

    public static function exigirPersonal(): void {
        self::exigirSesion();
    }

    // Los usuarios basicos que entran a una pagina de administrador vuelven al listado de documentos
    public static function exigirAdministrador(): void {
        self::exigirSesion();
        if (!self::esAdministrador()) {
            header('Location: ' . self::url('presentacion/documentos_listar.php'));
            exit;
        }
    }

    public static function cerrarSesion(): void {
        self::iniciar();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
