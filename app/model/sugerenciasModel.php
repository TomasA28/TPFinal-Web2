<?php

namespace app\model;

class SugerenciasModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function sugerir($descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria) {

        //POST y Sanitización de datos
        $descripcion        = trim(htmlspecialchars($descripcion ?? "", ENT_QUOTES, 'UTF-8'));
        $opcion_a           = trim(htmlspecialchars($opcion_a ?? "", ENT_QUOTES, 'UTF-8'));
        $opcion_b           = trim(htmlspecialchars($opcion_b ?? "", ENT_QUOTES, 'UTF-8'));
        $opcion_c           = trim(htmlspecialchars($opcion_c ?? "", ENT_QUOTES, 'UTF-8'));
        $opcion_d           = trim(htmlspecialchars($opcion_d ?? "", ENT_QUOTES, 'UTF-8'));
        $respuesta_correcta = strtoupper(trim(htmlspecialchars($respuesta_correcta ?? "", ENT_QUOTES, 'UTF-8')));
        $categoria          = trim(htmlspecialchars($categoria ?? "", ENT_QUOTES, 'UTF-8'));

        // Validaciones
        if ($descripcion === "") {
            return ["ok" => false, "error" => "Falta ingresar el enunciado."];
        }

        if ($opcion_a === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción A."];
        }

        if ($opcion_b === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción B."];
        }

        if ($opcion_c === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción C."];
        }

        if ($opcion_d === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción D."];
        }

        $opcionesValidasPreguntas = ['A', 'B', 'C', 'D'];
        if (!in_array($respuesta_correcta, $opcionesValidasPreguntas, true)) {
            return ["ok" => false, "error" => "Tenés que indicar la opción correcta (A, B, C o D)."];
        }

        $opcionesValidasCategorias = ['Historia', 'Cultura', 'Deporte', 'Arte', 'Ciencia'];
        if (!in_array($categoria, $opcionesValidasCategorias, true)) {
            return ["ok" => false, "error" => "La categoría seleccionada no es válida."];
        }

        try {
            $this->database->execute(
                "INSERT INTO preguntas_sugeridas (descripcion, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $descripcion,
                    $opcion_a,
                    $opcion_b,
                    $opcion_c,
                    $opcion_d,
                    $respuesta_correcta,
                    $categoria
                ]
            );
        } catch (\PDOException $e) {
            return ["ok" => false, "error" => "No se pudo guardar la sugerencia. Probá de nuevo más tarde."];
        }

        return ["ok" => true];
    }
}
