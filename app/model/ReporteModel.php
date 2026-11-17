<?php

namespace app\model;

class ReporteModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function existePregunta($preguntaId)
    {
        return $this->database->queryOne(
            "SELECT id FROM preguntas WHERE id = ?",
            [(int) $preguntaId]
        ) !== null;
    }

    public function guardarReporte($usuarioId, $preguntaId, $motivo, $comentario)
    {
        return $this->database->execute(
            "INSERT INTO reportes (usuario_id, pregunta_id, motivo, comentario)
             VALUES (?, ?, ?, ?)",
            [(int) $usuarioId, (int) $preguntaId, $motivo, $comentario]
        ) === 1;
    }
}
