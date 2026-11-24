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
        // Guardamos el texto tal cual; el escapado de HTML se hace al mostrarlo
        // (Mustache escapa por defecto). Así evitamos el doble escapado al editar.
        $datos = [
            'descripcion'        => trim((string) $descripcion),
            'opcion_a'           => trim((string) $opcion_a),
            'opcion_b'           => trim((string) $opcion_b),
            'opcion_c'           => trim((string) $opcion_c),
            'opcion_d'           => trim((string) $opcion_d),
            'respuesta_correcta' => strtoupper(trim((string) $respuesta_correcta)),
            'categoria'          => trim((string) $categoria),
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
