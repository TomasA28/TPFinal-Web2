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
        $sql = "SELECT id, usuario AS nombre_usuario, nombre_completo, foto_perfil,
                       rol, cuenta_validada, trampitas, entorno_id
                FROM usuarios
                WHERE id = $id";

        return $this->database->queryOne($sql);
    }

    public function getPuntaje($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $sql = "SELECT COALESCE(SUM(pj.puntaje), 0) AS puntaje
                FROM partida_jugador pj
                JOIN partida p ON p.id = pj.partida_id
                WHERE pj.usuario_id = $usuarioId
                  AND p.estado = 'finalizada'";

        $fila = $this->database->queryOne($sql);
        return (int)$fila['puntaje'];
    }

    // 1 = primero. Devuelve null si no tiene puntaje (A6)
    public function getPosicion($puntaje, $entornoId)
    {
        $puntaje = (int)$puntaje;
        if ($puntaje === 0) {
            return null;
        }

        $filtroEntorno = $entornoId ? "AND u.entorno_id = " . (int)$entornoId : "";

        $sql = "SELECT COUNT(*) + 1 AS posicion FROM (
                    SELECT pj.usuario_id, SUM(pj.puntaje) AS total
                    FROM partida_jugador pj
                    JOIN partida p ON p.id = pj.partida_id
                    JOIN usuarios u ON u.id = pj.usuario_id
                    WHERE p.estado = 'finalizada' $filtroEntorno
                    GROUP BY pj.usuario_id
                    HAVING total > $puntaje
                ) t";

        $fila = $this->database->queryOne($sql);
        return (int)$fila['posicion'];
    }

    public function getHistorial($usuarioId, $limite = 10)
    {
        $usuarioId = (int)$usuarioId;
        $limite = (int)$limite;

        $sql = "SELECT p.id, p.tipo, p.fecha,
                       mi.puntaje AS mi_puntaje,
                       rival.puntaje AS puntaje_rival,
                       u.id AS rival_id,
                       u.usuario AS rival_nombre
                FROM partida p
                JOIN partida_jugador mi
                     ON mi.partida_id = p.id AND mi.usuario_id = $usuarioId
                LEFT JOIN partida_jugador rival
                     ON rival.partida_id = p.id AND rival.usuario_id <> $usuarioId
                LEFT JOIN usuarios u ON u.id = rival.usuario_id
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
                       u.usuario AS desafiante_nombre
                FROM partida p
                JOIN partida_jugador pj
                     ON pj.partida_id = p.id AND pj.usuario_id = $usuarioId
                JOIN usuarios u ON u.id = p.creador_id
                WHERE p.estado = 'en_espera'
                  AND p.creador_id <> $usuarioId
                ORDER BY p.fecha DESC";

        return $this->database->queryAll($sql);
    }
}