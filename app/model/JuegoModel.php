<?php
namespace app\model;

class JuegoModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function getPreguntaAleatoria($categoria, $usuarioId)
    {
        $estadisticasUsuario = $this->getEstadisticasUsuario($usuarioId);
        $filtroTasa = null;

        if ($estadisticasUsuario['respuestas'] >= 10) {
            if ($estadisticasUsuario['tasa_acierto'] < 30) {
                $filtroTasa = '< 30';
            } elseif ($estadisticasUsuario['tasa_acierto'] > 70) {
                $filtroTasa = '> 70';
            }
        }

        $pregunta = $this->buscarPreguntaAleatoria($categoria, $usuarioId, true, $filtroTasa);
        if ($pregunta !== null) {
            return $pregunta;
        }

        $preguntaNueva = $this->buscarPreguntaAleatoria($categoria, $usuarioId, true);
        if ($preguntaNueva !== null) {
            return $preguntaNueva;
        }

        return $this->buscarPreguntaAleatoria($categoria, $usuarioId, false);
    }

    private function buscarPreguntaAleatoria($categoria, $usuarioId, $soloNuevas, $filtroTasa = null)
    {
        $sql = "SELECT p.*, COUNT(r.id) AS respuestas_totales,
                       AVG(r.es_correcta) * 100 AS tasa_acierto
                FROM preguntas p
                LEFT JOIN respuestas r ON r.pregunta_id = p.id
                WHERE p.categoria = ?";
        $params = [$categoria];

        if ($soloNuevas) {
            $sql .= " AND NOT EXISTS (
                          SELECT 1
                          FROM respuestas respondidas
                          WHERE respondidas.pregunta_id = p.id
                            AND respondidas.usuario_id = ?
                      )";
            $params[] = (int) $usuarioId;
        }

        $sql .= " GROUP BY p.id";

        if ($filtroTasa !== null) {
            $sql .= " HAVING COUNT(r.id) >= 10 AND AVG(r.es_correcta) $filtroTasa";
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        return $this->database->queryOne($sql, $params);
    }

    public function procesarRespuesta($respuestaElegida, $preguntaId, $usuarioId)
    {
        if (!\in_array($respuestaElegida, ['A', 'B', 'C', 'D'], true)) {
            return null;
        }

        $preguntaId = (int) $preguntaId;
        $usuarioId = (int) $usuarioId;
        $respuestaCorrecta = $this->database->queryOne(
            "SELECT respuesta_correcta FROM preguntas WHERE id = ?",
            [$preguntaId]
        );

        if ($respuestaCorrecta === null) {
            return null;
        }

        $esCorrecta = $respuestaCorrecta['respuesta_correcta'] === $respuestaElegida;
        $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, es_correcta) VALUES (?, ?, ?)",
            [$usuarioId, $preguntaId, $esCorrecta ? 1 : 0]
        );

        return $esCorrecta;
    }

    private function getEstadisticasUsuario($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        return $this->database->queryOne(
            "SELECT COUNT(*) AS respuestas,
                    COALESCE(AVG(es_correcta) * 100, 0) AS tasa_acierto
             FROM respuestas
             WHERE usuario_id = ?",
            [$usuarioId]
        );
    }

    public function guardarPartidaTerminada($usuarioId, $puntajeFinal)
    {
        $usuarioId = (int) $usuarioId;
        $puntajeFinal = (int) $puntajeFinal;

        $this->database->execute(
            "INSERT INTO partida (fecha) VALUES (NOW())"
        );

        $partidaId = (int) $this->database->lastInsertId();

        $this->database->execute(
            "INSERT INTO partida_jugador (partida_id, usuario_id, puntaje) VALUES (?, ?, ?)",
            [$partidaId, $usuarioId, $puntajeFinal]
        );
    }

}