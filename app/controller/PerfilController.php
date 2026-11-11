<?php

namespace app\controller;
use Redirect;
use Request;

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

        // El Router no pasa argumentos: el id llega por query string
        // (/perfil/show?id=N). Si viene inválido o ausente, se muestra el propio.
        $idParam = Request::get('id');
        $idValido = $idParam !== null ? filter_var($idParam, FILTER_VALIDATE_INT) : false;
        $idUsuario = $idValido !== false ? (int) $idValido : (int) $_SESSION['usuario_id'];

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

        $puntajeTotal = $this->model->getPuntajeTotal($idUsuario);

        $data["logueado"] = true;
        $data["duenioDePerfil"] = ((int)$idUsuario === (int)$_SESSION['usuario_id']);
        $data["puntajeTotal"] = $puntajeTotal["puntajeTotal"] ?? 0;
        $data["usuario"] = $usuario;

        $this->render->renderiza("perfilView", $data);
    }

    public function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        Redirect::toIndex();
    }
}
