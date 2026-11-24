<?php
namespace app\controller;

use Redirect;
use Request;

class PreguntasController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        if (!$this->puedeGestionar()) {
            Redirect::to('/lobby/show');
            return;
        }

        $exito = Request::get('exito') !== null ? "La pregunta fue creada correctamente." : null;
        $this->render->renderiza("preguntasView", ["exito" => $exito]);
    }

    public function crear()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        if (!$this->puedeGestionar()) {
            Redirect::to('/lobby/show');
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $descripcion = Request::post('descripcion');
        $opcion_a = Request::post('opcion_a');
        $opcion_b = Request::post('opcion_b');
        $opcion_c = Request::post('opcion_c');
        $opcion_d = Request::post('opcion_d');
        $respuesta_correcta = Request::post('respuesta_correcta');
        $categoria = Request::post('categoria');

        $resultado = $this->model->crear($usuarioId, $descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d,
            $respuesta_correcta, $categoria);

        if ($resultado['ok']) {
            Redirect::to('/preguntas/show?exito=1');
            return;
        }

        $this->render->renderiza("preguntasView", ["error" => $resultado['error']]);
    }

    private function puedeGestionar()
    {
        return in_array($_SESSION['rol'] ?? null, ['editor', 'administrador'], true);
    }
}
