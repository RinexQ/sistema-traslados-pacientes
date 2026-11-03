<?php
// Capa de NEGOCIO
// Verificacion "No soy un robot" de Google reCAPTCHA v2 (casilla).
// El navegador muestra la casilla y el servidor le pregunta a Google si la
// respuesta es valida. Si las claves no estan cargadas en config_captcha.php,
// activo() devuelve false y la encuesta funciona sin verificacion.

require_once __DIR__ . '/../config_captcha.php';

class Recaptcha {

    public static function activo(): bool {
        return RECAPTCHA_SITE_KEY !== '' && RECAPTCHA_SECRET_KEY !== '';
    }

    // Casilla que se muestra dentro del formulario
    public static function casilla(): string {
        return '<div class="g-recaptcha" data-sitekey="'
            . htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') . '"></div>';
    }

    // Script de Google (se pone una vez en la pagina)
    public static function script(): string {
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    // Le pregunta a Google si la casilla fue resuelta por una persona
    public static function verificar(string $respuesta): bool {
        if ($respuesta === '' || !self::activo()) {
            return false;
        }

        $datos = http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $respuesta,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $resultado = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $datos,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
            ]);
            $resultado = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $contexto = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $datos,
                'timeout' => 8,
            ]]);
            $resultado = @file_get_contents($url, false, $contexto);
        }

        $json = json_decode((string) $resultado, true);
        return is_array($json) && !empty($json['success']);
    }
}
