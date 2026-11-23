<?php

namespace app\model;

class ValidadorPregunta
{
    /**
     * Sanitiza y valida los datos comunes a una pregunta (opciones, respuesta y categoría).
     * Devuelve ["ok" => false, "error" => ...] o ["ok" => true, "datos" => [...]].
     */
    public static function validar($descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria)
    {
        $datos = [
            'descripcion'        => trim(htmlspecialchars($descripcion ?? "", ENT_QUOTES, 'UTF-8')),
            'opcion_a'           => trim(htmlspecialchars($opcion_a ?? "", ENT_QUOTES, 'UTF-8')),
            'opcion_b'           => trim(htmlspecialchars($opcion_b ?? "", ENT_QUOTES, 'UTF-8')),
            'opcion_c'           => trim(htmlspecialchars($opcion_c ?? "", ENT_QUOTES, 'UTF-8')),
            'opcion_d'           => trim(htmlspecialchars($opcion_d ?? "", ENT_QUOTES, 'UTF-8')),
            'respuesta_correcta' => strtoupper(trim(htmlspecialchars($respuesta_correcta ?? "", ENT_QUOTES, 'UTF-8'))),
            'categoria'          => trim(htmlspecialchars($categoria ?? "", ENT_QUOTES, 'UTF-8')),
        ];

        if ($datos['descripcion'] === "") {
            return ["ok" => false, "error" => "Falta ingresar el enunciado."];
        }

        if ($datos['opcion_a'] === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción A."];
        }

        if ($datos['opcion_b'] === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción B."];
        }

        if ($datos['opcion_c'] === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción C."];
        }

        if ($datos['opcion_d'] === "") {
            return ["ok" => false, "error" => "Falta ingresar la opción D."];
        }

        if (!in_array($datos['respuesta_correcta'], ['A', 'B', 'C', 'D'], true)) {
            return ["ok" => false, "error" => "Tenés que indicar la opción correcta (A, B, C o D)."];
        }

        if (!in_array($datos['categoria'], ['Historia', 'Cultura', 'Deporte', 'Arte', 'Ciencia'], true)) {
            return ["ok" => false, "error" => "La categoría seleccionada no es válida."];
        }

        return ["ok" => true, "datos" => $datos];
    }
}
