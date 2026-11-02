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

        if (isset($_SESSION['usuario_id'])) {
            Redirect::to('/lobby/show');
            return;
        }

        $error = Request::get('login_error');
        $data["error"] = null;

        if($error === '1') {
            $data["error"] = "Usuario o contraseña incorrectos.";
        } elseif ($error === '2') {
            $data["error"] = "Debes activar tu cuenta desde el enlace que mandamos a tu correo.";
        }

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
            return;
        }

        $model = call_user_func($this->modelFactory);
        
        $resultado = $model->autenticar(
            trim($usuarioIngresado),
             $password,
        );

        if (!$resultado['ok']) {
            if (isset($resultado['no_activado']) && $resultado['no_activado']) {
                Redirect::to('/login/show?login_error=2');
                return;
            }

            Redirect::to('/login/show?login_error=1');
            return;
        }
        
        $usuario = $resultado['usuario'];
        
        session_regenerate_id(true);
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario'] = $usuario['usuario'];
        Redirect::to('/lobby/show');
        return;
    }
}
