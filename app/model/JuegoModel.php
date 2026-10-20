<?php
namespace App\Model;

class JuegoModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function getPreguntaAleatoria()
    {
        return $this->database->queryOne("SELECT * FROM preguntas ORDER BY RAND() LIMIT 1");
    }

    public function procesarRespuesta($respuestaElegida, $preguntaId)
    {
        $preguntaId = (int) $preguntaId;
        $respuestaCorrecta = $this->database->queryOne(
            "SELECT respuesta_correcta FROM preguntas WHERE id = $preguntaId"
        );

        if (
            $respuestaCorrecta !== null
            && $respuestaCorrecta['respuesta_correcta'] === $respuestaElegida
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getPuntajeActual()
    {
        return 1;
    }

    public function getPuntajeFinal()
    {
        return 4;
    }
}