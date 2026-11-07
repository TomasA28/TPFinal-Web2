<?php

namespace app\controller;

use Redirect;
use Request;

class RegistroController
{
    private $model;
    private $render;
    private $mailer;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
        $this->mailer = new \Mailer();
    }

    public function show()
    {

        if (isset($_SESSION['usuario_id'])) {
            Redirect::to('/lobby/show');
            return;
        }

        $data["error"] = null;
        $data["exito"] = isset($_GET['registro_ok']);
        $this->render->renderiza("registroView", $data);
    }

    public function crear()
    {
        $nombre = Request::post('nombre');
        $apellido = Request::post('apellido');
        $fechaNac  = Request::post('fechaNac');
        $sexo = Request::post('sexo');
        $lat = Request::post('lat');
        $lng = Request::post('lng');
        $mail = Request::post('mail');
        $password  = Request::post('password');
        $passwordCheck = Request::post('confirmar');
        $usuario = Request::post('userName');
        $fotoDePerfil = $_FILES['fotoDePerfil'] ?? null;
        
        $resultado = $this->model->registrar($nombre, $apellido, $fechaNac, $sexo, $lat, $lng, 
        $mail, $password, $passwordCheck, $usuario, $fotoDePerfil);
        
        if($resultado['ok']) {
            $token = $resultado['token'];
            $enlace = 'http://localhost/registro/activar?token=' . urlencode($token);

            $mensaje = 
            "<h2>¡Bienvenido a nuestra aplicación!</h2>
                <p>Gracias por registrarte.</p>
                <p>Para activar tu cuenta, hacé clic en el siguiente enlace:</p>
                <a href='$enlace'>Activar mi cuenta</a>
            ";

            $enviado = $this->mailer->enviar($mail, 'Activación de cuenta', $mensaje);

            if ($enviado) {
                Redirect::to('/registro/show?registro_ok=1');
                return;
            }

            $data["error"] = "Tu cuenta fue creada, pero no pudimos enviar el correo de activación.";
            $data["exito"] = false;
            
            $this->render->renderiza("registroView", $data);
            return;

        }

        $data["error"] = $resultado['error'];
        $data["exito"] = false;
        $this->render->renderiza("registroView", $data);
    }

    public function activar()
    {
        $token = Request::get('token');

        if (!$token) {
            Redirect::to('/registro/show');
            return;
        }

        $usuario = $this->model->buscarPorToken($token);

        if (!$usuario) {
            Redirect::to('/registro/show');
            return;
        }

        $this->model->activarUsuario($usuario['id']);
        Redirect::to('/login/show');
        return;
    }
}