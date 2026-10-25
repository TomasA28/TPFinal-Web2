<?php

namespace app\controller;

class PartidaController
{
    private $partidaModel;
    private $render;

    public function __construct($partidaModel, $render)
    {
        $this->partidaModel = $partidaModel;
        $this->render = $render;
    }

    public function crear()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            exit;
        }

        $idPartida = $this->partidaModel->crearPartida($_SESSION['usuario_id']);
        Redirect::to('/partida/show?id=' . $idPartida);
    }
}