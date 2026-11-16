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
            "SELECT u.id, u.usuario AS nombre,
                    (
                        SELECT pj.puntaje
                        FROM partida_jugador pj
                        JOIN partida p ON p.id = pj.partida_id
                        WHERE pj.usuario_id = u.id
                        ORDER BY p.fecha DESC, pj.id DESC
                        LIMIT 1
                    ) AS puntaje,
                    (
                        SELECT COALESCE(SUM(pj2.puntaje), 0)
                        FROM partida_jugador pj2
                        WHERE pj2.usuario_id = u.id
                    ) AS puntajeTotal
             FROM usuarios u
             WHERE u.id = ?
             LIMIT 1",
            [(int) $idUsuario]
        );
    }

    public function getPartidas($idUsuario)
    {
        $idUsuario = (int) $idUsuario;

        return $this->database->queryAll(
            "SELECT p.id, pj.puntaje, p.fecha
             FROM partida_jugador pj
             JOIN partida p ON p.id = pj.partida_id
             WHERE pj.usuario_id = ?
             ORDER BY p.fecha DESC",
            [$idUsuario]
        );
    }
}
