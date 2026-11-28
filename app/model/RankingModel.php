<?php

namespace app\model;

class RankingModel
{
    private const PERIODOS = [
        'dia' => 'DATE(p.fecha) = CURDATE()',
        'semana' => 'DATE(p.fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
        'mes' => 'DATE(p.fecha) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
    ];

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function periodoValido($periodo)
    {
        return is_string($periodo) && isset(self::PERIODOS[$periodo]) ? $periodo : 'mes';
    }

    public function getRanking($periodo = 'mes')
    {
        $periodo = $this->periodoValido($periodo);

        return $this->database->queryAll(
            "SELECT u.id, u.usuario, COALESCE(puntajes.puntaje, 0) AS puntaje
             FROM usuarios u
             LEFT JOIN (
                 SELECT pj.usuario_id, SUM(pj.puntaje) AS puntaje
                 FROM partida_jugador pj
                 JOIN partida p ON p.id = pj.partida_id
                 WHERE " . self::PERIODOS[$periodo] . "
                 GROUP BY pj.usuario_id
             ) puntajes ON puntajes.usuario_id = u.id
             ORDER BY puntaje DESC, u.usuario ASC"
        );
    }
}
