<?php
// Capa de DATOS
// Registro de acciones importantes (tabla auditoria).

require_once __DIR__ . '/Conexion.php';

class AuditoriaDAO {

    public function registrar(?int $idAdmin, string $accion, string $entidad, ?int $idEntidad, string $detalle): void {
        $stmt = Conexion::obtener()->prepare(
            'INSERT INTO auditoria (id_admin, accion, entidad, id_entidad, detalle, fecha)
             VALUES (:admin, :accion, :entidad, :id_entidad, :detalle, NOW())'
        );
        $stmt->execute([
            ':admin'      => $idAdmin,
            ':accion'     => mb_substr($accion, 0, 40),
            ':entidad'    => mb_substr($entidad, 0, 30),
            ':id_entidad' => $idEntidad,
            ':detalle'    => mb_substr($detalle, 0, 500),
        ]);
    }

    // Filtros: usuario (id), accion, entidad, desde y hasta (fechas Y-m-d)
    private function condiciones(array $f, array &$params): string {
        $donde = [];
        if (!empty($f['usuario'])) {
            $donde[] = 'a.id_admin = :usuario';
            $params[':usuario'] = (int) $f['usuario'];
        }
        if (!empty($f['accion'])) {
            $donde[] = 'a.accion = :accion';
            $params[':accion'] = $f['accion'];
        }
        if (!empty($f['entidad'])) {
            $donde[] = 'a.entidad = :entidad';
            $params[':entidad'] = $f['entidad'];
        }
        if (!empty($f['desde'])) {
            $donde[] = 'a.fecha >= :desde';
            $params[':desde'] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $donde[] = 'a.fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
            $params[':hasta'] = $f['hasta'];
        }
        return count($donde) > 0 ? ' WHERE ' . implode(' AND ', $donde) : '';
    }

    public function contar(array $f): int {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM auditoria a' . $this->condiciones($f, $params);
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listar(array $f, int $limite, int $desplazamiento): array {
        $params = [];
        $sql = 'SELECT a.id_auditoria, a.accion, a.entidad, a.id_entidad, a.detalle, a.fecha,
                       ad.nombre AS usuario
                FROM auditoria a
                LEFT JOIN administrativo ad ON ad.id_admin = a.id_admin'
             . $this->condiciones($f, $params)
             . ' ORDER BY a.fecha DESC, a.id_auditoria DESC LIMIT ' . (int) $limite . ' OFFSET ' . (int) $desplazamiento;
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Acciones y entidades que ya aparecen en el registro (para los filtros)
    public function acciones(): array {
        return Conexion::obtener()->query('SELECT DISTINCT accion FROM auditoria ORDER BY accion')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function entidades(): array {
        return Conexion::obtener()->query('SELECT DISTINCT entidad FROM auditoria ORDER BY entidad')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function ultimas(int $limite = 5): array {
        $limite = (int) $limite;
        return Conexion::obtener()->query(
            "SELECT a.accion, a.detalle, a.fecha, ad.nombre AS usuario
             FROM auditoria a LEFT JOIN administrativo ad ON ad.id_admin = a.id_admin
             ORDER BY a.fecha DESC, a.id_auditoria DESC LIMIT $limite"
        )->fetchAll();
    }
}
