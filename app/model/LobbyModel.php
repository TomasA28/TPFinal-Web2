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
            "SELECT u.usuario AS nombre,
                    (
                        SELECT hp.puntaje
                        FROM historial_partidas hp
                        WHERE hp.usuario_id = u.id
                        ORDER BY hp.fecha DESC, hp.id DESC
                        LIMIT 1
                    ) AS puntaje
             FROM usuarios u
             WHERE u.id = ?
             LIMIT 1",
            [$idUsuario]
        );
    }

    /*public function getPartidas($idUsuario)
    {
        $idUsuario = (int) $idUsuario;
        return $this->database->queryAll(
            "SELECT p.id, pj.puntaje, p.fecha
             FROM partida_jugador pj
             JOIN partida p ON p.id = pj.partida_id
             WHERE pj.usuario_id = $idUsuario
             ORDER BY p.fecha DESC"
        );
    }*/
}