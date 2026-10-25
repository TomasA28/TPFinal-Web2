<?php

namespace app\model;

class PartidaModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPartidasPorUsuario($idUsuario)
    {
        $idUsuario = (int) $idUsuario;
        return $this->database->queryAll(
            "SELECT id, puntaje, fecha FROM partida
             WHERE id_usuario = $idUsuario ORDER BY fecha DESC"
        );
    }

    public function crearPartida($idUsuario)
    {
        $idUsuario = (int) $idUsuario;
        $this->database->execute(
            "INSERT INTO partida (id_usuario, puntaje, fecha) VALUES ($idUsuario, 0, NOW())"
        );
        return $this->database->lastInsertId();
    }
}