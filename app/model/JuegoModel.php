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
            // A un jugador con pocos aciertos le convienen preguntas fáciles;
            // a quien acierta mucho, preguntas difíciles.
            if ($estadisticasUsuario['tasa_acierto'] < 30) {
                $filtroTasa = '> 70';
            } elseif ($estadisticasUsuario['tasa_acierto'] > 70) {
                $filtroTasa = '< 30';
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

    public function tienePartidaVsBotActiva($usuarioId)
    {
        return $this->database->queryOne(
                "SELECT p.id
             FROM partida p
             INNER JOIN partida_jugador pj ON pj.partida_id = p.id
             WHERE p.tipo = 'vs_bot' AND p.estado = 'en_curso' AND pj.usuario_id = ?
             LIMIT 1",
                [(int) $usuarioId]
            ) !== null;
    }

    public function cerrarPartidaVsBotActiva($usuarioId)
    {
        $usuarioId = (int) $usuarioId;
        $this->database->begin();
        try {
            $this->database->queryOne(
                "SELECT id FROM usuarios WHERE id = ? FOR UPDATE",
                [$usuarioId]
            );
            $cerradas = $this->database->execute(
                "UPDATE partida p
                 INNER JOIN partida_jugador pj_jugador ON pj_jugador.partida_id = p.id
                 INNER JOIN partida_jugador pj_bot
                     ON pj_bot.partida_id = p.id AND pj_bot.usuario_id <> pj_jugador.usuario_id
                 INNER JOIN usuarios u_bot ON u_bot.id = pj_bot.usuario_id AND u_bot.es_bot = 1
                 SET p.estado = 'finalizada', p.ganador_id = pj_bot.usuario_id
                 WHERE p.tipo = 'vs_bot' AND p.estado = 'en_curso'
                   AND pj_jugador.usuario_id = ?",
                [$usuarioId]
            );
            $this->database->execute(
                "DELETE FROM preguntas_activas WHERE usuario_id = ?",
                [$usuarioId]
            );
            $this->database->commit();
            return $cerradas > 0 || !$this->tienePartidaVsBotActiva($usuarioId);
        } catch (\Throwable $e) {
            $this->database->rollback();
            error_log('JuegoModel::cerrarPartidaVsBotActiva falló: ' . $e->getMessage());
            return false;
        }
    }

    private function buscarPreguntaAleatoria($categoria, $usuarioId, $soloNuevas, $filtroTasa = null)
    {
        $sql = "SELECT p.*, COUNT(CASE WHEN u.es_bot = 0 AND r.con_trampita = 0 THEN r.id END) AS respuestas_totales,
                       AVG(CASE WHEN u.es_bot = 0 AND r.con_trampita = 0 THEN r.es_correcta END) * 100 AS tasa_acierto
                FROM preguntas p
                LEFT JOIN respuestas r ON r.pregunta_id = p.id
                LEFT JOIN usuarios u ON u.id = r.usuario_id
                WHERE p.categoria = ? AND p.activa = 1";
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
            $sql .= " HAVING COUNT(CASE WHEN u.es_bot = 0 AND r.con_trampita = 0 THEN r.id END) >= 10
                      AND AVG(CASE WHEN u.es_bot = 0 AND r.con_trampita = 0 THEN r.es_correcta END) $filtroTasa";
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        return $this->database->queryOne($sql, $params);
    }

    public function getPreguntasPorIds($ids)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $ids)));
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        if (count($ids) === 0) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $filas = $this->database->queryAll(
            "SELECT id, descripcion, categoria
             FROM preguntas
             WHERE id IN ($marcas)",
            $ids
        );

        $porId = [];
        foreach ($filas as $fila) {
            $porId[(int) $fila['id']] = $fila;
        }

        $ordenadas = [];
        foreach ($ids as $id) {
            if (isset($porId[$id])) {
                $ordenadas[] = $porId[$id];
            }
        }

        return $ordenadas;
    }

    public function getPreguntaIdActiva($usuarioId)
    {
        $fila = $this->database->queryOne(
            "SELECT pregunta_id FROM preguntas_activas WHERE usuario_id = ?",
            [(int) $usuarioId]
        );
        return $fila ? (int) $fila['pregunta_id'] : null;
    }

    public function procesarRespuesta($respuestaElegida, $usuarioId)
    {
        $usuarioId = (int) $usuarioId;
        $preguntaActiva = $this->database->queryOne(
            "SELECT pa.pregunta_id, p.respuesta_correcta, (pa.expira_en <= NOW()) AS expirada
             FROM preguntas_activas pa
             INNER JOIN preguntas p ON p.id = pa.pregunta_id
             WHERE pa.usuario_id = ?",
            [$usuarioId]
        );

        if ($preguntaActiva === null) {
            return null;
        }

        $preguntaId = (int) $preguntaActiva['pregunta_id'];
        $expirada = (bool) $preguntaActiva['expirada'];
        if (!$expirada && !\in_array($respuestaElegida, ['A', 'B', 'C', 'D'], true)) {
            return null;
        }

        // Consume el turno antes de guardar el resultado: dos envíos simultáneos
        // no pueden registrar dos respuestas para la misma pregunta.
        $consumida = $this->database->execute(
            "DELETE FROM preguntas_activas WHERE usuario_id = ? AND pregunta_id = ?",
            [$usuarioId, $preguntaId]
        );
        if ($consumida < 1) {
            return null;
        }

        $esCorrecta = !$expirada
            && $preguntaActiva['respuesta_correcta'] === $respuestaElegida;
        $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, es_correcta) VALUES (?, ?, ?)",
            [$usuarioId, $preguntaId, $esCorrecta ? 1 : 0]
        );
        return ['es_correcta' => $esCorrecta, 'pregunta_id' => $preguntaId];
    }

    private function getEstadisticasUsuario($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        return $this->database->queryOne(
            "SELECT COUNT(*) AS respuestas,
                    COALESCE(AVG(es_correcta) * 100, 0) AS tasa_acierto
             FROM respuestas r
             INNER JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.usuario_id = ? AND u.es_bot = 0 AND r.con_trampita = 0",
            [$usuarioId]
        );
    }

    public function guardarPartidaTerminada($usuarioId, $puntajeFinal, $totalPreguntas = 0)
    {
        $usuarioId = (int) $usuarioId;
        $puntajeFinal = (int) $puntajeFinal;
        $totalPreguntas = (int) $totalPreguntas;

        $this->database->execute(
            "INSERT INTO partida (tipo, estado, total_preguntas) VALUES ('solo', 'finalizada', ?)",
            [$totalPreguntas]
        );

        $partidaId = (int) $this->database->lastInsertId();

        $this->database->execute(
            "INSERT INTO partida_jugador (partida_id, usuario_id, puntaje) VALUES (?, ?, ?)",
            [$partidaId, $usuarioId, $puntajeFinal]
        );
    }


    public function procesarRespuestaConTrampita($usuarioId)
    {
        $usuarioId  = (int) $usuarioId;

        $activa = $this->database->queryOne(
            "SELECT p.respuesta_correcta, (pa.expira_en <= NOW()) AS expirada
         FROM preguntas_activas pa
         INNER JOIN preguntas p ON p.id = pa.pregunta_id
         WHERE pa.usuario_id = ?",
            [$usuarioId]
        );

        if ($activa === null || (bool) $activa['expirada']) {
            return null;
        }

        $preguntaId = $this->getPreguntaIdActiva($usuarioId);
        if ($preguntaId === null) {
            return null;
        }
        $consumida = $this->database->execute(
            "DELETE FROM preguntas_activas WHERE usuario_id = ? AND pregunta_id = ?",
            [$usuarioId, $preguntaId]
        );
        if ($consumida < 1) {
            return null;
        }

        $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, es_correcta, respuesta_elegida)
         VALUES (?, ?, 1, ?)",
            [$usuarioId, $preguntaId, $activa['respuesta_correcta']]
        );

        return ['es_correcta' => true, 'pregunta_id' => $preguntaId];
    }


    public function getJuegoController()
    {
        return new JuegoController(
            $this->getJuegoModel(),
            $this->getRender(),
            $this->getTrampitasModel()
        );
    }
}
