<?php

namespace app\model;

class SugerenciasModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function sugerir($usuario_id, $descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria)
    {
        $validacion = ValidadorPregunta::validar(
            $descripcion,
            $opcion_a,
            $opcion_b,
            $opcion_c,
            $opcion_d,
            $respuesta_correcta,
            $categoria
        );

        if (!$validacion['ok']) {
            return $validacion;
        }

        $datos = $validacion['datos'];

        try {
            $this->database->execute(
                "INSERT INTO preguntas_sugeridas (usuario_id, descripcion, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    (int) $usuario_id,
                    $datos['descripcion'],
                    $datos['opcion_a'],
                    $datos['opcion_b'],
                    $datos['opcion_c'],
                    $datos['opcion_d'],
                    $datos['respuesta_correcta'],
                    $datos['categoria']
                ]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo guardar la sugerencia. Probá de nuevo más tarde."];
        }

        return ["ok" => true];
    }

    public function listar($estado = 'pendiente')
    {
        $sql = "SELECT ps.*, u.usuario AS usuario_nombre, r.usuario AS revisor_nombre
                FROM preguntas_sugeridas ps
                INNER JOIN usuarios u ON u.id = ps.usuario_id
                LEFT JOIN usuarios r ON r.id = ps.revisado_por";
        $parametros = [];

        if ($estado !== null && $estado !== 'todas') {
            $sql .= " WHERE ps.estado = ?";
            $parametros[] = $estado;
        }

        $sql .= " ORDER BY ps.creado_en DESC, ps.id DESC";

        return $this->database->queryAll($sql, $parametros);
    }

    public function contarPorEstado()
    {
        $filas = $this->database->queryAll(
            "SELECT estado, COUNT(*) AS total FROM preguntas_sugeridas GROUP BY estado"
        );

        $conteos = ['pendiente' => 0, 'aprobada' => 0, 'rechazada' => 0];

        foreach ($filas as $fila) {
            $conteos[$fila['estado']] = (int) $fila['total'];
        }

        return $conteos;
    }

    public function getPorId($id)
    {
        return $this->database->queryOne(
            "SELECT ps.*, u.usuario AS usuario_nombre
             FROM preguntas_sugeridas ps
             INNER JOIN usuarios u ON u.id = ps.usuario_id
             WHERE ps.id = ?",
            [(int) $id]
        );
    }

    public function aprobar($id, $revisorId, $datos = null)
    {
        $sugerencia = $this->getPorId($id);

        if ($sugerencia === null) {
            return ["ok" => false, "error" => "La sugerencia no existe.", "codigo" => "no_encontrado"];
        }

        if ($sugerencia['estado'] !== 'pendiente') {
            return ["ok" => false, "error" => "La sugerencia ya fue revisada.", "codigo" => "ya_revisado"];
        }

        $validacion = ValidadorPregunta::validar(
            $datos['descripcion'] ?? $sugerencia['descripcion'],
            $datos['opcion_a'] ?? $sugerencia['opcion_a'],
            $datos['opcion_b'] ?? $sugerencia['opcion_b'],
            $datos['opcion_c'] ?? $sugerencia['opcion_c'],
            $datos['opcion_d'] ?? $sugerencia['opcion_d'],
            $datos['respuesta_correcta'] ?? $sugerencia['respuesta_correcta'],
            $datos['categoria'] ?? $sugerencia['categoria']
        );

        if (!$validacion['ok']) {
            return $validacion;
        }

        $pregunta = $validacion['datos'];

        try {
            $this->database->execute(
                "INSERT INTO preguntas (descripcion, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria, creado_por, origen)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sugerencia')",
                [
                    $pregunta['descripcion'],
                    $pregunta['opcion_a'],
                    $pregunta['opcion_b'],
                    $pregunta['opcion_c'],
                    $pregunta['opcion_d'],
                    $pregunta['respuesta_correcta'],
                    $pregunta['categoria'],
                    (int) $revisorId
                ]
            );

            $preguntaId = (int) $this->database->lastInsertId();

            $this->database->execute(
                "UPDATE preguntas_sugeridas
                 SET estado = 'aprobada', revisado_por = ?, revisado_en = NOW(), pregunta_id = ?
                 WHERE id = ? AND estado = 'pendiente'",
                [(int) $revisorId, $preguntaId, (int) $id]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo aprobar la sugerencia. Probá de nuevo más tarde.", "codigo" => "operacion"];
        }

        return ["ok" => true, "pregunta_id" => $preguntaId];
    }

    public function rechazar($id, $motivo, $revisorId)
    {
        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            return ["ok" => false, "error" => "Tenés que indicar el motivo del rechazo.", "codigo" => "motivo"];
        }

        $filas = $this->database->execute(
            "UPDATE preguntas_sugeridas
             SET estado = 'rechazada', motivo_rechazo = ?, revisado_por = ?, revisado_en = NOW()
             WHERE id = ? AND estado = 'pendiente'",
            [$motivo, (int) $revisorId, (int) $id]
        );

        if ($filas < 1) {
            return ["ok" => false, "error" => "La sugerencia no existe o ya fue revisada.", "codigo" => "ya_revisado"];
        }

        return ["ok" => true];
    }
}
