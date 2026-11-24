CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

DROP TABLE IF EXISTS reportes_preguntas;
DROP TABLE IF EXISTS respuestas;
DROP TABLE IF EXISTS preguntas_activas;
DROP TABLE IF EXISTS partida_pregunta;
DROP TABLE IF EXISTS historial_partidas;
DROP TABLE IF EXISTS partida_jugador;
DROP TABLE IF EXISTS partida;
DROP TABLE IF EXISTS movimiento_trampitas;
DROP TABLE IF EXISTS compra_trampitas;
DROP TABLE IF EXISTS preguntas_sugeridas;
DROP TABLE IF EXISTS preguntas;
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    fechaNacimiento DATE NOT NULL,
    sexo TINYINT(1) NOT NULL,
    lat VARCHAR(50) NOT NULL,
    lng VARCHAR(50) NOT NULL,
    mail VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    fotoDePerfil VARCHAR(255),
    rol ENUM('jugador','editor','administrador') NOT NULL DEFAULT 'jugador',
    token VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL
);

INSERT INTO usuarios (nombre, apellido, fechaNacimiento, sexo, lat, lng,
 mail, password, usuario, fotoDePerfil, rol, token, activo) VALUES
('Juan', 'Perez', '1998-12-12', '1', '-34.665336611141214', '-58.37326118991368', 'admin@devmail.com',
 MD5('1234'), 'admin', 'fotoDelAdmin.png', 'administrador', NULL, '1');

CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion TEXT NOT NULL,
    opcion_a VARCHAR(255) NOT NULL,
    opcion_b VARCHAR(255) NOT NULL,
    opcion_c VARCHAR(255) NOT NULL,
    opcion_d VARCHAR(255) NOT NULL,
    respuesta_correcta CHAR(1) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    creado_por INT NULL,
    origen ENUM('directa', 'sugerencia') NOT NULL DEFAULT 'directa',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_preguntas_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE preguntas_sugeridas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    opcion_a VARCHAR(255) NOT NULL,
    opcion_b VARCHAR(255) NOT NULL,
    opcion_c VARCHAR(255) NOT NULL,
    opcion_d VARCHAR(255) NOT NULL,
    respuesta_correcta CHAR(1) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    motivo_rechazo TEXT NULL,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    pregunta_id INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sugerencias_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_sugerencias_revisor
        FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_sugerencias_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE SET NULL,
    INDEX idx_sugerencias_estado (estado)
);

CREATE TABLE preguntas_activas (
    usuario_id INT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    expira_en DATETIME NOT NULL,
    CONSTRAINT fk_preguntas_activas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_preguntas_activas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
);

CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    es_correcta BOOLEAN NOT NULL,
    con_trampita BOOLEAN NOT NULL DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_respuestas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE,
    INDEX idx_respuestas_pregunta (pregunta_id),
    INDEX idx_respuestas_usuario (usuario_id)
);

INSERT INTO preguntas (descripcion, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria) VALUES 
-- HISTORIA
(
    '¿Quién fue el primer Director Supremo de las Provincias Unidas del Río de la Plata?',
    'Manuel Belgrano',
    'Gervasio Antonio de Posadas',
    'Cornelio Saavedra',
    'Bernardino Rivadavia',
    'B',
    'Historia'
),
(
    '¿En qué año cayó el Imperio Romano de Occidente?',
    '476 d.C.',
    '1492 d.C.',
    '1054 d.C.',
    '33 d.C.',
    'A',
    'Historia'
),
(
    '¿Quién fue el líder de la Revolución Cubana junto a Fidel Castro?',
    'Evo Morales',
    'Ernesto "Che" Guevara',
    'Salvador Allende',
    'Augusto Pinochet',
    'B',
    'Historia'
),
(
    '¿Qué civilización construyó la ciudad de Machu Picchu en Perú?',
    'Azteca',
    'Maya',
    'Inca',
    'Olmeca',
    'C',
    'Historia'
),

-- CULTURA
(
    '¿Cuál es el río más largo del mundo?',
    'Río Nilo',
    'Río Amazonas',
    'Río Yangtsé',
    'Río Misisipi',
    'B',
    'Cultura'
),
(
    '¿Cuál es el idioma oficial de Brasil?',
    'Español',
    'Inglés',
    'Portugués',
    'Francés',
    'C',
    'Cultura'
),
(
    '¿De qué país es originario el tango?',
    'España e Italia',
    'Argentina y Uruguay',
    'México y Colombia',
    'Cuba y Puerto Rico',
    'B',
    'Cultura'
),
(
    '¿Cuál es la festividad mexicana tradicional dedicada a honrar a los difuntos?',
    'El Carnaval',
    'El Inti Raymi',
    'El Día de los Muertos',
    'La Tomatina',
    'C',
    'Cultura'
),

-- DEPORTE
(
    '¿En qué año ganó la Selección Argentina su tercera Copa del Mundo de la FIFA?',
    '1978',
    '1986',
    '2022',
    '2014',
    'C',
    'Deportes'
),
(
    '¿Cuántos jugadores conforman un equipo titular de fútbol en el campo de juego?',
    '9',
    '10',
    '11',
    '12',
    'C',
    'Deporte'
),
(
    '¿En qué país se originaron los Juegos Olímpicos modernos?',
    'Italia',
    'Grecia',
    'Francia',
    'Inglaterra',
    'B',
    'Deporte'
),
(
    '¿Qué superficie se utiliza para disputar el torneo de tenis de Roland Garros?',
    'Césped',
    'Dura',
    'Polvo de ladrillo (Tierra batida)',
    'Alfombra',
    'C',
    'Deporte'
),

-- ARTE
(
    '¿Quién pintó la famosa obra "La última cena"?',
    'Pablo Picasso',
    'Leonardo da Vinci',
    'Vincent van Gogh',
    'Michelangelo Buonarroti',
    'B',
    'Arte'
),
(
    '¿A qué movimiento artístico pertenece el pintor Salvador Dalí?',
    'Impresionismo',
    'Cubismo',
    'Surrealismo',
    'Barroco',
    'C',
    'Arte'
),
(
    '¿En qué museo se encuentra expuesta la célebre obra "La Mona Lisa"?',
    'Museo del Prado',
    'Museo del Louvre',
    'The Metropolitan Museum of Art',
    'British Museum',
    'B',
    'Arte'
),

-- CIENCIA
(
    '¿Cuál es el elemento químico cuyo símbolo en la tabla periódica es el Au?',
    'Plata',
    'Cobre',
    'Oro',
    'Aluminio',
    'C',
    'Ciencia'
),
(
    '¿Qué planeta del Sistema Solar es conocido popularmente como el "Planeta Rojo"?',
    'Júpiter',
    'Marte',
    'Venus',
    'Saturno',
    'B',
    'Ciencia'
),
(
    '¿Qué físico desarrolló la famosa teoría de la relatividad?',
    'Isaac Newton',
    'Galileo Galilei',
    'Albert Einstein',
    'Nikola Tesla',
    'C',
    'Ciencia'
);

CREATE TABLE partida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE partida_jugador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partida_id INT NOT NULL,
    usuario_id INT NOT NULL,
    puntaje INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_partida_jugador UNIQUE (partida_id, usuario_id),
    CONSTRAINT fk_pj_partida FOREIGN KEY (partida_id) REFERENCES partida(id) ON DELETE CASCADE,
    CONSTRAINT fk_pj_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);



CREATE TABLE compra_trampitas
(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT            NOT NULL,
    cantidad        INT            NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    moneda          CHAR(3)        NOT NULL DEFAULT 'USD',
    estado          VARCHAR(20)    NOT NULL DEFAULT 'confirmada',
    medio_pago      VARCHAR(20)    NOT NULL DEFAULT 'simulado',
    referencia_pago VARCHAR(100) NULL,
    fecha           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_compra_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_compra_precio CHECK (precio_unitario >= 0),
    CONSTRAINT chk_compra_estado
        CHECK (estado IN ('pendiente', 'confirmada', 'rechazada', 'reembolsada')),
    CONSTRAINT fk_compra_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
);

CREATE TABLE movimiento_trampitas
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT         NOT NULL,
    tipo       VARCHAR(20) NOT NULL,
    cantidad   INT         NOT NULL,
    fecha      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
);


ALTER TABLE usuarios
ADD es_bot TINYINT(1) NOT NULL DEFAULT 0,
ADD habilidad DECIMAL(3,2) NULL;



INSERT INTO usuarios (nombre, apellido, fechaNacimiento, sexo, lat, lng,mail, password, usuario, fotoDePerfil, token, activo, es_bot, habilidad)
VALUES('Botito',   'Bot', '2000-01-01', 0, '0', '0', 'botito@bot.local',   '!', 'Botito',      'bot.png', NULL, 1, 1, 0.50),
('Einstein', 'Bot', '2000-01-01', 0, '0', '0', 'einstein@bot.local', '!', 'EinsteinBot', 'bot.png', NULL, 1, 1, 0.90);



ALTER TABLE partida
ADD tipo ENUM('solo','vs_jugador','vs_bot') NOT NULL DEFAULT 'solo',
ADD estado ENUM('pendiente','en_curso','finalizada') NOT NULL DEFAULT 'en_curso',
ADD orden_actual INT NOT NULL DEFAULT 1,
ADD total_preguntas INT NOT NULL DEFAULT 10,
ADD ganador_id INT NULL,
ADD CONSTRAINT fk_partida_ganador FOREIGN KEY (ganador_id) REFERENCES usuarios(id);



CREATE TABLE partida_pregunta (
partida_id INT NOT NULL,
orden INT NOT NULL,
pregunta_id INT NOT NULL,
PRIMARY KEY (partida_id, orden),
CONSTRAINT fk_pp_partida  FOREIGN KEY (partida_id)  REFERENCES partida(id)   ON DELETE CASCADE,
CONSTRAINT fk_pp_pregunta FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
);



ALTER TABLE respuestas
ADD partida_id INT NULL,
ADD respuesta_elegida CHAR(1) NULL,
ADD CONSTRAINT fk_respuestas_partida FOREIGN KEY (partida_id) REFERENCES partida(id) ON DELETE CASCADE;


UPDATE preguntas SET categoria = 'Deporte' WHERE categoria = 'Deportes';


ALTER TABLE respuestas
ADD UNIQUE KEY uq_resp_partida (partida_id, pregunta_id, usuario_id);

-- Partidas vs_bot: se registra si la respuesta usó trampita (el bot lo usa para su ratio).
-- IF NOT EXISTS para no romper instalaciones que ya tienen la columna.
ALTER TABLE respuestas
ADD COLUMN IF NOT EXISTS con_trampita BOOLEAN NOT NULL DEFAULT 0;


CREATE TABLE reportes_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NULL,
    usuario_id INT NOT NULL,
    partida_id INT NULL,
    motivo ENUM('respuesta_incorrecta', 'mal_formulada', 'ambigua',
                'ofensiva', 'duplicada', 'otro') NOT NULL,
    detalle TEXT NULL,
    estado ENUM('pendiente', 'editada', 'desactivada', 'eliminada', 'cerrada')
        NOT NULL DEFAULT 'pendiente',
    resuelto_por INT NULL,
    resuelto_en DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reportes_pregunta FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE SET NULL,
    CONSTRAINT fk_reportes_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    CONSTRAINT fk_reportes_partida  FOREIGN KEY (partida_id)  REFERENCES partida(id)   ON DELETE SET NULL,
    CONSTRAINT fk_reportes_revisor  FOREIGN KEY (resuelto_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT uq_reporte_usuario_pregunta UNIQUE (usuario_id, pregunta_id),
    INDEX idx_reportes_estado (estado)
);

ALTER TABLE usuarios ADD rol ENUM('jugador','editor','admin') NOT NULL DEFAULT 'jugador';
UPDATE usuarios SET rol = 'admin' WHERE usuario = 'admin';

ALTER TABLE movimiento_trampitas
    ADD partida_id INT NULL,
  ADD pregunta_id INT NULL,
  ADD INDEX idx_mov_usuario (usuario_id),
  ADD INDEX idx_mov_fecha (fecha);

ALTER TABLE compra_trampitas ADD INDEX idx_compra_fecha (fecha);

INSERT INTO movimiento_trampitas (usuario_id, tipo, cantidad) VALUES (1, 'compra', 3)


ALTER TABLE usuarios
    ADD rol ENUM('jugador','editor','admin') NOT NULL DEFAULT 'jugador';
UPDATE usuarios SET rol = 'admin' WHERE usuario = 'admin';



ALTER TABLE movimiento_trampitas
    ADD partida_id INT NULL,
  ADD pregunta_id INT NULL,
  ADD INDEX idx_mov_usuario (usuario_id),
  ADD INDEX idx_mov_fecha (fecha);


ALTER TABLE compra_trampitas ADD INDEX idx_compra_fecha (fecha);


ALTER TABLE respuestas ADD con_trampita TINYINT(1) NOT NULL DEFAULT 0;



ALTER TABLE usuarios
    MODIFY rol ENUM('jugador','editor','administrador') NOT NULL DEFAULT 'jugador';
UPDATE usuarios SET rol = 'administrador' WHERE usuario = 'admin';


ALTER TABLE usuarios
    ADD fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD pais VARCHAR(80) NULL;

ALTER TABLE preguntas
    ADD fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD creada_por INT NULL;


ALTER TABLE respuestas
    ADD con_trampita TINYINT(1) NOT NULL DEFAULT 0;


ALTER TABLE movimiento_trampitas
    ADD partida_id INT NULL,
    ADD pregunta_id INT NULL,
    ADD INDEX idx_mov_usuario (usuario_id),
    ADD INDEX idx_mov_fecha (fecha);

ALTER TABLE compra_trampitas ADD INDEX idx_compra_fecha (fecha);
