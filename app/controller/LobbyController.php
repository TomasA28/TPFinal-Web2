<?php

namespace app\controller;


use Redirect;

class LobbyController
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
            exit;
        }


        $idUsuario = $_SESSION['usuario_id'];

        /*$data["usuario"] = $this->model->getUsuario($idUsuario);
        $data["partidas"] = $this->model->getPartidas($idUsuario);
        $data["hay_partidas"] = !empty($data["partidas"]);*/

        $this->render->renderiza("lobbyView"/*, $data*/);
    }
}