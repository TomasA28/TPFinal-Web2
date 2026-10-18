<?php

namespace app\controller;

class LobbyController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function index(){
        if (empty($_SESSION['usuario_id'])) {
            header("Location: /login");
            exit;
        }

        $usuario = $this->model->getUsuario($_SESSION['usuario_id']);

        if (!$usuario) {
            session_destroy();
            header("Location: /login");
            exit;
        }

        if (!$usuario['cuenta_validada']) {
            $this->render->renderiza("cuentaNoValidada", [
                "mensaje" => "Debés validar tu cuenta desde el mail que te enviamos."
            ]);
            return;
        }

        if ($usuario['rol'] === 'editor') {
            header("Location: /editor/index");
            exit;
        }
        if ($usuario['rol'] === 'admin') {
            header("Location: /admin/index");
            exit;
        }

        try {
            $puntaje   = $this->model->getPuntaje($usuario['id']);
            $posicion  = $this->model->getPosicion($puntaje, $usuario['entorno_id']);
            $desafios  = $this->model->getDesafiosPendientes($usuario['id']);
            $historial = $this->model->getHistorial($usuario['id'], 10);
        } catch (Throwable $e) {
            error_log("[Lobby] " . $e->getMessage());
            http_response_code(500);
            $this->render->renderiza("error", ["mensaje" => "Ocurrió un error. Intentá más tarde."]);
            return;
        }

        $data = [
            "nombre"        => $usuario['nombre_completo'],
            "puntaje"       => $puntaje,
            "textoPosicion" => $posicion ? "#" . $posicion . " en el ranking" : "Sin ranking",
            "trampitas"     => (int)$usuario['trampitas'],
            "sinTrampitas"  => (int)$usuario['trampitas'] === 0,
            "desafios"      => $this->prepararDesafios($desafios),
            "hayDesafios"   => !empty($desafios),
            "partidas"      => $this->prepararPartidas($historial),
            "hayPartidas"   => !empty($historial),
        ];

        $this->render->renderiza("lobby", $data);
    }


    private function prepararPartidas($historial)
    {
        foreach ($historial as &$p) {
            $p['fecha_formateada'] = date('d/m/Y H:i', strtotime($p['fecha']));
            $p['esDesafio'] = ($p['tipo'] === 'desafio');

            if ($p['esDesafio']) {
                if ($p['mi_puntaje'] > $p['puntaje_rival']) {
                    $p['resultado'] = 'Ganada';
                } elseif ($p['mi_puntaje'] < $p['puntaje_rival']) {
                    $p['resultado'] = 'Perdida';
                } else {
                    $p['resultado'] = 'Empate';
                }
            } else {
                $p['resultado'] = $p['mi_puntaje'] . ' puntos';
            }
        }
        return $historial;
    }

    private function prepararDesafios($desafios)
    {
        foreach ($desafios as &$d) {
            $d['fecha_formateada'] = date('d/m/Y H:i', strtotime($d['fecha']));
        }
        return $desafios;
    }
}