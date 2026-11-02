<?php

namespace app\controller;
use Redirect;

class HomeController
{
    private $render;

    public function __construct($render)
    {
        $this->render = $render;
    }

    public function show()
    {

        if (isset($_SESSION['usuario_id'])) {
            Redirect::to('/lobby/show');
            return;
        }

        $data["logueado"] = isset($_SESSION['usuario']);
        $data["usuario"] = $_SESSION['usuario'] ?? null;

        $this->render->renderiza("homeView", $data);
    }
}
