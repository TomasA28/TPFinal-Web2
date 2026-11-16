<?php

namespace app\model;

class PartidaModel
{
    private $database;
    private $bot;
    private $trampitas;

    private const SEGUNDOS_POR_PREGUNTA = 20;
    private const SEGUNDOS_DE_GRACIA    = 2;
    private const PREGUNTAS_POR_PARTIDA = 10;
    private const COLOR_DEFAULT         = '#7f8c8d';
    private const COLORES = [
        'Historia' => '#e67e22',
        'Cultura'  => '#2980b9',
        'Deporte'  => '#27ae60',
        'Arte'     => '#8e44ad',
        'Ciencia'  => '#16a085',
    ];

    public function __construct($database)
    {
        $this->database  = $database;
        $this->bot       = new BotService();
        $this->trampitas = new TrampitasModel($database);
    }



    public function getSegundosPorPregunta()
    {
        return self::SEGUNDOS_POR_PREGUNTA;
    }

    public function getSaldoTrampitas($usuarioId)
    {
        return $this->trampitas->getSaldo($usuarioId);
    }


    public function crearPartidaVsBot($jugadorId, $cantidad = self::PREGUNTAS_POR_PARTIDA)
    {
        $jugadorId = (int)$jugadorId;

        $botId = $this->elegirBot();
        if (!$botId) {
            return null;
        }

        $preguntas = $this->seleccionarPreguntas($jugadorId, (int)$cantidad);
        if (count($preguntas) === 0) {
            return null;
        }

        $partidaId = $this->insertarPartida(count($preguntas), $jugadorId, $botId);
        $this->asignarPreguntas($partidaId, $preguntas);

        // Limpia un reloj abandonado de una partida anterior
        $this->database->execute("DELETE FROM preguntas_activas WHERE usuario_id = ?", [$jugadorId]);

        return $partidaId;
    }


    public function getPartida($partidaId)
    {
        return $this->database->queryOne(
            "SELECT pa.*, uh.id AS jugador1_id, ub.id AS jugador2_id, ub.usuario AS bot_nombre
             FROM partida pa
             JOIN partida_jugador pjh ON pjh.partida_id = pa.id
             JOIN usuarios uh ON uh.id = pjh.usuario_id AND uh.es_bot = 0
             JOIN partida_jugador pjb ON pjb.partida_id = pa.id
             JOIN usuarios ub ON ub.id = pjb.usuario_id AND ub.es_bot = 1
             WHERE pa.id = ? AND pa.tipo = 'vs_bot'",
            [(int)$partidaId]
        );
    }


    public function getPreguntaActual($partida)
    {
        $p = $this->database->queryOne(
            "SELECT p.id, p.descripcion AS enunciado,
                    p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d, p.categoria
             FROM partida_pregunta pp
             JOIN preguntas p ON p.id = pp.pregunta_id
             WHERE pp.partida_id = ? AND pp.orden = ?",
            [(int)$partida['id'], (int)$partida['orden_actual']]
        );
        if (!$p) {
            return null;
        }

        $p['color']    = self::COLORES[$p['categoria']] ?? self::COLOR_DEFAULT;
        $p['opciones'] = [
            ['letra' => 'A', 'texto' => $p['opcion_a']],
            ['letra' => 'B', 'texto' => $p['opcion_b']],
            ['letra' => 'C', 'texto' => $p['opcion_c']],
            ['letra' => 'D', 'texto' => $p['opcion_d']],
        ];

        $this->iniciarRelojSiHaceFalta((int)$partida['jugador1_id'], (int)$p['id']);

        return $p;
    }

    public function getSegundosRestantes($usuarioId, $preguntaId)
    {
        $r = $this->database->queryOne(
            "SELECT GREATEST(TIMESTAMPDIFF(SECOND, NOW(), expira_en), 0) AS s
             FROM preguntas_activas
             WHERE usuario_id = ? AND pregunta_id = ?",
            [(int)$usuarioId, (int)$preguntaId]
        );
        return $r ? (int)$r['s'] : 0;
    }

    public function getMarcador($partidaId)
    {
        $fila = $this->database->queryOne(
            "SELECT COALESCE(SUM(CASE WHEN u.es_bot = 0 THEN pj.puntaje END), 0) AS humano,
                    COALESCE(SUM(CASE WHEN u.es_bot = 1 THEN pj.puntaje END), 0) AS bot
             FROM partida_jugador pj
             JOIN usuarios u ON u.id = pj.usuario_id
             WHERE pj.partida_id = ?",
            [(int)$partidaId]
        );
        return ['humano' => (int)$fila['humano'], 'bot' => (int)$fila['bot']];
    }

    public function getResultado($partidaId)
    {
        $partida = $this->getPartida($partidaId);
        if (!$partida) {
            return null;
        }
        $m = $this->getMarcador($partidaId);
        $partida['puntos_humano'] = $m['humano'];
        $partida['puntos_bot']    = $m['bot'];
        $partida['empate']        = $partida['ganador_id'] === null;
        $partida['gano_humano']   = (int)$partida['ganador_id'] === (int)$partida['jugador1_id'];
        return $partida;
    }


    public function responder($partidaId, $jugadorId, $orden, $letra, $pideTrampita = false)
    {
        $partida = $this->getPartida($partidaId);
        $letra   = strtoupper(trim((string)$letra));

        if (!$this->esJugadaValida($partida, $jugadorId, $orden, $letra)) {
            return null;
        }

        $humanoId = (int)$partida['jugador1_id'];
        $pregunta = $this->getPreguntaDeOrden($partida['id'], $orden);

        // Atómico: solo UN request puede consumir el reloj de esta pregunta
        $enTiempo = $this->consumirReloj($humanoId, $pregunta['id']);

        // La trampita solo vale en tiempo y se descuenta en el servidor (atómico)
        $usaTrampita   = $pideTrampita && $enTiempo && $this->trampitas->usar($humanoId);
        $tiempoAgotado = !$enTiempo || ($letra === '' && !$usaTrampita);

        $humano = $this->resolverJugadaHumano($letra, $pregunta['respuesta_correcta'], $tiempoAgotado, $usaTrampita);
        $bot    = $this->resolverJugadaBot($partida['jugador2_id'], $pregunta);


        try {
            $this->registrarJugada($partida, $pregunta['id'], $humano, $bot);
        } catch (\Throwable $e) {
            return null;
        }

        $terminada = $this->avanzarOFinalizar($partida);

        return [
            'humano_acierta' => $humano['acierta'],
            'bot_acierta'    => $bot['acierta'],
            'letra_correcta' => $pregunta['respuesta_correcta'],
            'letra_humano'   => $humano['letra'],
            'letra_bot'      => $bot['letra'],
            'tiempo_agotado' => $tiempoAgotado,
            'trampita_usada' => $usaTrampita,
            'terminada'      => $terminada,
        ];
    }



    private function elegirBot()
    {
        $bot = $this->database->queryOne(
            "SELECT id FROM usuarios WHERE es_bot = 1 AND activo = 1 ORDER BY RAND() LIMIT 1"
        );
        return $bot ? (int)$bot['id'] : null;
    }


    private function seleccionarPreguntas($jugadorId, $cantidad)
    {
        $cantidad = max(1, (int)$cantidad);

        $preguntas = $this->database->queryAll(
            "SELECT id FROM preguntas
             WHERE id NOT IN (SELECT pregunta_id FROM respuestas WHERE usuario_id = ?)
             ORDER BY RAND() LIMIT $cantidad",
            [$jugadorId]
        );

        $faltan = $cantidad - count($preguntas);
        if ($faltan > 0) {
            $ids     = array_map('intval', array_column($preguntas, 'id'));
            $excluir = $ids ? implode(',', $ids) : '0';
            $extra   = $this->database->queryAll(
                "SELECT id FROM preguntas WHERE id NOT IN ($excluir) ORDER BY RAND() LIMIT $faltan"
            );
            $preguntas = array_merge($preguntas, $extra);
        }
        return $preguntas;
    }

    private function insertarPartida($total, $jugadorId, $botId)
    {
        $this->database->execute(
            "INSERT INTO partida (tipo, estado, total_preguntas) VALUES ('vs_bot', 'en_curso', ?)",
            [(int)$total]
        );
        $partidaId = (int)$this->database->lastInsertId();

        $this->database->execute(
            "INSERT INTO partida_jugador (partida_id, usuario_id, puntaje) VALUES (?, ?, 0), (?, ?, 0)",
            [$partidaId, $jugadorId, $partidaId, $botId]
        );
        return $partidaId;
    }

    private function asignarPreguntas($partidaId, $preguntas)
    {
        $orden = 1;
        foreach ($preguntas as $p) {
            $this->database->execute(
                "INSERT INTO partida_pregunta (partida_id, orden, pregunta_id) VALUES (?, ?, ?)",
                [$partidaId, $orden, (int)$p['id']]
            );
            $orden++;
        }
    }


    private function iniciarRelojSiHaceFalta($usuarioId, $preguntaId)
    {
        $activa = $this->database->queryOne(
            "SELECT pregunta_id FROM preguntas_activas WHERE usuario_id = ? AND pregunta_id = ?",
            [$usuarioId, $preguntaId]
        );
        if (!$activa) {
            $seg = (int)self::SEGUNDOS_POR_PREGUNTA;
            $this->database->execute(
                "REPLACE INTO preguntas_activas (usuario_id, pregunta_id, expira_en)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL $seg SECOND))",
                [$usuarioId, $preguntaId]
            );
        }
    }


    private function consumirReloj($usuarioId, $preguntaId)
    {
        $gracia = (int)self::SEGUNDOS_DE_GRACIA;

        $filas = $this->database->execute(
            "DELETE FROM preguntas_activas
             WHERE usuario_id = ? AND pregunta_id = ?
               AND DATE_ADD(expira_en, INTERVAL $gracia SECOND) > NOW()",
            [(int)$usuarioId, (int)$preguntaId]
        );

        // Limpia restos (reloj vencido u otra pregunta)
        $this->database->execute("DELETE FROM preguntas_activas WHERE usuario_id = ?", [(int)$usuarioId]);

        return $filas > 0;
    }



    private function esJugadaValida($partida, $jugadorId, $orden, $letra)
    {
        return $partida
            && (int)$partida['jugador1_id'] === (int)$jugadorId
            && $partida['estado'] === 'en_curso'
            && (int)$partida['orden_actual'] === (int)$orden
            && in_array($letra, ['', 'A', 'B', 'C', 'D'], true);   // '' = sin letra (timeout o trampita)
    }

    private function getPreguntaDeOrden($partidaId, $orden)
    {
        return $this->database->queryOne(
            "SELECT p.* FROM partida_pregunta pp
             JOIN preguntas p ON p.id = pp.pregunta_id
             WHERE pp.partida_id = ? AND pp.orden = ?",
            [(int)$partidaId, (int)$orden]
        );
    }


    private function getRatioPregunta($preguntaId)
    {
        $est = $this->database->queryOne(
            "SELECT COUNT(*) AS total, COALESCE(SUM(r.es_correcta), 0) AS ok
             FROM respuestas r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.pregunta_id = ? AND u.es_bot = 0 AND r.con_trampita = 0",
            [(int)$preguntaId]
        );
        return (int)$est['total'] > 0 ? (float)$est['ok'] / (int)$est['total'] : 0.5;
    }

    private function resolverJugadaHumano($letra, $correcta, $tiempoAgotado, $usaTrampita)
    {
        if ($usaTrampita) {
            return ['letra' => $correcta, 'acierta' => true, 'trampita' => true];
        }
        return [
            'letra'    => $tiempoAgotado ? null : $letra,
            'acierta'  => !$tiempoAgotado && $letra === $correcta,
            'trampita' => false,
        ];
    }

    private function resolverJugadaBot($botId, $pregunta)
    {
        $bot   = $this->database->queryOne("SELECT habilidad FROM usuarios WHERE id = ?", [(int)$botId]);
        $ratio = $this->getRatioPregunta($pregunta['id']);   // ANTES de registrar la jugada
        $letra = $this->bot->elegirRespuesta((float)$bot['habilidad'], $ratio, $pregunta['respuesta_correcta']);

        return [
            'id'      => (int)$botId,
            'letra'   => $letra,
            'acierta' => $letra === $pregunta['respuesta_correcta'],
        ];
    }

    private function registrarJugada($partida, $preguntaId, $humano, $bot)
    {
        $pid      = (int)$partida['id'];
        $humanoId = (int)$partida['jugador1_id'];

        $this->guardarRespuesta($pid, $humanoId, $preguntaId, $humano['letra'], $humano['acierta'], $humano['trampita']);
        $this->guardarRespuesta($pid, $bot['id'], $preguntaId, $bot['letra'], $bot['acierta'], false);

        if ($humano['acierta']) { $this->sumarPunto($pid, $humanoId); }
        if ($bot['acierta'])    { $this->sumarPunto($pid, $bot['id']); }
    }

    private function guardarRespuesta($pid, $usuarioId, $preguntaId, $letra, $acierta, $conTrampita)
    {
        $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, es_correcta, partida_id, respuesta_elegida, con_trampita)
             VALUES (?, ?, ?, ?, ?, ?)",
            [(int)$usuarioId, (int)$preguntaId, (int)$acierta, (int)$pid, $letra, (int)$conTrampita]
        );
    }

    private function sumarPunto($pid, $usuarioId)
    {
        $this->database->execute(
            "UPDATE partida_jugador SET puntaje = puntaje + 1 WHERE partida_id = ? AND usuario_id = ?",
            [(int)$pid, (int)$usuarioId]
        );
    }


    private function avanzarOFinalizar($partida)
    {
        $terminada = (int)$partida['orden_actual'] >= (int)$partida['total_preguntas'];

        if ($terminada) {
            $this->finalizar($partida);
        } else {
            $this->database->execute(
                "UPDATE partida SET orden_actual = orden_actual + 1 WHERE id = ?",
                [(int)$partida['id']]
            );
        }
        return $terminada;
    }

    private function finalizar($partida)
    {
        $m = $this->getMarcador($partida['id']);

        if ($m['humano'] > $m['bot'])     { $ganador = (int)$partida['jugador1_id']; }
        elseif ($m['bot'] > $m['humano']) { $ganador = (int)$partida['jugador2_id']; }
        else                              { $ganador = null; }   // empate

        $this->database->execute(
            "UPDATE partida SET estado = 'finalizada', ganador_id = ? WHERE id = ?",
            [$ganador, (int)$partida['id']]
        );
    }
}