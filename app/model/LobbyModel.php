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
        $idUsuario = (int) $idUsuario;
        return $this->database->queryOne(
            "SELECT u.id, u.usuario, COALESCE(SUM(pj.puntaje), 0) AS puntaje
             FROM usuarios u
             LEFT JOIN partida_jugador pj ON pj.usuario_id = u.id
             WHERE u.id = $idUsuario
             GROUP BY u.id, u.usuario"
        );
    }

    public function getPartidas($idUsuario)
    {
        $idUsuario = (int) $idUsuario;
        return $this->database->queryAll(
            "SELECT p.id, pj.puntaje, p.fecha
             FROM partida_jugador pj
             JOIN partida p ON p.id = pj.partida_id
             WHERE pj.usuario_id = $idUsuario
             ORDER BY p.fecha DESC"
        );
    }
}