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
}
