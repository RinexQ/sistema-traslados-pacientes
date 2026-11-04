<?php
// Capa de DATOS
// Encuestas, preguntas, opciones, envios y respuestas (siempre con consultas preparadas).
//
// Estados de una encuesta:
//   borrador  -> se esta armando; no se ve en el portal.
//   publicada -> se ve en el portal y recibe respuestas.
//   cerrada   -> ya no recibe respuestas; se conserva con todo su historial.
// Una encuesta que ya tiene respuestas no se edita: se crea una nueva version.

require_once __DIR__ . '/Conexion.php';

class EncuestaDAO {

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    // Evita que un % o _ escrito en la busqueda funcione como comodin
    private function escaparLike(string $texto): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }

    // Condicion por rango de fechas (Y-m-d) sobre una columna de fecha de envio
    private function condFecha(string $columna, string $desde, string $hasta, array &$params, string $sufijo): string {
        $sql = '';
        if ($desde !== '') {
            $sql .= " AND $columna >= :desde$sufijo";
            $params[":desde$sufijo"] = $desde;
        }
        if ($hasta !== '') {
            $sql .= " AND $columna < DATE_ADD(:hasta$sufijo, INTERVAL 1 DAY)";
            $params[":hasta$sufijo"] = $hasta;
        }
        return $sql;
    }

    // ------------------------------------------------------------------
    // Listados y consulta de una encuesta (panel del administrador)
    // ------------------------------------------------------------------

    private function filtrosAdmin(string $busqueda, int $idServicio, string $estado, array &$params): string {
        $donde = [];
        if ($busqueda !== '') {
            $donde[] = 'e.titulo LIKE :q';
            $params[':q'] = '%' . $this->escaparLike($busqueda) . '%';
        }
        if ($idServicio > 0) {
            $donde[] = 'e.id_servicio = :servicio';
            $params[':servicio'] = $idServicio;
        }
        if (in_array($estado, ['borrador', 'publicada', 'cerrada'], true)) {
            $donde[] = 'e.estado = :estado';
            $params[':estado'] = $estado;
        }
        return count($donde) > 0 ? ' WHERE ' . implode(' AND ', $donde) : '';
    }

    public function contarAdmin(string $busqueda, int $idServicio, string $estado): int {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM encuesta e' . $this->filtrosAdmin($busqueda, $idServicio, $estado, $params);
        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // Lista para el administrador. Las fechas (desde/hasta) limitan solo los conteos de respuestas.
    public function listarAdmin(string $busqueda, int $idServicio, string $estado, string $desde, string $hasta, int $limite, int $desplazamiento): array {
        $params = [];
        $filtroEnvio = $this->condFecha('ev.fecha', $desde, $hasta, $params, 'a');
        $filtroPromedio = $this->condFecha('ev2.fecha', $desde, $hasta, $params, 'b');

        $sql = "SELECT e.id_encuesta, e.titulo, e.version, e.estado, e.id_encuesta_origen, e.fecha_creacion,
                       s.nombre AS servicio, s.activo AS servicio_activo,
                       (SELECT COUNT(*) FROM encuesta_pregunta p WHERE p.id_encuesta = e.id_encuesta) AS preguntas,
                       (SELECT COUNT(*) FROM envio ev WHERE ev.id_encuesta = e.id_encuesta $filtroEnvio) AS envios,
                       (SELECT COUNT(*) FROM envio evt WHERE evt.id_encuesta = e.id_encuesta) AS envios_total,
                       (SELECT AVG(er.valor) FROM envio_respuesta er
                          JOIN envio ev2 ON ev2.id_envio = er.id_envio
                          JOIN encuesta_pregunta p2 ON p2.id_pregunta = er.id_pregunta
                         WHERE ev2.id_encuesta = e.id_encuesta AND p2.tipo = 'valoracion' $filtroPromedio) AS promedio
                FROM encuesta e
                LEFT JOIN servicio s ON s.id_servicio = e.id_servicio"
             . $this->filtrosAdmin($busqueda, $idServicio, $estado, $params)
             . ' ORDER BY e.id_encuesta_origen DESC, e.version DESC'
             . ' LIMIT ' . (int) $limite . ' OFFSET ' . (int) $desplazamiento;

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtener(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT e.*, s.nombre AS servicio, s.activo AS servicio_activo
             FROM encuesta e LEFT JOIN servicio s ON s.id_servicio = e.id_servicio
             WHERE e.id_encuesta = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function contarEnvios(int $idEncuesta): int {
        $stmt = Conexion::obtener()->prepare('SELECT COUNT(*) FROM envio WHERE id_encuesta = :id');
        $stmt->execute([':id' => $idEncuesta]);
        return (int) $stmt->fetchColumn();
    }

    // Una encuesta se edita solo si todavia no tiene respuestas y no esta cerrada
    public function esEditable(array $encuesta): bool {
        return $encuesta['estado'] !== 'cerrada' && $this->contarEnvios((int) $encuesta['id_encuesta']) === 0;
    }

    // Preguntas de una encuesta en orden, cada una con su lista de opciones
    public function preguntas(int $idEncuesta): array {
        $pdo = Conexion::obtener();
        $stmt = $pdo->prepare(
            'SELECT id_pregunta, id_encuesta, orden, texto, tipo, obligatoria
             FROM encuesta_pregunta WHERE id_encuesta = :id ORDER BY orden, id_pregunta'
        );
        $stmt->execute([':id' => $idEncuesta]);
        $preguntas = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT o.id_opcion, o.id_pregunta, o.orden, o.texto
             FROM encuesta_opcion o
             JOIN encuesta_pregunta p ON p.id_pregunta = o.id_pregunta
             WHERE p.id_encuesta = :id ORDER BY o.orden, o.id_opcion'
        );
        $stmt->execute([':id' => $idEncuesta]);
        $porPregunta = [];
        foreach ($stmt->fetchAll() as $o) {
            $porPregunta[(int) $o['id_pregunta']][] = $o;
        }
        foreach ($preguntas as $i => $p) {
            $preguntas[$i]['opciones'] = $porPregunta[(int) $p['id_pregunta']] ?? [];
        }
        return $preguntas;
    }

    public function obtenerPregunta(int $id): ?array {
        $stmt = Conexion::obtener()->prepare(
            'SELECT id_pregunta, id_encuesta, orden, texto, tipo, obligatoria FROM encuesta_pregunta WHERE id_pregunta = :id'
        );
        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch();
        if (!$p) {
            return null;
        }
        $stmt = Conexion::obtener()->prepare(
            'SELECT id_opcion, orden, texto FROM encuesta_opcion WHERE id_pregunta = :id ORDER BY orden, id_opcion'
        );
        $stmt->execute([':id' => $id]);
        $p['opciones'] = $stmt->fetchAll();
        return $p;
    }

    // ------------------------------------------------------------------
    // Alta y edicion (constructor)
    // ------------------------------------------------------------------

    public function crear(string $titulo, string $descripcion, int $idServicio, int $idAdmin): int {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO encuesta (titulo, descripcion, es_anonima, id_admin_creador, id_servicio, estado, version, fecha_creacion)
                 VALUES (:titulo, :descripcion, 1, :admin, :servicio, 'borrador', 1, NOW())"
            );
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $descripcion === '' ? null : $descripcion,
                ':admin'       => $idAdmin,
                ':servicio'    => $idServicio > 0 ? $idServicio : null,
            ]);
            $id = (int) $pdo->lastInsertId();
            // una encuesta nueva es la primera version de si misma
            $pdo->prepare('UPDATE encuesta SET id_encuesta_origen = :id WHERE id_encuesta = :id2')
                ->execute([':id' => $id, ':id2' => $id]);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function actualizarDatos(int $id, string $titulo, string $descripcion, int $idServicio): void {
        $stmt = Conexion::obtener()->prepare(
            'UPDATE encuesta SET titulo = :titulo, descripcion = :descripcion, id_servicio = :servicio WHERE id_encuesta = :id'
        );
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion === '' ? null : $descripcion,
            ':servicio'    => $idServicio > 0 ? $idServicio : null,
            ':id'          => $id,
        ]);
    }

    private function guardarOpciones(PDO $pdo, int $idPregunta, array $opciones): void {
        $stmt = $pdo->prepare('INSERT INTO encuesta_opcion (id_pregunta, orden, texto) VALUES (:pregunta, :orden, :texto)');
        foreach (array_values($opciones) as $i => $texto) {
            $stmt->execute([':pregunta' => $idPregunta, ':orden' => $i + 1, ':texto' => $texto]);
        }
    }

    public function crearPregunta(int $idEncuesta, string $texto, string $tipo, bool $obligatoria, array $opciones): int {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM encuesta_pregunta WHERE id_encuesta = :id');
            $stmt->execute([':id' => $idEncuesta]);
            $orden = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO encuesta_pregunta (id_encuesta, orden, texto, tipo, obligatoria)
                 VALUES (:encuesta, :orden, :texto, :tipo, :obligatoria)'
            );
            $stmt->execute([
                ':encuesta' => $idEncuesta, ':orden' => $orden, ':texto' => $texto,
                ':tipo' => $tipo, ':obligatoria' => $obligatoria ? 1 : 0,
            ]);
            $idPregunta = (int) $pdo->lastInsertId();
            if ($tipo === 'unica' || $tipo === 'multiple') {
                $this->guardarOpciones($pdo, $idPregunta, $opciones);
            }
            $pdo->commit();
            return $idPregunta;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Cambia la pregunta y reemplaza sus opciones (solo se usa mientras la encuesta no tiene respuestas)
    public function actualizarPregunta(int $idPregunta, string $texto, string $tipo, bool $obligatoria, array $opciones): void {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE encuesta_pregunta SET texto = :texto, tipo = :tipo, obligatoria = :obligatoria WHERE id_pregunta = :id'
            );
            $stmt->execute([':texto' => $texto, ':tipo' => $tipo, ':obligatoria' => $obligatoria ? 1 : 0, ':id' => $idPregunta]);

            $pdo->prepare('DELETE FROM encuesta_opcion WHERE id_pregunta = :id')->execute([':id' => $idPregunta]);
            if ($tipo === 'unica' || $tipo === 'multiple') {
                $this->guardarOpciones($pdo, $idPregunta, $opciones);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Sube o baja una pregunta intercambiando su lugar con la vecina. $direccion: 'subir' o 'bajar'
    public function moverPregunta(int $idPregunta, string $direccion): void {
        $pdo = Conexion::obtener();
        $pregunta = $this->obtenerPregunta($idPregunta);
        if ($pregunta === null) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $mayor = $direccion === 'bajar';
            $stmt = $pdo->prepare(
                'SELECT id_pregunta, orden FROM encuesta_pregunta
                 WHERE id_encuesta = :encuesta AND orden ' . ($mayor ? '>' : '<') . ' :orden
                 ORDER BY orden ' . ($mayor ? 'ASC' : 'DESC') . ' LIMIT 1'
            );
            $stmt->execute([':encuesta' => $pregunta['id_encuesta'], ':orden' => $pregunta['orden']]);
            $vecina = $stmt->fetch();
            if ($vecina) {
                $actualizar = $pdo->prepare('UPDATE encuesta_pregunta SET orden = :orden WHERE id_pregunta = :id');
                $actualizar->execute([':orden' => $vecina['orden'], ':id' => $pregunta['id_pregunta']]);
                $actualizar->execute([':orden' => $pregunta['orden'], ':id' => $vecina['id_pregunta']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Elimina una pregunta (y sus opciones) y deja los numeros de orden seguidos
    public function eliminarPregunta(int $idPregunta): void {
        $pdo = Conexion::obtener();
        $pregunta = $this->obtenerPregunta($idPregunta);
        if ($pregunta === null) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM encuesta_opcion WHERE id_pregunta = :id')->execute([':id' => $idPregunta]);
            $pdo->prepare('DELETE FROM encuesta_pregunta WHERE id_pregunta = :id')->execute([':id' => $idPregunta]);
            $pdo->prepare('UPDATE encuesta_pregunta SET orden = orden - 1 WHERE id_encuesta = :encuesta AND orden > :orden')
                ->execute([':encuesta' => $pregunta['id_encuesta'], ':orden' => $pregunta['orden']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Publicar, cerrar, duplicar y versiones
    // ------------------------------------------------------------------

    // Errores que impiden publicar (lista vacia si se puede publicar)
    public function erroresDePublicacion(int $idEncuesta): array {
        $errores = [];
        $preguntas = $this->preguntas($idEncuesta);
        if (count($preguntas) === 0) {
            return ['La encuesta necesita al menos una pregunta para publicarse.'];
        }
        foreach ($preguntas as $p) {
            if (($p['tipo'] === 'unica' || $p['tipo'] === 'multiple') && count($p['opciones']) < 2) {
                $errores[] = 'La pregunta "' . $p['texto'] . '" necesita al menos 2 opciones.';
            }
        }
        return $errores;
    }

    // Publica la encuesta. Si es una version nueva, las versiones anteriores publicadas se cierran.
    public function publicar(int $id): void {
        $pdo = Conexion::obtener();
        $encuesta = $this->obtener($id);
        if ($encuesta === null) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE encuesta SET estado = 'cerrada', fecha_cierre = NOW()
                 WHERE id_encuesta_origen = :origen AND id_encuesta <> :id AND estado = 'publicada'"
            )->execute([':origen' => $encuesta['id_encuesta_origen'], ':id' => $id]);

            $pdo->prepare("UPDATE encuesta SET estado = 'publicada', fecha_publicacion = NOW(), fecha_cierre = NULL WHERE id_encuesta = :id")
                ->execute([':id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function cerrar(int $id): void {
        Conexion::obtener()
            ->prepare("UPDATE encuesta SET estado = 'cerrada', fecha_cierre = NOW() WHERE id_encuesta = :id")
            ->execute([':id' => $id]);
    }

    // Copia la encuesta con sus preguntas y opciones como un borrador nuevo.
    // $nuevaVersion = true: es la version siguiente de la misma encuesta.
    // $nuevaVersion = false: es una copia independiente ("Copia de ...").
    public function duplicar(int $id, int $idAdmin, bool $nuevaVersion): int {
        $pdo = Conexion::obtener();
        $original = $this->obtener($id);
        if ($original === null) {
            throw new RuntimeException('La encuesta no existe.');
        }
        $preguntas = $this->preguntas($id);

        $pdo->beginTransaction();
        try {
            if ($nuevaVersion) {
                $stmt = $pdo->prepare('SELECT MAX(version) FROM encuesta WHERE id_encuesta_origen = :origen');
                $stmt->execute([':origen' => $original['id_encuesta_origen']]);
                $version = (int) $stmt->fetchColumn() + 1;
                $titulo = $original['titulo'];
            } else {
                $version = 1;
                $titulo = mb_substr('Copia de ' . $original['titulo'], 0, 200);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO encuesta (titulo, descripcion, es_anonima, id_admin_creador, id_servicio, estado, version, id_encuesta_origen, fecha_creacion)
                 VALUES (:titulo, :descripcion, 1, :admin, :servicio, 'borrador', :version, :origen, NOW())"
            );
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $original['descripcion'],
                ':admin'       => $idAdmin,
                ':servicio'    => $original['id_servicio'],
                ':version'     => $version,
                ':origen'      => $nuevaVersion ? $original['id_encuesta_origen'] : null,
            ]);
            $nuevoId = (int) $pdo->lastInsertId();
            if (!$nuevaVersion) {
                $pdo->prepare('UPDATE encuesta SET id_encuesta_origen = :id WHERE id_encuesta = :id2')
                    ->execute([':id' => $nuevoId, ':id2' => $nuevoId]);
            }

            $insertarPregunta = $pdo->prepare(
                'INSERT INTO encuesta_pregunta (id_encuesta, orden, texto, tipo, obligatoria)
                 VALUES (:encuesta, :orden, :texto, :tipo, :obligatoria)'
            );
            foreach ($preguntas as $p) {
                $insertarPregunta->execute([
                    ':encuesta' => $nuevoId, ':orden' => $p['orden'], ':texto' => $p['texto'],
                    ':tipo' => $p['tipo'], ':obligatoria' => $p['obligatoria'],
                ]);
                $nuevaPregunta = (int) $pdo->lastInsertId();
                $this->guardarOpciones($pdo, $nuevaPregunta, array_column($p['opciones'], 'texto'));
            }

            $pdo->commit();
            return $nuevoId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Parte publica: listado, encuesta y envio de respuestas
    // ------------------------------------------------------------------

    // Encuestas publicadas, generales o de servicios activos
    public function listarPublicas(string $busqueda, int $idServicio): array {
        $sql = "SELECT e.id_encuesta, e.titulo, e.descripcion, s.nombre AS servicio
                FROM encuesta e
                LEFT JOIN servicio s ON s.id_servicio = e.id_servicio
                WHERE e.estado = 'publicada' AND (e.id_servicio IS NULL OR s.activo = 1)";
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

    // Devuelve la encuesta solo si esta publicada y su servicio esta activo
    public function obtenerPublica(int $id): ?array {
        $e = $this->obtener($id);
        if ($e === null || $e['estado'] !== 'publicada') {
            return null;
        }
        if ($e['id_servicio'] !== null && (int) $e['servicio_activo'] !== 1) {
            return null;
        }
        return $e;
    }

    // Guarda un envio completo en una sola transaccion: o se guarda todo o no se guarda nada.
    // Devuelve false si el token ya se uso (la misma respuesta enviada dos veces).
    public function guardarEnvio(int $idEncuesta, string $token, array $respuestas): bool {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM envio WHERE token = :token');
            $stmt->execute([':token' => $token]);
            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->rollBack();
                return false;
            }

            // la encuesta tiene que seguir publicada en este momento
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuesta WHERE id_encuesta = :id AND estado = 'publicada'");
            $stmt->execute([':id' => $idEncuesta]);
            if ((int) $stmt->fetchColumn() === 0) {
                $pdo->rollBack();
                return false;
            }

            // solo se guarda la fecha: no se registra quien respondio
            $stmt = $pdo->prepare('INSERT INTO envio (id_encuesta, token, fecha) VALUES (:encuesta, :token, NOW())');
            $stmt->execute([':encuesta' => $idEncuesta, ':token' => $token]);
            $idEnvio = (int) $pdo->lastInsertId();

            $detalle = $pdo->prepare(
                'INSERT INTO envio_respuesta (id_envio, id_pregunta, id_opcion, valor, texto)
                 VALUES (:envio, :pregunta, :opcion, :valor, :texto)'
            );
            foreach ($respuestas as $r) {
                $detalle->execute([
                    ':envio'    => $idEnvio,
                    ':pregunta' => $r['id_pregunta'],
                    ':opcion'   => $r['id_opcion'] ?? null,
                    ':valor'    => $r['valor'] ?? null,
                    ':texto'    => $r['texto'] ?? null,
                ]);
            }
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            // 23000: el token ya existia (dos envios al mismo tiempo)
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Resultados y exportacion
    // ------------------------------------------------------------------

    // Resultados de una encuesta: cantidad de envios y, por pregunta, el conteo por opcion,
    // los porcentajes y el promedio de las valoraciones. Las respuestas de texto se piden aparte.
    public function resultados(int $idEncuesta, string $desde, string $hasta): array {
        $pdo = Conexion::obtener();
        $preguntas = $this->preguntas($idEncuesta);

        $params = [':id' => $idEncuesta];
        $fechaEnvio = $this->condFecha('ev.fecha', $desde, $hasta, $params, '');

        // envios totales en el rango
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM envio ev WHERE ev.id_encuesta = :id $fechaEnvio");
        $stmt->execute($params);
        $totalEnvios = (int) $stmt->fetchColumn();

        // cuantas personas respondieron cada pregunta
        $stmt = $pdo->prepare(
            "SELECT er.id_pregunta, COUNT(DISTINCT er.id_envio) AS n
             FROM envio_respuesta er JOIN envio ev ON ev.id_envio = er.id_envio
             WHERE ev.id_encuesta = :id $fechaEnvio GROUP BY er.id_pregunta"
        );
        $stmt->execute($params);
        $respondieron = [];
        foreach ($stmt->fetchAll() as $f) {
            $respondieron[(int) $f['id_pregunta']] = (int) $f['n'];
        }

        // conteo por opcion (opcion unica y multiple)
        $stmt = $pdo->prepare(
            "SELECT er.id_opcion, COUNT(*) AS n
             FROM envio_respuesta er JOIN envio ev ON ev.id_envio = er.id_envio
             WHERE ev.id_encuesta = :id AND er.id_opcion IS NOT NULL $fechaEnvio GROUP BY er.id_opcion"
        );
        $stmt->execute($params);
        $porOpcion = [];
        foreach ($stmt->fetchAll() as $f) {
            $porOpcion[(int) $f['id_opcion']] = (int) $f['n'];
        }

        // conteo por valor de 1 a 5 (valoracion)
        $stmt = $pdo->prepare(
            "SELECT er.id_pregunta, er.valor, COUNT(*) AS n
             FROM envio_respuesta er JOIN envio ev ON ev.id_envio = er.id_envio
             WHERE ev.id_encuesta = :id AND er.valor IS NOT NULL $fechaEnvio GROUP BY er.id_pregunta, er.valor"
        );
        $stmt->execute($params);
        $porValor = [];
        foreach ($stmt->fetchAll() as $f) {
            $porValor[(int) $f['id_pregunta']][(int) $f['valor']] = (int) $f['n'];
        }

        foreach ($preguntas as $i => $p) {
            $id = (int) $p['id_pregunta'];
            $total = $respondieron[$id] ?? 0;
            $preguntas[$i]['respondieron'] = $total;

            if ($p['tipo'] === 'unica' || $p['tipo'] === 'multiple') {
                foreach ($p['opciones'] as $j => $o) {
                    $n = $porOpcion[(int) $o['id_opcion']] ?? 0;
                    $preguntas[$i]['opciones'][$j]['cantidad'] = $n;
                    // el porcentaje es sobre las personas que respondieron la pregunta
                    $preguntas[$i]['opciones'][$j]['porcentaje'] = $total > 0 ? round($n * 100 / $total, 1) : 0;
                }
            } elseif ($p['tipo'] === 'valoracion') {
                $valores = [];
                $suma = 0;
                $cantidad = 0;
                for ($v = 1; $v <= 5; $v++) {
                    $n = $porValor[$id][$v] ?? 0;
                    $valores[$v] = ['cantidad' => $n, 'porcentaje' => $total > 0 ? round($n * 100 / $total, 1) : 0];
                    $suma += $v * $n;
                    $cantidad += $n;
                }
                $preguntas[$i]['valores'] = $valores;
                $preguntas[$i]['promedio'] = $cantidad > 0 ? round($suma / $cantidad, 2) : null;
            }
        }

        return ['envios' => $totalEnvios, 'preguntas' => $preguntas];
    }

    public function contarTextos(int $idPregunta, string $desde, string $hasta): int {
        $params = [':pregunta' => $idPregunta];
        $fecha = $this->condFecha('ev.fecha', $desde, $hasta, $params, '');
        $stmt = Conexion::obtener()->prepare(
            "SELECT COUNT(*) FROM envio_respuesta er JOIN envio ev ON ev.id_envio = er.id_envio
             WHERE er.id_pregunta = :pregunta AND er.texto IS NOT NULL $fecha"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // Respuestas de texto de una pregunta (sin ningun dato de quien las escribio)
    public function textos(int $idPregunta, string $desde, string $hasta, int $limite, int $desplazamiento): array {
        $params = [':pregunta' => $idPregunta];
        $fecha = $this->condFecha('ev.fecha', $desde, $hasta, $params, '');
        $stmt = Conexion::obtener()->prepare(
            "SELECT er.texto, ev.fecha
             FROM envio_respuesta er JOIN envio ev ON ev.id_envio = er.id_envio
             WHERE er.id_pregunta = :pregunta AND er.texto IS NOT NULL $fecha
             ORDER BY ev.fecha DESC, er.id_detalle DESC
             LIMIT " . (int) $limite . ' OFFSET ' . (int) $desplazamiento
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Todos los envios con sus respuestas, para exportar a CSV.
    // Devuelve una lista de ['id_envio', 'fecha', 'respuestas' => [id_pregunta => [texto, ...]]]
    public function enviosParaExportar(int $idEncuesta, string $desde, string $hasta): array {
        $params = [':id' => $idEncuesta];
        $fecha = $this->condFecha('ev.fecha', $desde, $hasta, $params, '');
        $stmt = Conexion::obtener()->prepare(
            "SELECT ev.id_envio, ev.fecha, er.id_pregunta, er.valor, er.texto, o.texto AS opcion
             FROM envio ev
             LEFT JOIN envio_respuesta er ON er.id_envio = ev.id_envio
             LEFT JOIN encuesta_opcion o ON o.id_opcion = er.id_opcion
             WHERE ev.id_encuesta = :id $fecha
             ORDER BY ev.fecha, ev.id_envio, o.orden"
        );
        $stmt->execute($params);

        $envios = [];
        foreach ($stmt->fetchAll() as $f) {
            $id = (int) $f['id_envio'];
            if (!isset($envios[$id])) {
                $envios[$id] = ['id_envio' => $id, 'fecha' => $f['fecha'], 'respuestas' => []];
            }
            if ($f['id_pregunta'] !== null) {
                $valor = $f['opcion'] ?? ($f['valor'] !== null ? (string) $f['valor'] : (string) $f['texto']);
                $envios[$id]['respuestas'][(int) $f['id_pregunta']][] = $valor;
            }
        }
        return array_values($envios);
    }
}
