<?php

namespace app\model;

class LobbyModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuario($idUsuario)
    {
        return $this->database->queryOne(
            "SELECT u.id, u.usuario, COALESCE(SUM(pj.puntaje), 0) AS puntaje
         FROM usuarios u
         LEFT JOIN partida_jugador pj ON pj.usuario_id = u.id
         WHERE u.id = ?
         GROUP BY u.id, u.usuario",
            [(int) $idUsuario]
        );
    }

    public function getPartidas($idUsuario)
    {
        $idUsuario = (int) $idUsuario;

        return $this->database->queryAll(
            "SELECT * 
        FROM historial_partidas hp 
        WHERE hp.usuario_id = $idUsuario
        ORDER BY hp.fecha DESC, hp.id DESC
        LIMIT 5"
        );
    }
}