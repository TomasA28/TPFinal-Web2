<?php

namespace app\model;

class PreguntasModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crear($usuario_id, $descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria)
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
                "INSERT INTO preguntas (descripcion, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria, creado_por, origen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'directa')",
                [
                    $datos['descripcion'],
                    $datos['opcion_a'],
                    $datos['opcion_b'],
                    $datos['opcion_c'],
                    $datos['opcion_d'],
                    $datos['respuesta_correcta'],
                    $datos['categoria'],
                    (int) $usuario_id
                ]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo guardar la pregunta. Probá de nuevo más tarde."];
        }

        return ["ok" => true];
    }

    public function actualizar($id, $descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria)
    {
        $id = (int) $id;

        if ($this->getPorId($id) === null) {
            return ["ok" => false, "error" => "La pregunta no existe.", "codigo" => "no_encontrado"];
        }

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
                "UPDATE preguntas
                 SET descripcion = ?, opcion_a = ?, opcion_b = ?, opcion_c = ?, opcion_d = ?,
                     respuesta_correcta = ?, categoria = ?
                 WHERE id = ?",
                [
                    $datos['descripcion'],
                    $datos['opcion_a'],
                    $datos['opcion_b'],
                    $datos['opcion_c'],
                    $datos['opcion_d'],
                    $datos['respuesta_correcta'],
                    $datos['categoria'],
                    $id
                ]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo actualizar la pregunta. Probá de nuevo más tarde."];
        }

        return ["ok" => true];
    }

    public function listarTodas()
    {
        return $this->database->queryAll(
            "SELECT p.*, u.usuario AS creador
             FROM preguntas p
             LEFT JOIN usuarios u ON u.id = p.creado_por
             ORDER BY p.id DESC"
        );
    }

    public function getPorId($id)
    {
        return $this->database->queryOne(
            "SELECT p.*, u.usuario AS creador
             FROM preguntas p
             LEFT JOIN usuarios u ON u.id = p.creado_por
             WHERE p.id = ?",
            [(int) $id]
        );
    }

    public function cambiarActividad($id, $activa)
    {
        $this->database->execute(
            "UPDATE preguntas SET activa = ? WHERE id = ?",
            [(int) $activa, (int) $id]
        );

        return ["ok" => true];
    }

    public function eliminar($id)
    {
        try {
            $this->database->execute("DELETE FROM preguntas WHERE id = ?", [(int) $id]);
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo eliminar la pregunta.", "codigo" => "operacion"];
        }

        return ["ok" => true];
    }

    public function contarTodas()
    {
        $fila = $this->database->queryOne("SELECT COUNT(*) AS total FROM preguntas");
        return (int) ($fila['total'] ?? 0);
    }

    public function contarActivas()
    {
        $fila = $this->database->queryOne("SELECT COUNT(*) AS total FROM preguntas WHERE activa = 1");
        return (int) ($fila['total'] ?? 0);
    }
}
