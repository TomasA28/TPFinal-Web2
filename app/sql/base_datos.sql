CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL
);

INSERT INTO usuarios (usuario, password) VALUES
('admin', MD5('1234'));



CREATE TABLE partida (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        puntaje INT DEFAULT 0,
        fecha DATETIME,
        FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);