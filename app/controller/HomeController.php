<?php

namespace app\controller;

class HomeController
{
    private $render;

    public function __construct($render)
    {
        $this->render = $render;
    }

    public function show()
    {
        $data["logueado"] = isset($_SESSION['usuario']);
        $data["usuario"] = $_SESSION['usuario'] ?? null;

        $this->render->renderiza("homeView", $data);
    }
}
