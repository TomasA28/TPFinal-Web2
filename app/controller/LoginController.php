<?php

namespace app\controller;

use Redirect;
use Request;

class LoginController
{
    private $modelFactory;
    private $render;

    public function __construct($modelFactory, $render)
    {
        $this->modelFactory = $modelFactory;
        $this->render = $render;
    }

    public function show()
    {
        $data["error"] = Request::get('login_error') !== null
            ? "Usuario o contraseña incorrectos."
            : null;

        $this->render->renderiza("loginView", $data);
    }

    public function login()
    {
        $usuarioIngresado = Request::post('usuario');
        $password = Request::post('password');

        if (
            !is_string($usuarioIngresado) || trim($usuarioIngresado) === ''
            || !is_string($password) || trim($password) === ''
        ) {
            Redirect::to('/login/show?login_error=1');
        }

        $model = call_user_func($this->modelFactory);
        $usuario = $model->autenticar(trim($usuarioIngresado), $password);

        if ($usuario === null) {
            Redirect::to('/login/show?login_error=1');
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario'] = $usuario['usuario'];
        Redirect::to('/lobby/show');
    }

    public function logout()
    {
        session_destroy();
        Redirect::toIndex();
    }
}
