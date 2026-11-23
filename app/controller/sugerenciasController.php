<?php

namespace app\controller;

use Redirect;
use Request;

class SugerenciasController
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

        $exito = Request::get('exito') !== null ? "La sugerencia ha sido enviada!" : null;
        $this->render->renderiza("sugerenciasView", ["exito" => $exito]);
    }

    public function enviarSugerencia()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        $descripcion = Request::post('descripcion');
        $opcion_a = Request::post('opcion_a');
        $opcion_b = Request::post('opcion_b');
        $opcion_c = Request::post('opcion_c');
        $opcion_d = Request::post('opcion_d');
        $respuesta_correcta = Request::post('respuesta_correcta');
        $categoria = Request::post('categoria');

        $resultado = $this->model->sugerir($descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d,
            $respuesta_correcta, $categoria);

        if ($resultado['ok']) {
            Redirect::to('/sugerencias/show?exito=1');
            return;
        }

        $this->render->renderiza("sugerenciasView", ["error" => $resultado['error']]);
    }
}
