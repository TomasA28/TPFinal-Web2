<?php

namespace app\controller;
use Redirect;

class PerfilController
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
            Redirect::toIndex();
            return;
        }

        $idUsuario = (int) $_SESSION['usuario_id'];

        $usuario = $this->model->getUsuario($idUsuario);

        if($usuario === null){
            Redirect::toIndex();
            return;
        }

        $sexos = [
            1 => "Masculino",
            2 => "Femenino",
            3 => "Prefiero no decirlo"
        ];

        $usuario["sexo"] = $sexos[$usuario["sexo"]] ?? 3;

        $data["logueado"] = true;
        $data["usuario"] = $usuario;

        $this->render->renderiza("perfilView", $data);
    }

    public function logout()
    {
        session_destroy();
        Redirect::toIndex();
    }
}
