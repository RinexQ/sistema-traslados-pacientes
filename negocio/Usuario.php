<?php
// Capa de NEGOCIO
// Representa a la persona que inicia sesion, valida sus credenciales y
// valida los datos del formulario de usuarios. Se identifican con la cedula.

require_once __DIR__ . '/../datos/UsuarioDAO.php';

class Usuario {

    // Los dos roles del sistema (valor guardado en la base => nombre para mostrar)
    public const ROLES = [
        'administrador' => 'Administrador',
        'usuario'       => 'Usuario básico',
    ];

    public string $identificador; // id_admin
    public string $nombre;
    public string $email;
    public string $rol; // 'administrador' o 'usuario'

    public function __construct(string $identificador, string $nombre, string $email, string $rol) {
        $this->identificador = $identificador;
        $this->nombre        = $nombre;
        $this->email         = $email;
        $this->rol           = $rol;
    }

    // Devuelve un Usuario si la cedula y la clave son correctas y la cuenta esta activa, o null.
    // password_verify compara la clave con el hash guardado (nunca se guarda la clave en texto).
    public static function autenticar(string $cedula, string $passwordPlano): ?Usuario {
        $dao = new UsuarioDAO();
        $fila = $dao->buscarAdministrativoPorCi($cedula);

        if ($fila
            && (int) $fila['activo'] === 1
            && $fila['password_hash'] !== ''
            && password_verify($passwordPlano, $fila['password_hash'])) {
            $rol = isset(self::ROLES[$fila['rol']]) ? $fila['rol'] : 'usuario';
            return new Usuario((string) $fila['id_admin'], $fila['nombre'], $fila['email'], $rol);
        }
        return null;
    }

    // Reglas de contrasena: de 8 a 72 caracteres, con al menos una letra y un numero
    public static function validarPassword(string $password): array {
        $errores = [];
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (strlen($password) > 72) {
            $errores[] = 'La contraseña no puede superar los 72 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errores[] = 'La contraseña debe incluir al menos una letra y un número.';
        }
        return $errores;
    }

    // Valida los datos del formulario de usuarios.
    // En el alta la contrasena es obligatoria; al editar solo se valida si se escribio una nueva.
    public static function validarDatos(array $d, bool $esAlta): array {
        $errores = [];

        $nombre   = trim($d['nombre'] ?? '');
        $ci       = trim($d['ci'] ?? '');
        $email    = trim($d['email'] ?? '');
        $rol      = $d['rol'] ?? '';
        $password = $d['password'] ?? '';

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores[] = 'El nombre no puede superar los 100 caracteres.';
        }

        if (!preg_match('/^\d{7,8}$/', $ci)) {
            $errores[] = 'La cédula debe tener 7 u 8 dígitos, sin puntos ni guión.';
        }

        if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingresá un email válido (hasta 150 caracteres).';
        }

        if (!isset(self::ROLES[$rol])) {
            $errores[] = 'Elegí un rol válido.';
        }

        if ($esAlta || $password !== '') {
            $errores = array_merge($errores, self::validarPassword($password));
        }

        return $errores;
    }
}
