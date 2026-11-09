CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

DROP TABLE IF EXISTS respuestas;
DROP TABLE IF EXISTS preguntas_activas;
DROP TABLE IF EXISTS historial_partidas;
DROP TABLE IF EXISTS partida_jugador;
DROP TABLE IF EXISTS partida;
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
    token VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL
);

INSERT INTO usuarios (nombre, apellido, fechaNacimiento, sexo, lat, lng,
 mail, password, usuario, fotoDePerfil, token, activo) VALUES
('Juan', 'Perez', '1998-12-12', '1', '-34.665336611141214', '-58.37326118991368', 'admin@devmail.com',
 MD5('1234'), 'admin', 'fotoDelAdmin.png', NULL, '1');

CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion TEXT NOT NULL,
    opcion_a VARCHAR(255) NOT NULL,
    opcion_b VARCHAR(255) NOT NULL,
    opcion_c VARCHAR(255) NOT NULL,
    opcion_d VARCHAR(255) NOT NULL,
    respuesta_correcta CHAR(1) NOT NULL,
    categoria VARCHAR(50) NOT NULL
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