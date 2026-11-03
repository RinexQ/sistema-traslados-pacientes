<?php
// Capa de NEGOCIO
// Proteccion CSRF: cada formulario que modifica datos lleva un token oculto.
// El token se guarda en la sesion y se compara cuando llega el POST, asi otro
// sitio no puede enviar formularios en nombre del usuario.

require_once __DIR__ . '/Auth.php';

class Csrf {

    private const CLAVE = 'csrf_token';

    // Devuelve el token de la sesion (lo crea si todavia no existe)
    public static function token(): string {
        Auth::iniciar();
        if (empty($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CLAVE];
    }

    // HTML del campo oculto que se pone dentro de cada <form method="post">
    public static function campo(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function valido($recibido): bool {
        Auth::iniciar();
        $esperado = $_SESSION[self::CLAVE] ?? '';
        return is_string($recibido) && $esperado !== '' && hash_equals($esperado, $recibido);
    }

    // Corta la ejecucion si el POST no trae un token valido
    public static function exigir(): void {
        if (!self::valido($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'El formulario no es válido o la sesión expiró. Volvé atrás, recargá la página y probá de nuevo.';
            exit;
        }
    }
}
