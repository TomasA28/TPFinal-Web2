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
        // Los perfiles son públicos: se pueden ver sin sesión
        // (necesario para que el QR funcione desde otro dispositivo).

        $idParam = Request::get('id');
        $idValido = $idParam !== null ? filter_var($idParam, FILTER_VALIDATE_INT) : false;

        // Si no viene id válido, solo tiene sentido mostrar el propio,
        // y para eso hace falta estar logueado.
        if ($idValido === false) {
            if (!isset($_SESSION['usuario_id'])) {
                Redirect::toIndex();
                return;
            }
            $idUsuario = (int) $_SESSION['usuario_id'];
        } else {
            $idUsuario = (int) $idValido;
        }

        $usuario = $this->model->getUsuario($idUsuario);

        if ($usuario === null) {
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

        $logueado = isset($_SESSION['usuario_id']);
        $data["logueado"] = $logueado;
        $data["duenioDePerfil"] = $logueado && ((int) $idUsuario === (int) $_SESSION['usuario_id']);
        $data["puntajeTotal"] = $puntajeTotal["puntajeTotal"] ?? 0;
        $data["usuario"] = $usuario;

        $this->render->renderiza("perfilView", $data);
    }
    
    public function qr()
    {
        $idBuscado = Request::get('id');
        $idValido = $idBuscado !== null ? filter_var($idBuscado, FILTER_VALIDATE_INT) : false;
        $idUsuario = $idValido !== false ? (int) $idValido : (int) ($_SESSION['usuario_id'] ?? 0);

        if($idUsuario <= 0 || $this->model->getUsuario($idUsuario) === null) {
            http_response_code(404);
            return;
        }

        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $urlPerfil = $esquema . '://' . $_SERVER['HTTP_HOST'] . '/perfil/show?id=' . $idUsuario;

        require_once("vendor/phpqrcode/qrlib.php");

        header('Content-Type: image/png');
        \QRcode::png($urlPerfil, false, QR_ECLEVEL_M, 8, 2);

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
