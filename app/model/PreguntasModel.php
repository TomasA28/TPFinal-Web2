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
}
