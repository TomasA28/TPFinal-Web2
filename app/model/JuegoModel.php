<?php
namespace App\Model;

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

        if ($filtroTasa !== null) {
            $pregunta = $this->database->queryOne(
                "SELECT p.*, COUNT(r.id) AS respuestas_totales,
                        AVG(r.es_correcta) * 100 AS tasa_acierto
                 FROM preguntas p
                 LEFT JOIN respuestas r ON r.pregunta_id = p.id
                 WHERE p.categoria = ?
                 GROUP BY p.id
                 HAVING COUNT(r.id) >= 10 AND AVG(r.es_correcta) $filtroTasa
                 ORDER BY RAND()
                 LIMIT 1",
                [$categoria]
            );

            if ($pregunta !== null) {
                return $pregunta;
            }
        }

        return $this->database->queryOne(
            "SELECT p.*, COUNT(r.id) AS respuestas_totales,
                    AVG(r.es_correcta) * 100 AS tasa_acierto
             FROM preguntas p
             LEFT JOIN respuestas r ON r.pregunta_id = p.id
             WHERE p.categoria = ?
             GROUP BY p.id
             ORDER BY RAND()
             LIMIT 1",
            [$categoria]
        );
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
        $this->database->execute("INSERT INTO historial_partidas (usuario_id, puntaje) 
        VALUES ('$usuarioId', '$puntajeFinal')");
    }

}