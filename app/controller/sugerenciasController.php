<?php
namespace app\controller;

use Redirect;
use Request;

class SugerenciasController
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
            Redirect::to('/login/show');
            return;
        }

        if (!$this->esJugador()) {
            Redirect::to('/lobby/show');
            return;
        }

        $exito = Request::get('exito') !== null ? "La sugerencia ha sido enviada!" : null;
        $this->render->renderiza("sugerenciasView", ["exito" => $exito]);
    }

    public function enviarSugerencia()
    {
        $esAjax = $this->esPedidoAjax();

        if (!isset($_SESSION['usuario_id'])) {
            if ($esAjax) {
                $this->responderJson(401, ["ok" => false, "error" => "Tu sesión expiró. Iniciá sesión de nuevo."]);
            } else {
                Redirect::to('/login/show');
            }
            return;
        }

        if (!$this->esJugador()) {
            if ($esAjax) {
                $this->responderJson(403, ["ok" => false, "error" => "Solo los jugadores pueden enviar sugerencias."]);
            } else {
                Redirect::to('/lobby/show');
            }
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $descripcion = Request::post('descripcion');
        $opcion_a = Request::post('opcion_a');
        $opcion_b = Request::post('opcion_b');
        $opcion_c = Request::post('opcion_c');
        $opcion_d = Request::post('opcion_d');
        $respuesta_correcta = Request::post('respuesta_correcta');
        $categoria = Request::post('categoria');

        $resultado = $this->model->sugerir($usuarioId, $descripcion, $opcion_a, $opcion_b, $opcion_c, $opcion_d,
            $respuesta_correcta, $categoria);

        if ($esAjax) {
            if ($resultado['ok']) {
                $resultado["mensaje"] = "La sugerencia ha sido enviada!";
            }

            $this->responderJson(200, $resultado);
            return;
        }

        if ($resultado['ok']) {
            Redirect::to('/sugerencias/show?exito=1');
            return;
        }

        $this->render->renderiza("sugerenciasView", ["error" => $resultado['error']]);
    }

    private function esJugador()
    {
        return ($_SESSION['rol'] ?? null) === 'jugador';
    }

    private function esPedidoAjax()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return strpos($accept, 'application/json') !== false || $xhr === 'xmlhttprequest';
    }

    private function responderJson($status, array $datos)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos);
    }
}
