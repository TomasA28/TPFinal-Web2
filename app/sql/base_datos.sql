CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    mail VARCHAR(50) NOT NULL UNIQUE,
    fechaNacimiento DATE NOT NULL
);

INSERT INTO usuarios (usuario, password, mail, fechaNacimiento) VALUES
('admin', MD5('1234'), 'admin@devmail.com', '1998-12-12');

DROP TABLE IF EXISTS preguntas;
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

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS partida_jugador;
DROP TABLE IF EXISTS partida;
DROP TABLE IF EXISTS historial_partidas;

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

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO usuarios (usuario, password, mail, fechaNacimiento) VALUES
                    ('pablo', MD5('1234'), 'pablo@devmail.com', '2000-01-01'),
                ('lucia', MD5('1234'), 'lucia@devmail.com', '2001-02-02'),
                ('mati',  MD5('1234'), 'mati@devmail.com',  '1999-03-03');

INSERT INTO partida (id, fecha) VALUES (1, NOW()), (2, NOW());

INSERT INTO partida_jugador (partida_id, usuario_id, puntaje) VALUES
                            (1, 1, 50), (1, 2, 80), (2, 1, 30), (2, 3, 100);