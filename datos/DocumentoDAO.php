<?php
// Capa de DATOS
// Tabla documento y su historial de modificaciones (siempre con consultas preparadas).
//
// Estados de un documento:
//   activo = 0   -> dado de baja (baja logica): no se lista ni se puede abrir.
//   visible = 0  -> oculto: solo lo ve el administrador.
//   visible = 1  -> publicado: se muestra en el portal si el servicio esta activo.

require_once __DIR__ . '/Conexion.php';

class DocumentoDAO {

    private const COLUMNAS = 'd.id_documento, d.titulo, d.descripcion, d.archivo_url, d.fecha_carga,
                       d.fecha_modificacion, d.activo, d.visible, d.id_servicio,
                       a.nombre AS admin_nombre, s.nombre AS servicio, s.activo AS servicio_activo';

    private const UNIONES = 'FROM documento d
                JOIN administrativo a ON a.id_admin = d.id_admin_carga
                JOIN servicio s ON s.id_servicio = d.id_servicio';

    // Lista para el administrador. $estado: 'activos', 'baja' o 'todos'
    public function listarAdmin(string $busqueda, int $idServicio, string $estado): array {
        $sql = 'SELECT ' . self::COLUMNAS . ' ' . self::UNIONES;
        $donde = [];
        $params = [];

        if ($busqueda !== '') {
            $donde[] = 'd.titulo LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($idServicio > 0) {
            $donde[] = 'd.id_servicio = :servicio';
            $params[':servicio'] = $idServicio;
        }
        if ($estado === 'baja') {
            $donde[] = 'd.activo = 0';
        } elseif ($estado !== 'todos') {
            $donde[] = 'd.activo = 1';
        }
        if (count($donde) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $donde);
        }
        $sql .= ' ORDER BY d.fecha_carga DESC, d.id_documento DESC';

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Lista para el portal publico y para los usuarios basicos:
    // solo documentos activos, publicados y de servicios activos.
    public function listarPublicos(string $busqueda, int $idServicio): array {
        $sql = 'SELECT ' . self::COLUMNAS . ' ' . self::UNIONES
             . ' WHERE d.activo = 1 AND d.visible = 1 AND s.activo = 1';
        $params = [];

        if ($busqueda !== '') {
            $sql .= ' AND d.titulo LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($idServicio > 0) {
            $sql .= ' AND d.id_servicio = :servicio';
            $params[':servicio'] = $idServicio;
        }
        $sql .= ' ORDER BY s.nombre, d.titulo';

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT ' . self::COLUMNAS . ' ' . self::UNIONES . ' WHERE d.id_documento = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // Documento que se puede abrir o descargar. Un documento dado de baja nunca se entrega.
    // El administrador puede abrir tambien los ocultos y los de servicios dados de baja.
    public function obtenerAbrible(int $id, bool $esAdmin): ?array {
        $fila = $this->obtenerPorId($id);
        if ($fila === null || (int) $fila['activo'] !== 1) {
            return null;
        }
        if (!$esAdmin && ((int) $fila['visible'] !== 1 || (int) $fila['servicio_activo'] !== 1)) {
            return null;
        }
        return $fila;
    }

    public function crear(string $titulo, string $descripcion, string $archivo, int $idAdmin, int $idServicio, bool $visible): int {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO documento (titulo, descripcion, archivo_url, fecha_carga, id_admin_carga, id_servicio, activo, visible)
                 VALUES (:titulo, :descripcion, :archivo, CURDATE(), :admin, :servicio, 1, :visible)'
            );
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $descripcion === '' ? null : $descripcion,
                ':archivo'     => $archivo,
                ':admin'       => $idAdmin,
                ':servicio'    => $idServicio,
                ':visible'     => $visible ? 1 : 0,
            ]);
            $id = (int) $pdo->lastInsertId();
            $this->registrar($pdo, $id, $idAdmin, 'alta', 'Documento cargado: «' . $titulo . '»');
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Actualiza los datos y, si $archivoNuevo no es null, reemplaza el PDF.
    // Devuelve la ruta del PDF anterior si fue reemplazado (para borrarlo del servidor), o null.
    public function actualizar(int $id, string $titulo, string $descripcion, int $idServicio, bool $visible, ?string $archivoNuevo, int $idAdmin): ?string {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $antes = $this->obtenerPorId($id);
            if ($antes === null) {
                throw new RuntimeException('El documento no existe.');
            }

            $stmt = $pdo->prepare(
                'UPDATE documento
                 SET titulo = :titulo, descripcion = :descripcion, id_servicio = :servicio,
                     visible = :visible, archivo_url = :archivo, fecha_modificacion = NOW()
                 WHERE id_documento = :id'
            );
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $descripcion === '' ? null : $descripcion,
                ':servicio'    => $idServicio,
                ':visible'     => $visible ? 1 : 0,
                ':archivo'     => $archivoNuevo ?? $antes['archivo_url'],
                ':id'          => $id,
            ]);

            // Detalle de lo que cambio, para el historial
            $cambios = [];
            if ($antes['titulo'] !== $titulo) {
                $cambios[] = 'Título: «' . $antes['titulo'] . '» → «' . $titulo . '»';
            }
            if ((int) $antes['id_servicio'] !== $idServicio) {
                $nuevo = $pdo->prepare('SELECT nombre FROM servicio WHERE id_servicio = :id');
                $nuevo->execute([':id' => $idServicio]);
                $cambios[] = 'Servicio: ' . $antes['servicio'] . ' → ' . $nuevo->fetchColumn();
            }
            if ((string) $antes['descripcion'] !== $descripcion) {
                $cambios[] = 'Descripción modificada';
            }
            if ((int) $antes['visible'] !== ($visible ? 1 : 0)) {
                $cambios[] = 'Publicación: ' . ((int) $antes['visible'] === 1 ? 'Publicado' : 'Oculto')
                           . ' → ' . ($visible ? 'Publicado' : 'Oculto');
            }
            if ($archivoNuevo !== null) {
                $cambios[] = 'PDF reemplazado';
            }
            if (count($cambios) > 0) {
                $this->registrar($pdo, $id, $idAdmin, $archivoNuevo !== null ? 'reemplazo' : 'edicion', implode('; ', $cambios));
            }

            $pdo->commit();
            return $archivoNuevo !== null ? $antes['archivo_url'] : null;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Baja logica ($activo = false) o reactivacion ($activo = true). El registro y el PDF se conservan.
    public function cambiarEstado(int $id, bool $activo, int $idAdmin): void {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE documento SET activo = :activo, fecha_modificacion = NOW() WHERE id_documento = :id');
            $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
            $this->registrar($pdo, $id, $idAdmin, $activo ? 'reactivacion' : 'baja',
                $activo ? 'Documento reactivado' : 'Documento dado de baja');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Registro de modificaciones de un documento, de la mas reciente a la mas antigua
    public function historial(int $idDocumento): array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT h.accion, h.detalle, h.fecha, a.nombre AS admin_nombre
             FROM documento_historial h
             LEFT JOIN administrativo a ON a.id_admin = h.id_admin
             WHERE h.id_documento = :id
             ORDER BY h.fecha DESC, h.id_historial DESC'
        );
        $stmt->execute([':id' => $idDocumento]);
        return $stmt->fetchAll();
    }

    private function registrar(PDO $pdo, int $idDocumento, int $idAdmin, string $accion, string $detalle): void {
        $stmt = $pdo->prepare(
            'INSERT INTO documento_historial (id_documento, id_admin, accion, detalle, fecha)
             VALUES (:documento, :admin, :accion, :detalle, NOW())'
        );
        $stmt->execute([
            ':documento' => $idDocumento,
            ':admin'     => $idAdmin,
            ':accion'    => $accion,
            ':detalle'   => mb_substr($detalle, 0, 500),
        ]);
    }

    // Evita que un % o _ escrito en la busqueda funcione como comodin
    private function escaparLike(string $texto): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }
}
