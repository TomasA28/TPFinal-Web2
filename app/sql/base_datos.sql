CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

DROP TABLE IF EXISTS respuestas;
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
 MD5('1234'), 'SoyElAdmin99', 'fotoDelAdmin.png', NULL, '1');

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
    '¿Quién fue el primer Director Supremo de las Provincias Unidas del Río de la Plata?',
    'Manuel Belgrano',
    'Gervasio Antonio de Posadas',
    'Cornelio Saavedra',
    'Bernardino Rivadavia',
    'B',
    'Historia'
),
(
    '¿Cuál es el río más largo del mundo?',
    'Río Nilo',
    'Río Amazonas',
    'Río Yangtsé',
    'Río Misisipi',
    'B',
    'Cultura'
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

