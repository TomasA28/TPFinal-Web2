<?php

namespace app\controller;

use Request;

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
        $data["error"] = Request:: get('login_error') !== null ? "Usuario o contraseña incorrectos." : null;

        $this->render->renderiza("homeView", $data);
    }
}
