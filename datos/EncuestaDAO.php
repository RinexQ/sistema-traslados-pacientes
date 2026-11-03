<?php
// Capa de DATOS
// Consultas de encuestas y respuestas. Las respuestas son anonimas.

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../negocio/RespuestaEncuesta.php';

class EncuestaDAO {

    // Encuestas visibles para el publico: las generales y las de servicios activos
    public function listarPublicas(string $busqueda, int $idServicio): array {
        $sql = 'SELECT e.id_encuesta, e.titulo, e.pregunta, s.nombre AS servicio
                FROM encuesta e
                LEFT JOIN servicio s ON s.id_servicio = e.id_servicio
                WHERE (e.id_servicio IS NULL OR s.activo = 1)';
        $params = [];
        if ($busqueda !== '') {
            $sql .= ' AND e.titulo LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($idServicio > 0) {
            $sql .= ' AND e.id_servicio = :servicio';
            $params[':servicio'] = $idServicio;
        }
        $sql .= ' ORDER BY e.titulo';
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPublica(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT e.id_encuesta, e.titulo, e.pregunta, s.nombre AS servicio
             FROM encuesta e
             LEFT JOIN servicio s ON s.id_servicio = e.id_servicio
             WHERE e.id_encuesta = :id AND (e.id_servicio IS NULL OR s.activo = 1)'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // Guarda solo la calificacion y la fecha: no se registra quien respondio
    public function guardarRespuesta(RespuestaEncuesta $r): int {
        $pdo = Conexion::obtener();
        $stmt = $pdo->prepare(
            "INSERT INTO respuesta (id_encuesta, ci_paciente, respuesta_texto, calificacion, fecha)
             VALUES (:encuesta, NULL, '', :calificacion, NOW())"
        );
        $stmt->execute([':encuesta' => $r->idEncuesta, ':calificacion' => $r->calificacion]);
        return (int) $pdo->lastInsertId();
    }

    // Resultados para el administrador: cantidad de respuestas, promedio y cantidad por opcion (1 a 5)
    public function reporte(string $busqueda, int $idServicio): array {
        $sql = 'SELECT e.id_encuesta, e.titulo, s.nombre AS servicio, s.activo AS servicio_activo,
                       COUNT(r.id_respuesta) AS total,
                       AVG(r.calificacion) AS promedio,
                       COALESCE(SUM(r.calificacion = 1), 0) AS c1,
                       COALESCE(SUM(r.calificacion = 2), 0) AS c2,
                       COALESCE(SUM(r.calificacion = 3), 0) AS c3,
                       COALESCE(SUM(r.calificacion = 4), 0) AS c4,
                       COALESCE(SUM(r.calificacion = 5), 0) AS c5
                FROM encuesta e
                LEFT JOIN servicio s ON s.id_servicio = e.id_servicio
                LEFT JOIN respuesta r ON r.id_encuesta = e.id_encuesta';
        $donde = [];
        $params = [];
        if ($busqueda !== '') {
            $donde[] = 'e.titulo LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($idServicio > 0) {
            $donde[] = 'e.id_servicio = :servicio';
            $params[':servicio'] = $idServicio;
        }
        if (count($donde) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $donde);
        }
        $sql .= ' GROUP BY e.id_encuesta, e.titulo, e.id_servicio, s.nombre, s.activo ORDER BY e.titulo';
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Evita que un % o _ escrito en la busqueda funcione como comodin
    private function escaparLike(string $texto): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }
}
