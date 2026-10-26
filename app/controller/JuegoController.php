<?php
namespace App\Controller;

use Redirect;
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
            'categoria' => strtolower($pregunta['categoria']),
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

        if (!isset($_SESSION['puntaje_actual'])) {
        $_SESSION['puntaje_actual'] = 0;
        }

        if ($esCorrecta) {
            $_SESSION['puntaje_actual']++;
            $data = [
                'es_correcta' => true,
                'puntaje_actual' => $_SESSION['puntaje_actual']
            ];
        } else {

            $usuarioId = $_SESSION['usuario_id'];
            $puntajeFinal = $_SESSION['puntaje_actual'];
            $puntajeFinal === null ? $puntajeFinal = 0 : $puntajeFinal;

            $this->juegoModel->guardarPartidaTerminada($usuarioId, $puntajeFinal);

            $data = [
                'es_incorrecta' => true,
                'puntaje_final' => $puntajeFinal
            ];

            unset($_SESSION['puntaje_actual']);
        }

        $this->render->renderiza('juegoView', $data);
    }
}