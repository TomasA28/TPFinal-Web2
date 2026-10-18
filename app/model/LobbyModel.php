<?php

namespace app\model;

class LobbyModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuario($id)
    {
        $id = (int)$id;
        $sql = "SELECT id, nombre_usuario, nombre_completo, foto_perfil,
                       rol, cuenta_validada, trampitas, entorno_id
                FROM usuario
                WHERE id = $id";

        $filas = $this->database->queryAll($sql);
        return $filas ? $filas[0] : null;
    }


    public function getPuntaje($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $sql = "SELECT COALESCE(SUM(pj.puntaje), 0) AS puntaje
                FROM partida_jugador pj
                JOIN partida p ON p.id = pj.partida_id
                WHERE pj.usuario_id = $usuarioId
                  AND p.estado = 'finalizada'";

        $filas = $this->database->queryAll($sql);
        return (int)$filas[0]['puntaje'];
    }


    public function getPosicion($puntaje, $entornoId)
    {
        if ($puntaje === 0) {
            return null;
        }

        $puntaje = (int)$puntaje;
        $filtroEntorno = $entornoId ? "AND u.entorno_id = " . (int)$entornoId : "";

        $sql = "SELECT COUNT(*) + 1 AS posicion FROM (
                    SELECT pj.usuario_id, SUM(pj.puntaje) AS total
                    FROM partida_jugador pj
                    JOIN partida p ON p.id = pj.partida_id
                    JOIN usuario u ON u.id = pj.usuario_id
                    WHERE p.estado = 'finalizada' $filtroEntorno
                    GROUP BY pj.usuario_id
                    HAVING total > $puntaje
                ) t";

        $filas = $this->database->queryAll($sql);
        return (int)$filas[0]['posicion'];
    }


    public function getHistorial($usuarioId, $limite = 10)
    {
        $usuarioId = (int)$usuarioId;
        $limite = (int)$limite;

        $sql = "SELECT p.id, p.tipo, p.fecha,
                       mi.puntaje AS mi_puntaje,
                       rival.puntaje AS puntaje_rival,
                       u.id AS rival_id,
                       u.nombre_usuario AS rival_nombre
                FROM partida p
                JOIN partida_jugador mi
                     ON mi.partida_id = p.id AND mi.usuario_id = $usuarioId
                LEFT JOIN partida_jugador rival
                     ON rival.partida_id = p.id AND rival.usuario_id <> $usuarioId
                LEFT JOIN usuario u ON u.id = rival.usuario_id
                WHERE p.estado = 'finalizada'
                ORDER BY p.fecha DESC
                LIMIT $limite";

        return $this->database->queryAll($sql);
    }

    public function getDesafiosPendientes($usuarioId)
    {
        $usuarioId = (int)$usuarioId;

        $sql = "SELECT p.id AS partida_id, p.fecha,
                       u.id AS desafiante_id,
                       u.nombre_usuario AS desafiante_nombre
                FROM partida p
                JOIN partida_jugador pj
                     ON pj.partida_id = p.id AND pj.usuario_id = $usuarioId
                JOIN usuario u ON u.id = p.creador_id
                WHERE p.estado = 'en_espera'
                  AND p.creador_id <> $usuarioId
                ORDER BY p.fecha DESC";

        return $this->database->queryAll($sql);
    }
}