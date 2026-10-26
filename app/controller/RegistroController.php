<?php

namespace app\controller;

use Redirect;
use Request;

class RegistroController
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
        $data["error"] = null;
        $data["exito"] = isset($_GET['registro_ok']);
        $this->render->renderiza("registroView", $data);
    }

    public function crear()
    {
        $usuario   = Request::post('userName');
        $mail      = Request::post('Mail');
        $password  = Request::post('password');
        $passwordCheck = Request::post('Confirmar');
        $fechaNac  = Request::post('fechaNac');

        $resultado = $this->model->registrar($usuario, $mail, $password, $passwordCheck, $fechaNac);
        
        if($resultado['ok']) {
            Redirect::to('/login/show');
        }

        $data["error"] = $resultado['error'];
        $data["exito"] = false;
        $this->render->renderiza("registroView", $data);
    }
}
