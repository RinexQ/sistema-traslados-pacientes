-- =====================================================================
-- S.I.G.S.M. - Instalacion completa (un solo archivo)
--
-- Crea la base de datos traslados_pacientes con todas sus tablas y datos
-- de prueba, incluidas las mejoras de "excelencia" (constructor de encuestas,
-- respuestas por envio y registro de acciones). No hace falta ningun otro .sql.
--
-- VERSION PARA HOSTING compartido (InfinityFree y similares): alli no se puede
-- crear la base por SQL. Antes de importar: crear la base desde el panel del
-- hosting y SELECCIONARLA en phpMyAdmin (panel izquierdo). Importar UNA SOLA VEZ.
-- Es identico a instalar_todo.sql, sin las lineas CREATE DATABASE y USE.
-- =====================================================================

-- =====================================================================
-- PARTE 1 de 7 (antes: traslados_pacientes_v2.sql)
-- =====================================================================

CREATE TABLE administrativo (
    id_admin   INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE paciente (
    ci      VARCHAR(20) PRIMARY KEY,
    nombre  VARCHAR(100) NOT NULL,
    email   VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE conductor (
    id_conductor INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    licencia     VARCHAR(50)  NOT NULL UNIQUE
);

CREATE TABLE vehiculo (
    id_vehiculo        INT AUTO_INCREMENT PRIMARY KEY,
    patente            VARCHAR(20)  NOT NULL UNIQUE,
    tipo               VARCHAR(50)  NOT NULL,
    requiere_enfermero BOOLEAN      NOT NULL DEFAULT FALSE
);

CREATE TABLE enfermero (
    id_enfermero INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    matricula    VARCHAR(50)  NOT NULL UNIQUE
);

CREATE TABLE documento (
    id_documento    INT AUTO_INCREMENT PRIMARY KEY,
    titulo          VARCHAR(200) NOT NULL,
    archivo_url     VARCHAR(500) NOT NULL,
    fecha_carga     DATE         NOT NULL DEFAULT (CURRENT_DATE),
    id_admin_carga  INT NOT NULL,
    FOREIGN KEY (id_admin_carga) REFERENCES administrativo(id_admin)
);

CREATE TABLE solicitud_traslado (
    id_solicitud          INT AUTO_INCREMENT PRIMARY KEY,
    fecha                 DATETIME NOT NULL,
    origen                VARCHAR(200) NOT NULL,
    destino               VARCHAR(200) NOT NULL,
    estado                ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    id_admin_solicitante  INT NOT NULL,
    FOREIGN KEY (id_admin_solicitante) REFERENCES administrativo(id_admin)
);

CREATE TABLE encuesta (
    id_encuesta       INT AUTO_INCREMENT PRIMARY KEY,
    titulo            VARCHAR(200) NOT NULL,
    es_anonima        BOOLEAN      NOT NULL DEFAULT FALSE,
    id_admin_creador  INT NOT NULL,
    FOREIGN KEY (id_admin_creador) REFERENCES administrativo(id_admin)
);

CREATE TABLE codigo_qr (
    id_qr        INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(255) NOT NULL UNIQUE,
    id_documento INT NOT NULL,
    FOREIGN KEY (id_documento) REFERENCES documento(id_documento)
);

CREATE TABLE respuesta (
    id_respuesta     INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta      INT NOT NULL,
    ci_paciente      VARCHAR(20) NULL,
    respuesta_texto  TEXT NOT NULL,
    fecha            DATETIME NOT NULL,
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta),
    FOREIGN KEY (ci_paciente) REFERENCES paciente(ci)
);

CREATE TABLE traslado (
    id_traslado   INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud  INT NOT NULL,
    id_conductor  INT NOT NULL,
    id_enfermero  INT NULL,
    id_vehiculo   INT NOT NULL,
    fecha_salida  DATETIME NOT NULL,
    hora_llegada  TIME NULL,
    estado        ENUM('programado','en_curso','finalizado','cancelado') NOT NULL DEFAULT 'programado',
    FOREIGN KEY (id_solicitud) REFERENCES solicitud_traslado(id_solicitud),
    FOREIGN KEY (id_conductor) REFERENCES conductor(id_conductor),
    FOREIGN KEY (id_enfermero) REFERENCES enfermero(id_enfermero),
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculo(id_vehiculo)
);

CREATE TABLE carga (
    id_carga      INT AUTO_INCREMENT PRIMARY KEY,
    id_traslado   INT NOT NULL,
    tipo_elemento VARCHAR(100) NOT NULL,
    ci_paciente   VARCHAR(20) NULL,
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado),
    FOREIGN KEY (ci_paciente) REFERENCES paciente(ci)
);

-- =====================================================================
-- PARTE 2 de 7 (antes: agregar_login.sql)
-- =====================================================================

ALTER TABLE administrativo
    ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email;

ALTER TABLE paciente
    ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email;

INSERT INTO administrativo (nombre, email, password_hash)
VALUES (
    'Administrador de prueba',
    'admin@sirius.com',
    '$2b$12$7MhwefqHDvNBKAEEcGMLQu.enxv1ZBdAJm74yaRVonQewg6i3VSOS'
);

-- =====================================================================
-- PARTE 3 de 7 (antes: datos_prueba_ampliado.sql)
-- =====================================================================

INSERT INTO administrativo (nombre, email) VALUES
('Ana Fernandez', 'ana.fernandez@sirius.com'),
('Carlos Rodriguez', 'carlos.rodriguez@sirius.com'),
('Laura Benitez', 'laura.benitez@sirius.com'),
('Martin Silva', 'martin.silva@sirius.com');

INSERT INTO paciente (ci, nombre, email, password_hash) VALUES
('11111111', 'Maria Gonzalez', 'maria.gonzalez@gmail.com', '$2b$12$WKs8BexiOfGlVHlrAZ5Gouf2LqXgKSirVDDlYkekhzuXeFnqN/V8C'),
('22222222', 'Jose Martinez', 'jose.martinez@gmail.com', ''),
('33333333', 'Lucia Perez', 'lucia.perez@gmail.com', ''),
('44444444', 'Roberto Acosta', 'roberto.acosta@gmail.com', ''),
('55555555', 'Silvia Ramos', 'silvia.ramos@gmail.com', ''),
('66666666', 'Federico Mena', 'federico.mena@gmail.com', '');

INSERT INTO conductor (nombre, licencia) VALUES
('Pedro Suarez', 'LIC-001'),
('Marta Diaz', 'LIC-002'),
('Gustavo Pintos', 'LIC-003'),
('Valeria Costa', 'LIC-004');

INSERT INTO vehiculo (patente, tipo, requiere_enfermero) VALUES
('ABC1234', 'Ambulancia basica', FALSE),
('XYZ5678', 'Ambulancia UCI', TRUE),
('DEF9012', 'Traslado programado', FALSE),
('GHI3456', 'Ambulancia UCI', TRUE);

INSERT INTO enfermero (nombre, matricula) VALUES
('Sofia Lopez', 'MAT-001'),
('Diego Torres', 'MAT-002'),
('Camila Ferreira', 'MAT-003'),
('Nicolas Vega', 'MAT-004');

INSERT INTO documento (titulo, archivo_url, id_admin_carga) VALUES
('Historia clinica - Maria Gonzalez', 'https://ejemplo.com/docs/historia_maria.pdf', 2),
('Autorizacion de traslado', 'https://ejemplo.com/docs/autorizacion.pdf', 2),
('Informe de estudios', 'https://ejemplo.com/docs/informe_estudios.pdf', 3),
('Indicaciones post alta - Jose Martinez', 'https://ejemplo.com/docs/indicaciones_jose.pdf', 3),
('Consentimiento informado', 'https://ejemplo.com/docs/consentimiento.pdf', 4),
('Horarios de visita sala 3', 'https://ejemplo.com/docs/horarios_sala3.pdf', 5);

INSERT INTO solicitud_traslado (fecha, origen, destino, estado, id_admin_solicitante) VALUES
('2026-09-01 08:30:00', 'Hospital Central', 'Domicilio - Maria Gonzalez', 'aprobada', 2),
('2026-09-02 10:00:00', 'Domicilio - Jose Martinez', 'Hospital Central', 'aprobada', 3),
('2026-09-03 09:15:00', 'Hospital Central', 'Centro de rehabilitacion', 'aprobada', 2),
('2026-09-04 13:00:00', 'Domicilio - Roberto Acosta', 'Hospital Central', 'aprobada', 4),
('2026-09-05 07:45:00', 'Hospital Central', 'Domicilio - Silvia Ramos', 'aprobada', 5),
('2026-09-06 11:30:00', 'Domicilio - Federico Mena', 'Hospital Central', 'pendiente', 3),
('2026-09-06 16:00:00', 'Hospital Central', 'Clinica privada', 'rechazada', 4);

INSERT INTO encuesta (titulo, es_anonima, id_admin_creador) VALUES
('Satisfaccion del servicio de traslado', FALSE, 2),
('Atencion del personal de enfermeria', TRUE, 3),
('Calidad de la informacion recibida', FALSE, 2);

INSERT INTO codigo_qr (codigo, id_documento) VALUES
('QR-DOC-001', 1),
('QR-DOC-002', 2),
('QR-DOC-003', 3),
('QR-DOC-004', 4),
('QR-DOC-005', 5),
('QR-DOC-006', 6);

INSERT INTO respuesta (id_encuesta, ci_paciente, respuesta_texto, fecha) VALUES
(1, '11111111', 'Muy buen servicio, el conductor fue muy amable.', '2026-09-01 12:00:00'),
(1, '22222222', 'Todo bien, pero el traslado se demoro un poco.', '2026-09-02 15:00:00'),
(1, '44444444', 'Llegaron puntuales y fueron muy cuidadosos.', '2026-09-04 18:00:00'),
(2, NULL, 'La enfermera explico todo con claridad.', '2026-09-02 16:30:00'),
(2, NULL, 'Buena atencion durante todo el viaje.', '2026-09-05 10:15:00'),
(3, '55555555', 'La informacion recibida fue clara y completa.', '2026-09-05 12:00:00');

INSERT INTO traslado (id_solicitud, id_conductor, id_enfermero, id_vehiculo, fecha_salida, hora_llegada, estado) VALUES
(1, 1, NULL, 1, '2026-09-01 09:00:00', '09:45:00', 'finalizado'),
(2, 2, 1, 2, '2026-09-02 10:30:00', NULL, 'programado'),
(3, 3, NULL, 3, '2026-09-03 09:45:00', NULL, 'en_curso'),
(4, 4, 2, 4, '2026-09-04 13:30:00', '14:20:00', 'finalizado'),
(5, 1, 3, 2, '2026-09-05 08:15:00', NULL, 'cancelado');

INSERT INTO carga (id_traslado, tipo_elemento, ci_paciente) VALUES
(1, 'Silla de ruedas', '11111111'),
(2, 'Tanque de oxigeno', '22222222'),
(3, 'Camilla', '33333333'),
(4, 'Monitor portatil', NULL),
(4, 'Silla de ruedas', '44444444'),
(5, 'Tanque de oxigeno', '55555555');

-- =====================================================================
-- PARTE 4 de 7 (antes: actualizacion_ejes.sql)
-- =====================================================================

CREATE TABLE servicio (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO servicio (nombre) VALUES
('Unidad de Cuidados Paliativos'),
('Emergencia'),
('Neonatología'),
('Cardiología'),
('Centro cardiovascular - Hemodinamia'),
('Centro cardiovascular - Crujía cardíaca'),
('Clínica médica A'),
('Clínica médica B'),
('Clínica médica C'),
('Dermatología'),
('Endocrinología'),
('Gastroenterología'),
('Gastroenterología - Unidad de ostomías'),
('Geriatría'),
('Hematología'),
('Infectología'),
('Medicina física, rehabilitación y medicina del deporte'),
('Nefrología'),
('Neurología'),
('Oncología'),
('Psiquiatría'),
('U.E. Autoinmunes sistémicas'),
('Unidad de tabaquismo'),
('Anestesiología'),
('Cirugía plástica y quemados'),
('Cirugía vascular periférica'),
('Ginecotocológica B'),
('Neurocirugía'),
('Odontología'),
('Oftalmología'),
('Otorrinolaringología'),
('Quirúrgica A'),
('Quirúrgica B'),
('Quirúrgica F'),
('Urología'),
('Traumatología y ortopedia de adultos'),
('Radioterapia'),
('Medicina Nuclear'),
('Hemoterapia'),
('Imagenología'),
('Información general');

ALTER TABLE documento ADD COLUMN id_servicio INT NULL;

UPDATE documento
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Información general');

UPDATE documento
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Imagenología')
WHERE titulo = 'Informe de estudios';

UPDATE documento
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Clínica médica A')
WHERE titulo = 'Indicaciones post alta - Jose Martinez';

ALTER TABLE documento MODIFY id_servicio INT NOT NULL;

ALTER TABLE documento
    ADD CONSTRAINT fk_documento_servicio FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio);

ALTER TABLE administrativo
    ADD COLUMN rol ENUM('administrador','medico','enfermeria') NOT NULL DEFAULT 'administrador' AFTER email;

INSERT INTO administrativo (nombre, email, rol, password_hash) VALUES
('Dr. Ricardo Alonso', 'medico@sirius.com', 'medico', '$2b$12$Gk1ROD0lauoeoJPEM2lR6uNPpGVdfXNsKuqoXvslAsh4cTchQ1edS'),
('Enf. Paula Vidal', 'enfermeria@sirius.com', 'enfermeria', '$2b$12$ypXIrhj5CMKVihS1wAyqJOpgHlFCY8gj20DcIcg9afU6bkT/Yhf0W');

INSERT INTO documento (titulo, archivo_url, id_admin_carga, id_servicio) VALUES
('Indicaciones de interrupción voluntaria del embarazo', 'https://ejemplo.com/docs/interrupcion_voluntaria_embarazo.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Ginecotocológica B')),
('Prostatectomía radical (indicaciones e información para el paciente)', 'https://ejemplo.com/docs/prostatectomia_radical.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Urología')),
('Preparación para estudios imagenológicos', 'https://ejemplo.com/docs/preparacion_estudios_imagenologicos.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Imagenología')),
('Estudios diagnósticos con pertecnetato', 'https://ejemplo.com/docs/estudios_pertecnetato.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Medicina Nuclear')),
('Información sobre centellograma de perfusión miocárdica', 'https://ejemplo.com/docs/centellograma_perfusion_miocardica.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Medicina Nuclear')),
('Indicaciones ecocardiograma con dobutamina', 'https://ejemplo.com/docs/ecocardiograma_dobutamina.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Cardiología')),
('Indicaciones para pacientes en tratamiento con warfarina', 'https://ejemplo.com/docs/tratamiento_warfarina.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Cardiología')),
('Indicaciones ecocardiograma transesofágico', 'https://ejemplo.com/docs/ecocardiograma_transesofagico.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Cardiología')),
('Pauta para pacientes ostomizados (7 páginas)', 'https://ejemplo.com/docs/pauta_pacientes_ostomizados.pdf',
 (SELECT id_admin FROM administrativo WHERE email = 'admin@sirius.com'),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Gastroenterología - Unidad de ostomías'));

INSERT INTO codigo_qr (codigo, id_documento)
SELECT CONCAT('QR-DOC-', LPAD(id_documento, 3, '0')), id_documento
FROM documento
WHERE id_documento NOT IN (SELECT id_documento FROM codigo_qr);

ALTER TABLE solicitud_traslado
    ADD COLUMN tipo ENUM('paciente','biologico','no_biologico') NOT NULL DEFAULT 'paciente' AFTER fecha,
    ADD COLUMN ci_paciente VARCHAR(20) NULL,
    ADD COLUMN domicilio VARCHAR(200) NULL,
    ADD COLUMN telefono VARCHAR(30) NULL,
    ADD COLUMN diagnostico VARCHAR(255) NULL,
    ADD COLUMN motivo VARCHAR(255) NULL,
    ADD COLUMN aislamiento BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN aislamiento_detalle VARCHAR(150) NULL,
    ADD COLUMN rodado ENUM('ninguno','silla','camilla') NOT NULL DEFAULT 'ninguno',
    ADD COLUMN oxigeno BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN verificado BOOLEAN NOT NULL DEFAULT FALSE,
    ADD CONSTRAINT fk_solicitud_paciente FOREIGN KEY (ci_paciente) REFERENCES paciente(ci);

UPDATE solicitud_traslado
SET ci_paciente = '11111111', domicilio = 'Av. Italia 2345', telefono = '099111222',
    diagnostico = 'Postoperatorio estable', motivo = 'Alta con traslado a domicilio',
    rodado = 'silla', verificado = TRUE
WHERE id_solicitud = 1;

UPDATE solicitud_traslado
SET ci_paciente = '22222222', domicilio = 'Bulevar Artigas 1450', telefono = '098333444',
    diagnostico = 'Control cardiologico', motivo = 'Traslado al hospital para control',
    rodado = 'camilla', oxigeno = TRUE, verificado = TRUE
WHERE id_solicitud = 2;

INSERT INTO solicitud_traslado
    (fecha, tipo, ci_paciente, origen, destino, domicilio, telefono, diagnostico, motivo,
     aislamiento, aislamiento_detalle, rodado, oxigeno, verificado, estado, id_admin_solicitante)
VALUES
(NOW(), 'paciente', '33333333', 'Hospital de Clínicas', 'Domicilio - Lucia Perez',
 'Calle Mercedes 987', '094555666', 'Infeccion respiratoria en recuperacion', 'Alta medica con traslado',
 TRUE, 'Aislamiento de contacto', 'silla', FALSE, TRUE, 'pendiente',
 (SELECT id_admin FROM administrativo WHERE email = 'enfermeria@sirius.com'));

INSERT INTO solicitud_traslado
    (fecha, tipo, origen, destino, motivo, estado, id_admin_solicitante)
VALUES
(NOW(), 'biologico', 'Laboratorio central', 'Banco de sangre', 'Traslado de muestras de sangre', 'pendiente',
 (SELECT id_admin FROM administrativo WHERE email = 'enfermeria@sirius.com')),
(NOW(), 'no_biologico', 'Farmacia del hospital', 'Policlinica externa', 'Entrega de insumos medicos', 'pendiente',
 (SELECT id_admin FROM administrativo WHERE email = 'medico@sirius.com'));

-- =====================================================================
-- PARTE 5 de 7 (antes: actualizacion_requerimientos.sql)
-- =====================================================================

ALTER TABLE administrativo ADD COLUMN ci VARCHAR(8) NULL AFTER nombre;

UPDATE administrativo SET ci = CAST(10000000 + id_admin AS CHAR);

ALTER TABLE administrativo MODIFY ci VARCHAR(8) NOT NULL;

ALTER TABLE administrativo ADD CONSTRAINT uq_administrativo_ci UNIQUE (ci);

ALTER TABLE respuesta ADD COLUMN calificacion TINYINT NULL;

UPDATE respuesta SET calificacion = 3 + (id_respuesta % 3);

ALTER TABLE vehiculo
    ADD COLUMN estado ENUM('disponible','roto') NOT NULL DEFAULT 'disponible';

INSERT INTO vehiculo (patente, tipo, requiere_enfermero, estado)
VALUES ('JKL7890', 'Auto de apoyo', FALSE, 'roto');

ALTER TABLE solicitud_traslado
    MODIFY tipo ENUM('paciente','biologico','no_biologico','organo','muestra_biologica','cadeteria','materiales') NOT NULL DEFAULT 'paciente',
    ADD COLUMN prioridad ENUM('sin_prioridad','organos','dialisis','tratamiento','alta_domicilio','horario_especifico') NOT NULL DEFAULT 'sin_prioridad';

UPDATE solicitud_traslado SET tipo = 'muestra_biologica' WHERE tipo = 'biologico';

UPDATE solicitud_traslado SET tipo = 'materiales' WHERE tipo = 'no_biologico';

ALTER TABLE solicitud_traslado
    MODIFY tipo ENUM('paciente','organo','muestra_biologica','cadeteria','materiales') NOT NULL DEFAULT 'paciente';

UPDATE solicitud_traslado SET prioridad = 'alta_domicilio' WHERE id_solicitud = 1;

UPDATE solicitud_traslado SET prioridad = 'tratamiento' WHERE id_solicitud = 2;

INSERT INTO solicitud_traslado
    (fecha, tipo, origen, destino, motivo, prioridad, estado, id_admin_solicitante)
VALUES
(NOW(), 'organo', 'Hospital de Clínicas', 'Centro de trasplante', 'Traslado de órgano para trasplante', 'organos', 'aprobada',
 (SELECT id_admin FROM administrativo WHERE rol = 'medico' ORDER BY id_admin LIMIT 1)),
(NOW(), 'cadeteria', 'Registros médicos', 'Mesa de entrada central', 'Entrega de trámites administrativos', 'sin_prioridad', 'pendiente',
 (SELECT id_admin FROM administrativo WHERE rol = 'enfermeria' ORDER BY id_admin LIMIT 1));

INSERT INTO documento (titulo, archivo_url, id_admin_carga, id_servicio)
VALUES
('Indicaciones de ejemplo (archivo local)', 'documentos/ejemplo_indicaciones.pdf',
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Información general'));

INSERT INTO codigo_qr (codigo, id_documento)
SELECT CONCAT('QR-DOC-', LPAD(id_documento, 3, '0')), id_documento
FROM documento
WHERE id_documento NOT IN (SELECT id_documento FROM codigo_qr);

-- =====================================================================
-- PARTE 6 de 7 (antes: actualizacion_entrega3.sql)
-- =====================================================================

-- =====================================================================
-- Entrega 3: ejecutar UNA SOLA VEZ, despues de los scripts 1 a 5
-- (ver README). Hay que tener seleccionada la base de datos
-- (traslados_pacientes) antes de importar este archivo.
--
-- Que hace:
--   1. Servicios: baja logica.
--   2. Usuarios: dos roles (administrador y usuario) y baja logica.
--   3. Documentos: descripcion, baja logica, publicado/oculto,
--      historial de modificaciones y PDF de prueba en documentos/.
--   4. Encuestas: servicio y pregunta; respuestas anonimas.
--   5. Elimina el codigo QR por documento (ahora hay un unico QR).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. SERVICIOS: baja logica (activo = 0 los oculta del portal)
-- ---------------------------------------------------------------------
ALTER TABLE servicio ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;

-- ---------------------------------------------------------------------
-- 2. USUARIOS: roles 'administrador' y 'usuario' (basico) + baja logica
-- ---------------------------------------------------------------------
ALTER TABLE administrativo
    MODIFY rol ENUM('administrador','medico','enfermeria','usuario') NOT NULL DEFAULT 'usuario';

UPDATE administrativo SET rol = 'usuario' WHERE rol IN ('medico', 'enfermeria');
-- las cuentas de prueba sin contrasena no inician sesion: quedan como usuarios basicos
UPDATE administrativo SET rol = 'usuario' WHERE password_hash = '';

ALTER TABLE administrativo
    MODIFY rol ENUM('administrador','usuario') NOT NULL DEFAULT 'usuario';

ALTER TABLE administrativo ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;

-- ---------------------------------------------------------------------
-- 3. DOCUMENTOS
-- ---------------------------------------------------------------------
ALTER TABLE documento ADD COLUMN descripcion VARCHAR(500) NULL;
ALTER TABLE documento ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;           -- 0 = dado de baja
ALTER TABLE documento ADD COLUMN visible BOOLEAN NOT NULL DEFAULT TRUE;          -- 1 = publicado, 0 = oculto
ALTER TABLE documento ADD COLUMN fecha_modificacion DATETIME NULL;

-- Registro de las modificaciones: quien hizo que y cuando
CREATE TABLE documento_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    id_admin     INT NOT NULL,
    accion       VARCHAR(20)  NOT NULL,   -- alta, edicion, reemplazo, baja, reactivacion
    detalle      VARCHAR(500) NOT NULL,
    fecha        DATETIME     NOT NULL,
    FOREIGN KEY (id_documento) REFERENCES documento(id_documento),
    FOREIGN KEY (id_admin)     REFERENCES administrativo(id_admin)
);

-- Los documentos de prueba apuntaban a enlaces de ejemplo: ahora usan el PDF
-- real de la carpeta documentos/ (mismo nombre de archivo)
UPDATE documento
SET archivo_url = CONCAT('documentos/', SUBSTRING_INDEX(archivo_url, '/', -1))
WHERE archivo_url LIKE 'https://ejemplo.com/docs/%';

UPDATE documento SET descripcion = 'Resumen de la historia clínica de la paciente (documento ficticio).'
WHERE archivo_url = 'documentos/historia_maria.pdf';
UPDATE documento SET descripcion = 'Formulario que debe firmar el paciente o su familiar antes del traslado.'
WHERE archivo_url = 'documentos/autorizacion.pdf';
UPDATE documento SET descripcion = 'Resultados de estudios realizados durante la internación.'
WHERE archivo_url = 'documentos/informe_estudios.pdf';
UPDATE documento SET descripcion = 'Cuidados y medicación que debe seguir el paciente después del alta.'
WHERE archivo_url = 'documentos/indicaciones_jose.pdf';
UPDATE documento SET descripcion = 'Explica el procedimiento y los riesgos para dar el consentimiento.'
WHERE archivo_url = 'documentos/consentimiento.pdf';
UPDATE documento SET descripcion = 'Días y horarios en los que se permite la visita a la sala 3.'
WHERE archivo_url = 'documentos/horarios_sala3.pdf';

-- Documentos de prueba para comprobar los distintos estados
INSERT INTO servicio (nombre, activo) VALUES ('Archivo histórico (servicio dado de baja)', FALSE);

INSERT INTO documento (titulo, descripcion, archivo_url, fecha_carga, id_admin_carga, id_servicio, activo, visible) VALUES
('Protocolo interno de limpieza (borrador)',
 'Documento oculto: solo lo ve el administrador.',
 'documentos/protocolo_limpieza_borrador.pdf', CURDATE(),
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Información general'), TRUE, FALSE),
('Indicaciones de alta (versión obsoleta)',
 'Documento dado de baja: no se puede abrir.',
 'documentos/indicaciones_obsoletas.pdf', CURDATE(),
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Información general'), FALSE, TRUE),
('Registro histórico de visitas',
 'Pertenece a un servicio dado de baja: no se muestra en el portal.',
 'documentos/registro_historico.pdf', CURDATE(),
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Archivo histórico (servicio dado de baja)'), TRUE, TRUE);

-- Historial inicial de todos los documentos
INSERT INTO documento_historial (id_documento, id_admin, accion, detalle, fecha)
SELECT id_documento, id_admin_carga, 'alta', 'Documento cargado (datos de prueba)', NOW()
FROM documento;

INSERT INTO documento_historial (id_documento, id_admin, accion, detalle, fecha)
SELECT id_documento, id_admin_carga, 'baja', 'Documento dado de baja', NOW()
FROM documento WHERE activo = 0;

-- ---------------------------------------------------------------------
-- 4. ENCUESTAS: servicio y pregunta. Las respuestas pasan a ser anonimas.
-- ---------------------------------------------------------------------
ALTER TABLE encuesta ADD COLUMN id_servicio INT NULL;
ALTER TABLE encuesta ADD COLUMN pregunta VARCHAR(255) NOT NULL DEFAULT '¿Cómo calificás tu experiencia?';
ALTER TABLE encuesta
    ADD CONSTRAINT fk_encuesta_servicio FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio);

UPDATE encuesta
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Emergencia'),
    pregunta = '¿Cómo calificás el servicio de traslado que recibiste?'
WHERE titulo = 'Satisfaccion del servicio de traslado';

UPDATE encuesta
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Clínica médica A'),
    pregunta = '¿Cómo calificás la atención del personal de enfermería?'
WHERE titulo = 'Atencion del personal de enfermeria';

UPDATE encuesta
SET id_servicio = (SELECT id_servicio FROM servicio WHERE nombre = 'Información general'),
    pregunta = '¿Qué tan clara fue la información que recibiste?'
WHERE titulo = 'Calidad de la informacion recibida';

UPDATE encuesta SET es_anonima = TRUE;

-- Las respuestas ya no se asocian a ninguna persona
UPDATE respuesta SET ci_paciente = NULL;

-- Respuestas de prueba (calificaciones 1, 2, 4, 5 y 5 en cada encuesta)
INSERT INTO respuesta (id_encuesta, ci_paciente, respuesta_texto, calificacion, fecha)
SELECT e.id_encuesta, NULL, '', v.c, NOW()
FROM encuesta e
CROSS JOIN (SELECT 1 AS c UNION ALL SELECT 2 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 5) v;

-- ---------------------------------------------------------------------
-- 5. CODIGO QR: ahora hay un unico QR que abre la portada (portal.php)
--    y se genera desde el sistema, por eso se elimina la tabla por documento.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS codigo_qr;

-- =====================================================================
-- PARTE 7 de 7 (antes: actualizacion_excelencia.sql)
-- =====================================================================

-- =====================================================================
-- Excelencia 3 a 6 (ya integradas en este archivo: no se ejecutan por separado).
--
--   3. Constructor de encuestas: preguntas, opciones, estados y versiones.
--   4. Respuestas por envio, con token para no duplicar.
--   5. (Las estadisticas y el CSV usan estas mismas tablas.)
--   6. Registro de acciones importantes (auditoria).
--
-- Las 3 encuestas y las respuestas de las partes anteriores se conservan: se
-- convierten a la estructura nueva (una pregunta de valoracion de 1 a 5).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. ENCUESTA: descripcion, estado (borrador / publicada / cerrada) y versiones
-- ---------------------------------------------------------------------
ALTER TABLE encuesta ADD COLUMN descripcion VARCHAR(500) NULL;
ALTER TABLE encuesta ADD COLUMN estado ENUM('borrador','publicada','cerrada') NOT NULL DEFAULT 'borrador';
ALTER TABLE encuesta ADD COLUMN version INT NOT NULL DEFAULT 1;
ALTER TABLE encuesta ADD COLUMN id_encuesta_origen INT NULL;   -- primera version de la encuesta
ALTER TABLE encuesta ADD COLUMN fecha_creacion DATETIME NULL;
ALTER TABLE encuesta ADD COLUMN fecha_publicacion DATETIME NULL;
ALTER TABLE encuesta ADD COLUMN fecha_cierre DATETIME NULL;

-- Las encuestas que ya estaban pasan a estar publicadas
UPDATE encuesta
SET estado = 'publicada', id_encuesta_origen = id_encuesta,
    fecha_creacion = NOW(), fecha_publicacion = NOW();

ALTER TABLE encuesta
    ADD CONSTRAINT fk_encuesta_origen FOREIGN KEY (id_encuesta_origen) REFERENCES encuesta(id_encuesta);

-- ---------------------------------------------------------------------
-- 2. PREGUNTAS Y OPCIONES
--    tipo: unica (una opcion), multiple (varias), valoracion (1 a 5), texto (breve)
-- ---------------------------------------------------------------------
CREATE TABLE encuesta_pregunta (
    id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    orden       INT NOT NULL,
    texto       VARCHAR(255) NOT NULL,
    tipo        ENUM('unica','multiple','valoracion','texto') NOT NULL,
    obligatoria BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta)
);

CREATE TABLE encuesta_opcion (
    id_opcion   INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    orden       INT NOT NULL,
    texto       VARCHAR(150) NOT NULL,
    FOREIGN KEY (id_pregunta) REFERENCES encuesta_pregunta(id_pregunta)
);

-- ---------------------------------------------------------------------
-- 3. ENVIOS Y RESPUESTAS
--    Un envio es una persona que completo la encuesta (sin identificarla):
--    solo se guarda la fecha y un token unico que evita enviar dos veces
--    lo mismo al recargar la pagina.
-- ---------------------------------------------------------------------
CREATE TABLE envio (
    id_envio    INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    fecha       DATETIME NOT NULL,
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta)
);

CREATE TABLE envio_respuesta (
    id_detalle  INT AUTO_INCREMENT PRIMARY KEY,
    id_envio    INT NOT NULL,
    id_pregunta INT NOT NULL,
    id_opcion   INT NULL,          -- preguntas de opcion unica o multiple
    valor       INT NULL,          -- valoracion de 1 a 5
    texto       VARCHAR(500) NULL, -- respuesta de texto breve
    FOREIGN KEY (id_envio)    REFERENCES envio(id_envio),
    FOREIGN KEY (id_pregunta) REFERENCES encuesta_pregunta(id_pregunta),
    FOREIGN KEY (id_opcion)   REFERENCES encuesta_opcion(id_opcion)
);

-- ---------------------------------------------------------------------
-- 4. Se conservan las encuestas y respuestas anteriores en la estructura nueva
-- ---------------------------------------------------------------------
INSERT INTO encuesta_pregunta (id_encuesta, orden, texto, tipo, obligatoria)
SELECT id_encuesta, 1, pregunta, 'valoracion', TRUE FROM encuesta;

INSERT INTO envio (id_envio, id_encuesta, token, fecha)
SELECT id_respuesta, id_encuesta, CONCAT('anterior-', id_respuesta), COALESCE(fecha, NOW())
FROM respuesta;

INSERT INTO envio_respuesta (id_envio, id_pregunta, valor)
SELECT r.id_respuesta, p.id_pregunta, r.calificacion
FROM respuesta r
JOIN encuesta_pregunta p ON p.id_encuesta = r.id_encuesta AND p.orden = 1
WHERE r.calificacion IS NOT NULL;

DROP TABLE respuesta;
ALTER TABLE encuesta DROP COLUMN pregunta;

-- ---------------------------------------------------------------------
-- 5. Encuestas de prueba: una publicada con los 4 tipos de pregunta,
--    una en borrador y una cerrada
-- ---------------------------------------------------------------------
INSERT INTO encuesta (titulo, descripcion, es_anonima, id_admin_creador, id_servicio, estado, version,
                      fecha_creacion, fecha_publicacion, fecha_cierre) VALUES
('Experiencia general de la consulta', 'Contanos cómo fue tu consulta en el hospital.', TRUE,
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 NULL, 'publicada', 1, NOW(), NOW(), NULL),
('Encuesta de limpieza y confort (borrador)', 'Todavía no está publicada: se puede seguir editando.', TRUE,
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 (SELECT id_servicio FROM servicio WHERE nombre = 'Información general'), 'borrador', 1, NOW(), NULL, NULL),
('Encuesta de visitas (cerrada)', 'Encuesta cerrada: ya no recibe respuestas.', TRUE,
 (SELECT id_admin FROM administrativo WHERE rol = 'administrador' ORDER BY id_admin LIMIT 1),
 NULL, 'cerrada', 1, NOW(), NOW(), NOW());

UPDATE encuesta SET id_encuesta_origen = id_encuesta WHERE id_encuesta_origen IS NULL;

INSERT INTO encuesta_pregunta (id_encuesta, orden, texto, tipo, obligatoria)
SELECT id_encuesta, 1, '¿Cómo llegaste al hospital?', 'unica', TRUE
  FROM encuesta WHERE titulo = 'Experiencia general de la consulta'
UNION ALL
SELECT id_encuesta, 2, '¿Qué aspectos te parecieron buenos?', 'multiple', FALSE
  FROM encuesta WHERE titulo = 'Experiencia general de la consulta'
UNION ALL
SELECT id_encuesta, 3, '¿Cómo calificás la atención recibida?', 'valoracion', TRUE
  FROM encuesta WHERE titulo = 'Experiencia general de la consulta'
UNION ALL
SELECT id_encuesta, 4, '¿Querés dejarnos un comentario?', 'texto', FALSE
  FROM encuesta WHERE titulo = 'Experiencia general de la consulta'
UNION ALL
SELECT id_encuesta, 1, '¿Cómo calificás la limpieza de las instalaciones?', 'valoracion', TRUE
  FROM encuesta WHERE titulo = 'Encuesta de limpieza y confort (borrador)'
UNION ALL
SELECT id_encuesta, 2, '¿Qué sector necesita más limpieza?', 'unica', TRUE
  FROM encuesta WHERE titulo = 'Encuesta de limpieza y confort (borrador)'
UNION ALL
SELECT id_encuesta, 1, '¿Cómo calificás el horario de visitas?', 'valoracion', TRUE
  FROM encuesta WHERE titulo = 'Encuesta de visitas (cerrada)';

INSERT INTO encuesta_opcion (id_pregunta, orden, texto)
SELECT p.id_pregunta, o.n, o.t
FROM encuesta_pregunta p
CROSS JOIN (SELECT 1 AS n, 'En auto o moto' AS t UNION ALL SELECT 2, 'En transporte público' UNION ALL SELECT 3, 'A pie') o
WHERE p.texto = '¿Cómo llegaste al hospital?';

INSERT INTO encuesta_opcion (id_pregunta, orden, texto)
SELECT p.id_pregunta, o.n, o.t
FROM encuesta_pregunta p
CROSS JOIN (SELECT 1 AS n, 'Rapidez' AS t UNION ALL SELECT 2, 'Trato del personal' UNION ALL SELECT 3, 'Limpieza' UNION ALL SELECT 4, 'Información recibida') o
WHERE p.texto = '¿Qué aspectos te parecieron buenos?';

INSERT INTO encuesta_opcion (id_pregunta, orden, texto)
SELECT p.id_pregunta, o.n, o.t
FROM encuesta_pregunta p
CROSS JOIN (SELECT 1 AS n, 'Baños' AS t UNION ALL SELECT 2, 'Sala de espera' UNION ALL SELECT 3, 'Pasillos') o
WHERE p.texto = '¿Qué sector necesita más limpieza?';

-- Envios de prueba: 12 en la encuesta general y 4 en la cerrada (en distintos dias)
INSERT INTO envio (id_encuesta, token, fecha)
SELECT e.id_encuesta, CONCAT('prueba-', e.id_encuesta, '-', n.n), DATE_SUB(NOW(), INTERVAL n.n DAY)
FROM encuesta e
CROSS JOIN (SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
            UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
            UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12) n
WHERE e.titulo = 'Experiencia general de la consulta'
   OR (e.titulo = 'Encuesta de visitas (cerrada)' AND n.n <= 4);

-- Opcion unica
INSERT INTO envio_respuesta (id_envio, id_pregunta, id_opcion)
SELECT ev.id_envio, p.id_pregunta, o.id_opcion
FROM envio ev
JOIN encuesta_pregunta p ON p.id_encuesta = ev.id_encuesta AND p.tipo = 'unica'
JOIN encuesta_opcion o ON o.id_pregunta = p.id_pregunta AND o.orden = 1 + (ev.id_envio % 3)
WHERE ev.token LIKE 'prueba-%';

-- Opcion multiple (dos opciones distintas por envio)
INSERT INTO envio_respuesta (id_envio, id_pregunta, id_opcion)
SELECT ev.id_envio, p.id_pregunta, o.id_opcion
FROM envio ev
JOIN encuesta_pregunta p ON p.id_encuesta = ev.id_encuesta AND p.tipo = 'multiple'
JOIN encuesta_opcion o ON o.id_pregunta = p.id_pregunta AND o.orden = 1 + (ev.id_envio % 4)
WHERE ev.token LIKE 'prueba-%'
UNION ALL
SELECT ev.id_envio, p.id_pregunta, o.id_opcion
FROM envio ev
JOIN encuesta_pregunta p ON p.id_encuesta = ev.id_encuesta AND p.tipo = 'multiple'
JOIN encuesta_opcion o ON o.id_pregunta = p.id_pregunta AND o.orden = 1 + ((ev.id_envio + 1) % 4)
WHERE ev.token LIKE 'prueba-%';

-- Valoracion de 1 a 5
INSERT INTO envio_respuesta (id_envio, id_pregunta, valor)
SELECT ev.id_envio, p.id_pregunta,
       CASE WHEN ev.id_envio % 7 = 0 THEN 1
            WHEN ev.id_envio % 5 = 0 THEN 2
            ELSE 3 + (ev.id_envio % 3) END
FROM envio ev
JOIN encuesta_pregunta p ON p.id_encuesta = ev.id_encuesta AND p.tipo = 'valoracion'
WHERE ev.token LIKE 'prueba-%';

-- Texto breve (solo algunos envios dejan comentario)
INSERT INTO envio_respuesta (id_envio, id_pregunta, texto)
SELECT ev.id_envio, p.id_pregunta,
       CASE ev.id_envio % 2 WHEN 0 THEN 'Muy buena atención, gracias.' ELSE 'Esperé bastante en la sala de espera.' END
FROM envio ev
JOIN encuesta_pregunta p ON p.id_encuesta = ev.id_encuesta AND p.tipo = 'texto'
WHERE ev.token LIKE 'prueba-%' AND ev.id_envio % 3 = 0;

-- ---------------------------------------------------------------------
-- 6. REGISTRO DE ACCIONES IMPORTANTES: quien hizo que y cuando
-- ---------------------------------------------------------------------
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_admin     INT NULL,
    accion       VARCHAR(40)  NOT NULL,   -- por ejemplo: documento_baja, encuesta_publicacion
    entidad      VARCHAR(30)  NOT NULL,   -- documento, usuario, servicio, encuesta, pregunta o sesion
    id_entidad   INT NULL,
    detalle      VARCHAR(500) NOT NULL,
    fecha        DATETIME NOT NULL,
    FOREIGN KEY (id_admin) REFERENCES administrativo(id_admin)
);

CREATE INDEX idx_auditoria_fecha ON auditoria (fecha);
