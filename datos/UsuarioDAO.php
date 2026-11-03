<?php
// Capa de DATOS
// Consultas de usuarios (tabla administrativo). Las busquedas de login son por cedula.
// Un usuario nunca se borra: la baja es logica (activo = 0).

require_once __DIR__ . '/Conexion.php';

class UsuarioDAO {

    public function buscarAdministrativoPorCi(string $ci): ?array {
        $stmt = Conexion::obtener()->prepare('SELECT * FROM administrativo WHERE ci = :ci');
        $stmt->execute([':ci' => $ci]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // Estado y rol actuales de una cuenta (se consulta en cada pagina protegida)
    public function obtenerEstadoSesion(int $id): ?array {
        $stmt = Conexion::obtener()->prepare('SELECT activo, rol FROM administrativo WHERE id_admin = :id');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT id_admin, ci, nombre, email, rol, activo FROM administrativo WHERE id_admin = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // $estado: 'activos', 'baja' o 'todos'
    public function listarAdministrativos(string $busqueda = '', string $estado = 'todos'): array {
        $sql = "SELECT id_admin, ci, nombre, email, rol, activo, (password_hash <> '') AS tiene_password
                FROM administrativo";
        $donde = [];
        $params = [];
        if ($busqueda !== '') {
            $donde[] = '(nombre LIKE :q1 OR ci LIKE :q2 OR email LIKE :q3)';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }
        if ($estado === 'activos') {
            $donde[] = 'activo = 1';
        } elseif ($estado === 'baja') {
            $donde[] = 'activo = 0';
        }
        if (count($donde) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $donde);
        }
        $sql .= ' ORDER BY nombre';
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function existeCi(string $ci, int $excluirId = 0): bool {
        $stmt = Conexion::obtener()->prepare('SELECT COUNT(*) FROM administrativo WHERE ci = :ci AND id_admin <> :id');
        $stmt->execute([':ci' => $ci, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function existeEmail(string $email, int $excluirId = 0): bool {
        $stmt = Conexion::obtener()->prepare('SELECT COUNT(*) FROM administrativo WHERE email = :email AND id_admin <> :id');
        $stmt->execute([':email' => $email, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(string $nombre, string $ci, string $email, string $rol, string $passwordHash): int {
        $pdo = Conexion::obtener();
        $stmt = $pdo->prepare(
            'INSERT INTO administrativo (nombre, ci, email, rol, password_hash, activo)
             VALUES (:nombre, :ci, :email, :rol, :hash, 1)'
        );
        $stmt->execute([':nombre' => $nombre, ':ci' => $ci, ':email' => $email, ':rol' => $rol, ':hash' => $passwordHash]);
        return (int) $pdo->lastInsertId();
    }

    // Si $passwordHash es null, la contrasena no se toca
    public function actualizar(int $id, string $nombre, string $ci, string $email, string $rol, ?string $passwordHash): void {
        $sql = 'UPDATE administrativo SET nombre = :nombre, ci = :ci, email = :email, rol = :rol';
        $params = [':nombre' => $nombre, ':ci' => $ci, ':email' => $email, ':rol' => $rol, ':id' => $id];
        if ($passwordHash !== null) {
            $sql .= ', password_hash = :hash';
            $params[':hash'] = $passwordHash;
        }
        $sql .= ' WHERE id_admin = :id';
        Conexion::obtener()->prepare($sql)->execute($params);
    }

    public function cambiarEstado(int $id, bool $activo): void {
        $stmt = Conexion::obtener()->prepare('UPDATE administrativo SET activo = :activo WHERE id_admin = :id');
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    // Para no dejar el sistema sin ningun administrador activo
    public function contarAdministradoresActivos(): int {
        return (int) Conexion::obtener()
            ->query("SELECT COUNT(*) FROM administrativo WHERE rol = 'administrador' AND activo = 1")
            ->fetchColumn();
    }

    public function obtenerHash(int $id): string {
        $stmt = Conexion::obtener()->prepare('SELECT password_hash FROM administrativo WHERE id_admin = :id');
        $stmt->execute([':id' => $id]);
        return (string) $stmt->fetchColumn();
    }

    // Pacientes registrados: se usan al cargar una solicitud de traslado.
    // Los pacientes no tienen cuenta ni inician sesion; solo son datos del sistema.
    public function listarPacientes(): array {
        return Conexion::obtener()
            ->query('SELECT ci, nombre, email FROM paciente ORDER BY nombre')
            ->fetchAll();
    }

    public function cambiarPassword(int $id, string $passwordHash): void {
        $stmt = Conexion::obtener()->prepare('UPDATE administrativo SET password_hash = :hash WHERE id_admin = :id');
        $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }
}
