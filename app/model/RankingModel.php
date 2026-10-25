<?php

namespace app\model;

class RankingModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getRanking()
    {
        return $this->database->queryAll(
            "SELECT u.usuario, COALESCE(SUM(pj.puntaje), 0) AS puntaje
         FROM usuarios u
         LEFT JOIN partida_jugador pj ON pj.usuario_id = u.id
         GROUP BY u.id, u.usuario
         ORDER BY puntaje DESC"
        );
    }
}