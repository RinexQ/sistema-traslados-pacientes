<?php
// Claves de Google reCAPTCHA (casilla "No soy un robot", version v2).
// Se obtienen gratis en el sitio de administracion de reCAPTCHA de Google,
// registrando el dominio donde esta publicado el sistema.
//
// Mientras estas claves esten vacias, la encuesta funciona pero SIN verificacion anti-robots.
// IMPORTANTE: la clave secreta es privada. No la subas a GitHub.
define('RECAPTCHA_SITE_KEY', '6LeOJgwuAAAAAP56oIwk09ImxbtZlFkPQjmegYoO');   // clave del sitio (publica)
define('RECAPTCHA_SECRET_KEY', '6LeOJgwuAAAAAEZ55aTsM16u5p8FLQ-V_2qhi8BT'); // clave secreta (privada)
