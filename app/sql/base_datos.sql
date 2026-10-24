CREATE DATABASE IF NOT EXISTS my_web CHARACTER SET utf8mb4;
USE my_web;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL,
    mail VARCHAR(50) NOT NULL UNIQUE,
    fechaNacimiento DATE NOT NULL
);

INSERT INTO usuarios (usuario, password) VALUES
('admin', MD5('1234'));
