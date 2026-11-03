<?php
// Capa de DATOS
// Clase encargada exclusivamente de abrir la conexión a MySQL.
// Ninguna otra clase debe usar mysqli/PDO directo: siempre pasan por aca.

class Conexion {

    private static $host   = 'localhost';
    private static $dbname = 'traslados_pacientes';
    private static $user   = 'root';
    private static $pass   = ''; // XAMPP por defecto no tiene clave en root

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
                die('Error :' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
