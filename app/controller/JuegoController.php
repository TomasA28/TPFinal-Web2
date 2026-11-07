<?php
namespace app\controller;

use Redirect;
use Request;

class JuegoController
{
    private $categorias = [
        'historia' => 'Historia',
        'cultura' => 'Cultura',
        'deporte' => 'Deportes',
        'arte' => 'Arte',
        'ciencia' => 'Ciencia',
    ];

    private $juegoModel;
    private $render;

    public function __construct($juegoModel, $render)
    {
        $this->juegoModel = $juegoModel;
        $this->render = $render;
    }
    public function jugar()
    {
        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }

        $this->render->renderiza('ruletaView');
    }

    public function nuevaPartida()
    {
        $_SESSION['puntaje_actual'] = 0;
        $this->jugar();
    }

    public function procesarPregunta()
    {
        $categoriaElegida = Request::post('categoria');
        if (!\is_string($categoriaElegida) || !isset($this->categorias[$categoriaElegida])) {
            $this->render->renderiza('ruletaView', [
                'error' => 'Seleccioná una categoría válida para continuar.',
            ]);
            return;
        }

        $categoria = $this->categorias[$categoriaElegida];
        $pregunta = $this->juegoModel->getPreguntaAleatoria(
            $categoria,
            $_SESSION['usuario_id']
        );
        if ($pregunta === null) {
            $this->render->renderiza('juegoView', [
                'sin_pregunta' => true,
                'categoria_sin_preguntas' => $categoria,
            ]);
            return;
        }

        $data["logueado"] = isset($_SESSION['usuario']);
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
        $esCorrecta = $this->juegoModel->procesarRespuesta(
            $respuestaElegida,
            $preguntaId,
            $_SESSION['usuario_id']
        );

        if ($esCorrecta === null) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'No se pudo registrar la respuesta. Volvé a elegir una pregunta.',
            ]);
            return;
        }

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