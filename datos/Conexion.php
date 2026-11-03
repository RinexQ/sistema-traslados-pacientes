<?php
// Capa de DATOS
// Clase encargada exclusivamente de abrir la conexión a MySQL.
// Ninguna otra clase debe usar mysqli/PDO directo: siempre pasan por aca.

class Conexion {

    private static $host   = 'sql104.infinityfree.com'; // MySQL Hostname del panel
    private static $dbname = 'if0_43235844_traslados_pacientes'; // MySQL DB Name del panel
    private static $user   = 'if0_43235844'; // MySQL User Name del panel
    private static $pass   = 'M9vrq0XTbZZ'; // la clave de tu cuenta (vPanel)

    private static $pdo = null;

    // Devuelve siempre la misma instancia de PDO (patron singleton simple)
    public static function obtener(): PDO {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$dbname . ';charset=utf8mb4';
            try {
                self::$pdo = new PDO($dsn, self::$user, self::$pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // No mostramos el error real de MySQL al usuario final
                die('No se pudo conectar a la base de datos.');
            }
        }
        return self::$pdo;
    }
}
