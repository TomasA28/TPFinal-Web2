<?php

namespace app\model;

class ReportesModel
{
    public const MOTIVOS = [
        'respuesta_incorrecta' => 'La respuesta correcta está mal',
        'mal_formulada'        => 'El enunciado o las opciones están mal redactados',
        'ambigua'              => 'La pregunta es ambigua o admite más de una respuesta',
        'ofensiva'             => 'Contenido ofensivo o inapropiado',
        'duplicada'            => 'Pregunta duplicada',
        'otro'                 => 'Otro motivo',
    ];

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public static function motivos()
    {
        return self::MOTIVOS;
    }

    public static function esMotivoValido($motivo)
    {
        return is_string($motivo) && isset(self::MOTIVOS[$motivo]);
    }

    public static function motivoTexto($motivo)
    {
        return self::MOTIVOS[$motivo] ?? $motivo;
    }

    public function crear($usuarioId, $preguntaId, $motivo, $detalle = null, $partidaId = null)
    {
        $usuarioId  = (int) $usuarioId;
        $preguntaId = (int) $preguntaId;
        $detalle    = trim((string) $detalle);
        $partidaId  = ($partidaId === null || $partidaId === '') ? null : (int) $partidaId;
        if ((int) $partidaId <= 0) {
            $partidaId = null;
        }

        if (!self::esMotivoValido($motivo)) {
            return ["ok" => false, "error" => "Seleccioná un motivo válido."];
        }

        if ($motivo === 'otro' && $detalle === '') {
            return ["ok" => false, "error" => "Contanos el motivo del reporte."];
        }

        $respondida = $this->database->queryOne(
            "SELECT 1 AS respondida FROM respuestas
             WHERE usuario_id = ? AND pregunta_id = ?
             LIMIT 1",
            [$usuarioId, $preguntaId]
        );

        if ($respondida === null) {
            return ["ok" => false, "error" => "Solo podés reportar preguntas que hayas respondido."];
        }

        $existente = $this->existeReporte($usuarioId, $preguntaId);

        if ($existente) {
            return ["ok" => false, "error" => "Ya reportaste esta pregunta."];
        }

        try {
            $this->database->execute(
                "INSERT INTO reportes_preguntas (pregunta_id, usuario_id, partida_id, motivo, detalle)
                 VALUES (?, ?, ?, ?, ?)",
                [$preguntaId, $usuarioId, $partidaId, $motivo, $detalle !== '' ? $detalle : null]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo enviar el reporte. Probá de nuevo más tarde."];
        }

        return ["ok" => true];
    }

    public function existeReporte($usuarioId, $preguntaId)
    {
        return $this->database->queryOne(
            "SELECT id FROM reportes_preguntas
             WHERE usuario_id = ? AND pregunta_id = ?
             LIMIT 1",
            [(int) $usuarioId, (int) $preguntaId]
        ) !== null;
    }

    public function getPreguntaParaReportar($preguntaId, $usuarioId)
    {
        return $this->database->queryOne(
            "SELECT p.id, p.descripcion, p.categoria
             FROM preguntas p
             WHERE p.id = ?
               AND EXISTS (
                   SELECT 1 FROM respuestas r
                   WHERE r.usuario_id = ? AND r.pregunta_id = p.id
               )
             LIMIT 1",
            [(int) $preguntaId, (int) $usuarioId]
        );
    }

    public function partidaDelUsuario($partidaId, $usuarioId)
    {
        // La propiedad "jugador1_id" vive en partida_jugador (alias en PartidaModel),
        // por eso se consulta por usuario dentro de la partida.
        return $this->database->queryOne(
            "SELECT pjh.id AS partida_jugador_id
             FROM partida p
             INNER JOIN partida_jugador pjh ON pjh.partida_id = p.id
             WHERE p.id = ? AND pjh.usuario_id = ?
             LIMIT 1",
            [(int) $partidaId, (int) $usuarioId]
        ) !== null;
    }

    public function listar($filtro = 'pendientes')
    {
        $sql = "SELECT rp.*, p.descripcion AS pregunta_descripcion, p.activa AS pregunta_activa,
                       u.usuario AS reportante_nombre, r.usuario AS revisor_nombre
                FROM reportes_preguntas rp
                LEFT JOIN preguntas p ON p.id = rp.pregunta_id
                INNER JOIN usuarios u ON u.id = rp.usuario_id
                LEFT JOIN usuarios r ON r.id = rp.resuelto_por";

        if ($filtro === 'pendientes') {
            $sql .= " WHERE rp.estado = 'pendiente'";
        } elseif ($filtro === 'resueltos') {
            $sql .= " WHERE rp.estado <> 'pendiente'";
        }

        $sql .= " ORDER BY (rp.estado = 'pendiente') DESC, rp.creado_en DESC, rp.id DESC";

        return $this->database->queryAll($sql);
    }

    public function contarPendientes()
    {
        $fila = $this->database->queryOne(
            "SELECT COUNT(*) AS total FROM reportes_preguntas WHERE estado = 'pendiente'"
        );

        return (int) ($fila['total'] ?? 0);
    }

    public function getPorId($id)
    {
        return $this->database->queryOne(
            "SELECT rp.*, p.descripcion AS pregunta_descripcion, p.activa AS pregunta_activa,
                    u.usuario AS reportante_nombre, r.usuario AS revisor_nombre
             FROM reportes_preguntas rp
             LEFT JOIN preguntas p ON p.id = rp.pregunta_id
             INNER JOIN usuarios u ON u.id = rp.usuario_id
             LEFT JOIN usuarios r ON r.id = rp.resuelto_por
             WHERE rp.id = ?",
            [(int) $id]
        );
    }

    public function resolver($id, $estado, $revisorId)
    {
        $estadosValidos = ['editada', 'desactivada', 'eliminada', 'cerrada'];

        if (!in_array($estado, $estadosValidos, true)) {
            return ["ok" => false, "error" => "La resolución indicada no es válida.", "codigo" => "accion"];
        }

        $filas = $this->database->execute(
            "UPDATE reportes_preguntas
             SET estado = ?, resuelto_por = ?, resuelto_en = NOW()
             WHERE id = ? AND estado = 'pendiente'",
            [$estado, (int) $revisorId, (int) $id]
        );

        if ($filas < 1) {
            return ["ok" => false, "error" => "El reporte no existe o ya fue resuelto.", "codigo" => "ya_revisado"];
        }

        return ["ok" => true];
    }

    /**
     * Cierra todos los reportes pendientes de una pregunta.
     * Se usa al editarla, desactivarla o eliminarla desde el panel.
     */
    public function cerrarPendientesDePregunta($preguntaId, $estado, $revisorId)
    {
        $estadosValidos = ['editada', 'desactivada', 'eliminada', 'cerrada'];

        if (!in_array($estado, $estadosValidos, true)) {
            return;
        }

        $this->database->execute(
            "UPDATE reportes_preguntas
             SET estado = ?, resuelto_por = ?, resuelto_en = NOW()
             WHERE pregunta_id = ? AND estado = 'pendiente'",
            [$estado, (int) $revisorId, (int) $preguntaId]
        );
    }
}
