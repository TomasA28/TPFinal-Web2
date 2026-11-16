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

    public function guardarPreguntaActiva($usuarioId, $preguntaId)
    {
        $this->database->execute(
            "INSERT INTO preguntas_activas (usuario_id, pregunta_id, expira_en)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 SECOND))
             ON DUPLICATE KEY UPDATE
                pregunta_id = VALUES(pregunta_id),
                expira_en = VALUES(expira_en)",
            [(int) $usuarioId, (int) $preguntaId]
        );

        return $this->getPreguntaActiva($usuarioId, $preguntaId);
    }

    public function getPreguntaActiva($usuarioId, $preguntaId)
    {
        return $this->database->queryOne(
            "SELECT p.*, UNIX_TIMESTAMP(pa.expira_en) AS expira_en
             FROM preguntas_activas pa
             INNER JOIN preguntas p ON p.id = pa.pregunta_id
             WHERE pa.usuario_id = ? AND pa.pregunta_id = ?",
            [(int) $usuarioId, (int) $preguntaId]
        );
    }

    public function limpiarPreguntaActiva($usuarioId)
    {
        $this->database->execute(
            "DELETE FROM preguntas_activas WHERE usuario_id = ?",
            [(int) $usuarioId]
        );
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
        $preguntaId = (int) $preguntaId;
        $usuarioId = (int) $usuarioId;
        $preguntaActiva = $this->database->queryOne(
            "SELECT p.respuesta_correcta, (pa.expira_en <= NOW()) AS expirada
             FROM preguntas_activas pa
             INNER JOIN preguntas p ON p.id = pa.pregunta_id
             WHERE pa.usuario_id = ? AND pa.pregunta_id = ?",
            [$usuarioId, $preguntaId]
        );

        if ($preguntaActiva === null) {
            return null;
        }

        $expirada = (bool) $preguntaActiva['expirada'];
        if (!$expirada && !\in_array($respuestaElegida, ['A', 'B', 'C', 'D'], true)) {
            return null;
        }

        $esCorrecta = !$expirada
            && $preguntaActiva['respuesta_correcta'] === $respuestaElegida;
        $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, es_correcta) VALUES (?, ?, ?)",
            [$usuarioId, $preguntaId, $esCorrecta ? 1 : 0]
        );
        $this->limpiarPreguntaActiva($usuarioId);

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