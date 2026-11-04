<?php
require_once __DIR__ . '/Conexion.php';

class PanelDAO {

    private function contar(string $sql): int {
        return (int) Conexion::obtener()->query($sql)->fetchColumn();
    }

    public function contarVehiculos(): int {
        return $this->contar('SELECT COUNT(*) FROM vehiculo');
    }

    public function contarDocumentos(): int {
        return $this->contar('SELECT COUNT(*) FROM documento WHERE activo = 1');
    }

    public function contarTrasladosActivos(): int {
        return $this->contar("SELECT COUNT(*) FROM traslado WHERE estado IN ('programado','en_curso')");
    }

    // Indicadores generales del sistema para el panel del administrador
    public function indicadores(): array {
        return [
            'documentos_activos' => $this->contar('SELECT COUNT(*) FROM documento WHERE activo = 1'),
            'documentos_ocultos' => $this->contar('SELECT COUNT(*) FROM documento WHERE activo = 1 AND visible = 0'),
            'documentos_baja'    => $this->contar('SELECT COUNT(*) FROM documento WHERE activo = 0'),
            'servicios_activos'  => $this->contar('SELECT COUNT(*) FROM servicio WHERE activo = 1'),
            'usuarios_activos'   => $this->contar('SELECT COUNT(*) FROM administrativo WHERE activo = 1'),
            'encuestas_publicadas' => $this->contar("SELECT COUNT(*) FROM encuesta WHERE estado = 'publicada'"),
            'encuestas_borrador'   => $this->contar("SELECT COUNT(*) FROM encuesta WHERE estado = 'borrador'"),
            'encuestas_cerradas'   => $this->contar("SELECT COUNT(*) FROM encuesta WHERE estado = 'cerrada'"),
            'respuestas_total'   => $this->contar('SELECT COUNT(*) FROM envio'),
            'respuestas_semana'  => $this->contar('SELECT COUNT(*) FROM envio WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'satisfaccion'       => Conexion::obtener()->query(
                "SELECT AVG(er.valor) FROM envio_respuesta er
                 JOIN encuesta_pregunta p ON p.id_pregunta = er.id_pregunta
                 WHERE p.tipo = 'valoracion' AND er.valor IS NOT NULL"
            )->fetchColumn(),
            'ambulancias'        => $this->contarVehiculos(),
            'traslados_activos'  => $this->contarTrasladosActivos(),
        ];
    }

    public function trasladosRecientes(int $limite = 5): array {
        $limite = (int) $limite;
        $sql = "SELECT t.id_traslado, s.origen, s.destino, t.estado, t.fecha_salida
                FROM traslado t
                JOIN solicitud_traslado s ON s.id_solicitud = t.id_solicitud
                ORDER BY t.fecha_salida DESC
                LIMIT $limite";
        return Conexion::obtener()->query($sql)->fetchAll();
    }

    public function actividadReciente(int $limite = 5): array {
        $limite = (int) $limite;
        $sql = "(SELECT 'documento' AS tipo,
                        CONCAT('Documento cargado: ', titulo) AS texto,
                        CAST(fecha_carga AS DATETIME) AS fecha
                 FROM documento WHERE activo = 1)
                UNION ALL
                (SELECT 'traslado',
                        CONCAT('Traslado ', t.estado, ': ', s.origen, ' a ', s.destino),
                        t.fecha_salida
                 FROM traslado t
                 JOIN solicitud_traslado s ON s.id_solicitud = t.id_solicitud)
                UNION ALL
                (SELECT 'respuesta',
                        CONCAT('Nueva respuesta en: ', e.titulo),
                        ev.fecha
                 FROM envio ev
                 JOIN encuesta e ON e.id_encuesta = ev.id_encuesta)
                ORDER BY fecha DESC
                LIMIT $limite";
        return Conexion::obtener()->query($sql)->fetchAll();
    }

    public function listarVehiculos(): array {
        return Conexion::obtener()
            ->query('SELECT id_vehiculo, patente, tipo, requiere_enfermero, estado FROM vehiculo ORDER BY id_vehiculo')
            ->fetchAll();
    }

    public function listarTraslados(): array {
        $sql = 'SELECT t.id_traslado, s.origen, s.destino, t.fecha_salida, t.hora_llegada, t.estado,
                       c.nombre AS conductor, v.patente, e.nombre AS enfermero
                FROM traslado t
                JOIN solicitud_traslado s ON s.id_solicitud = t.id_solicitud
                JOIN conductor c ON c.id_conductor = t.id_conductor
                JOIN vehiculo v ON v.id_vehiculo = t.id_vehiculo
                LEFT JOIN enfermero e ON e.id_enfermero = t.id_enfermero
                ORDER BY t.fecha_salida DESC';
        return Conexion::obtener()->query($sql)->fetchAll();
    }
}
