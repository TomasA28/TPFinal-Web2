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

    public function show()
    {
        // TEMPORAL: borrar cuando el login guarde el id en la sesión
        $_SESSION['usuario_id'] = 2;

        // A1: sin sesión
        if (empty($_SESSION['usuario_id'])) {
            $this->redirigir("login");
        }

        $usuario = $this->model->getUsuario($_SESSION['usuario_id']);

        // La sesión apunta a un usuario que ya no existe
        if (!$usuario) {
            session_destroy();
            $this->redirigir("login");
        }

        // A2: cuenta no validada
        if (!$usuario['cuenta_validada']) {
            $this->render->renderiza("cuentaNoValidada", [
                "mensaje" => "Debés validar tu cuenta desde el mail que te enviamos."
            ]);
            return;
        }

        // A3: editor y admin van a su panel
        if ($usuario['rol'] === 'editor') {
            $this->redirigir("editor");
        }
        if ($usuario['rol'] === 'admin') {
            $this->redirigir("admin");
        }

        // A7: si falla la base, pantalla de error
        try {
            $puntaje   = $this->model->getPuntaje($usuario['id']);
            $posicion  = $this->model->getPosicion($puntaje, $usuario['entorno_id']);
            $desafios  = $this->model->getDesafiosPendientes($usuario['id']);
            $historial = $this->model->getHistorial($usuario['id'], 10);
        } catch (\Throwable $e) {
            error_log("[Lobby] " . $e->getMessage());
            http_response_code(500);
            $this->render->renderiza("error", [
                "mensaje" => "Ocurrió un error. Intentá más tarde."
            ]);
            return;
        }

        $trampitas = (int)$usuario['trampitas'];

        $data = [
            "nombre"        => $usuario['nombre_completo'],
            "puntaje"       => $puntaje,
            "textoPosicion" => $posicion ? "#" . $posicion . " en el ranking" : "Sin ranking",
            "trampitas"     => $trampitas,
            "sinTrampitas"  => $trampitas === 0,
            "desafios"      => $this->prepararDesafios($desafios),
            "hayDesafios"   => !empty($desafios),
            "partidas"      => $this->prepararPartidas($historial),
            "hayPartidas"   => !empty($historial),
        ];

        $this->render->renderiza("lobby", $data);
    }

    private function redirigir($controller, $method = "show")
    {
        header("Location: /TPFinal-Web2/index.php?controller=$controller&method=$method");
        exit;
    }

    // Mustache no formatea ni compara: se resuelve acá
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
        unset($p);
        return $historial;
    }

    private function prepararDesafios($desafios)
    {
        foreach ($desafios as &$d) {
            $d['fecha_formateada'] = date('d/m/Y H:i', strtotime($d['fecha']));
        }
        unset($d);
        return $desafios;
    }
}