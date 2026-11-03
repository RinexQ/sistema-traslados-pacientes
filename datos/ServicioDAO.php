<?php
// Capa de DATOS
// Consultas de la tabla servicio (siempre con consultas preparadas).

require_once __DIR__ . '/Conexion.php';

class ServicioDAO {

    // Servicios activos: los que se ofrecen al cargar documentos y en los filtros publicos
    public function listarActivos(): array {
        return Conexion::obtener()
            ->query('SELECT id_servicio, nombre FROM servicio WHERE activo = 1 ORDER BY nombre')
            ->fetchAll();
    }

    // Todos los servicios, con la cantidad de documentos activos y de encuestas que tienen.
    // $estado: 'activos', 'baja' o 'todos'
    public function listarTodos(string $busqueda = '', string $estado = 'todos'): array {
        $sql = 'SELECT s.id_servicio, s.nombre, s.activo,
                       (SELECT COUNT(*) FROM documento d
                         WHERE d.id_servicio = s.id_servicio AND d.activo = 1) AS documentos,
                       (SELECT COUNT(*) FROM encuesta e
                         WHERE e.id_servicio = s.id_servicio) AS encuestas
                FROM servicio s';
        $donde = [];
        $params = [];
        if ($busqueda !== '') {
            $donde[] = 's.nombre LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($estado === 'activos') {
            $donde[] = 's.activo = 1';
        } elseif ($estado === 'baja') {
            $donde[] = 's.activo = 0';
        }
        if (count($donde) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $donde);
        }
        $sql .= ' ORDER BY s.nombre';
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT s.id_servicio, s.nombre, s.activo,
                    (SELECT COUNT(*) FROM documento d WHERE d.id_servicio = s.id_servicio AND d.activo = 1) AS documentos,
                    (SELECT COUNT(*) FROM encuesta e WHERE e.id_servicio = s.id_servicio) AS encuestas
             FROM servicio s WHERE s.id_servicio = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // Control de nombres duplicados (la comparacion no distingue mayusculas ni tildes)
    public function existeNombre(string $nombre, int $excluirId = 0): bool {
        $stmt = Conexion::obtener()->prepare(
            'SELECT COUNT(*) FROM servicio WHERE nombre = :nombre AND id_servicio <> :id'
        );
        $stmt->execute([':nombre' => $nombre, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(string $nombre): int {
        $pdo = Conexion::obtener();
        $stmt = $pdo->prepare('INSERT INTO servicio (nombre, activo) VALUES (:nombre, 1)');
        $stmt->execute([':nombre' => $nombre]);
        return (int) $pdo->lastInsertId();
    }

    public function actualizar(int $id, string $nombre): void {
        $stmt = Conexion::obtener()->prepare('UPDATE servicio SET nombre = :nombre WHERE id_servicio = :id');
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
    }

    // Baja logica (activo = 0) o reactivacion (activo = 1). Nunca se borra el servicio.
    public function cambiarEstado(int $id, bool $activo): void {
        $stmt = Conexion::obtener()->prepare('UPDATE servicio SET activo = :activo WHERE id_servicio = :id');
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    // Evita que un % o _ escrito en la busqueda funcione como comodin
    private function escaparLike(string $texto): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }
}
