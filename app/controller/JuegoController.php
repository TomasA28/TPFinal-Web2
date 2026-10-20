<?php
namespace App\Controller;

use Request;

class JuegoController
{
    private $juegoModel;
    private $render;

    public function __construct($juegoModel, $render)
    {
        $this->juegoModel = $juegoModel;
        $this->render = $render;
    }

    public function jugar()
    {
        $pregunta = $this->juegoModel->getPreguntaAleatoria();

        $data = [
            'id' => $pregunta['id'],
            'categoria' => $pregunta['categoria'],
            'pregunta' => $pregunta['descripcion'],
            'opcionA' => $pregunta['opcion_a'],
            'opcionB' => $pregunta['opcion_b'],
            'opcionC' => $pregunta['opcion_c'],
            'opcionD' => $pregunta['opcion_d'],
        ];

        $this->render->renderiza('juegoView', $data);
    }

    public function procesarRespuesta()
    {
        $respuestaElegida = Request::post('respuesta');
        $preguntaId = Request::post('pregunta_id');
        $esCorrecta = $this->juegoModel->procesarRespuesta($respuestaElegida, $preguntaId);

        if ($esCorrecta) {
            $data = [
                'es_correcta' => true,
                'puntaje_actual' => $this->juegoModel->getPuntajeActual()
            ];
        } else {
            $data = [
                'es_incorrecta' => true,
                'puntaje_final' => $this->juegoModel->getPuntajeFinal()
            ];
        }

        $this->render->renderiza('juegoView', $data);
    }
}