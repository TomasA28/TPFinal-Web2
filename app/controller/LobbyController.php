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


        $idUsuario = (int) $_SESSION['usuario_id'];
        $usuario = $this->model->getUsuario($idUsuario);

        if ($usuario === null) {
            Redirect::toIndex();
            exit;
        }

        $data = [
            'usuario' => $usuario,
            'hay_partida' => $usuario['puntaje'] !== null,
        ];

        $this->render->renderiza("lobbyView", $data);
    }
}