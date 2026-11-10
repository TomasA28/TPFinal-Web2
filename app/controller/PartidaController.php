<?php

namespace app\controller;

use Redirect;
use Request;

class PartidaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function iniciarVsBot()
    {
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        $partidaId = $this->model->crearPartidaVsBot($usuarioId);
        if (!$partidaId) {
            Redirect::to('/lobby/show');
            return;
        }
        Redirect::to('/partida/jugar?id=' . $partidaId);
    }


    public function jugar()
    {
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        $partida = $this->getPartidaPropia(Request::get('id'), $usuarioId);
        if (!$partida) {
            Redirect::to('/lobby/show');
            return;
        }
        if ($partida['estado'] === 'finalizada') {
            Redirect::to('/partida/resultado?id=' . (int)$partida['id']);
            return;
        }

        // Esto inicia el reloj, por eso va antes de pedir los segundos
        $pregunta = $this->model->getPreguntaActual($partida);
        if (!$pregunta) {
            Redirect::to('/lobby/show');
            return;
        }

        $marcador = $this->model->getMarcador($partida['id']);

        $this->render->renderiza('partida_jugar', [
            'logueado'         => true,
            'partida_id'       => (int)$partida['id'],
            'orden'            => (int)$partida['orden_actual'],
            'total'            => (int)$partida['total_preguntas'],
            'usuario'          => $_SESSION['usuario'] ?? '',
            'bot_nombre'       => $partida['bot_nombre'],
            'puntos_humano'    => $marcador['humano'],
            'puntos_bot'       => $marcador['bot'],
            'pregunta'         => $pregunta,
            'segundos'         => $this->model->getSegundosRestantes($usuarioId, $pregunta['id']),
            'segundos_totales' => $this->model->getSegundosPorPregunta(),
            'trampitas'        => $this->model->getSaldoTrampitas($usuarioId),
        ]);
    }


    public function responder()
    {
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        $partidaId = (int)Request::get('partida_id');


        if (!$this->esPost()) {
            Redirect::to('/partida/jugar?id=' . $partidaId);
            return;
        }

        $res = $this->model->responder(
            $partidaId,
            $usuarioId,
            Request::get('orden'),
            Request::get('respuesta'),
            Request::get('usar_trampita') === '1'
        );

        if ($res === null) {
            Redirect::to('/partida/jugar?id=' . $partidaId);
            return;
        }

        $partida  = $this->model->getPartida($partidaId);
        $marcador = $this->model->getMarcador($partidaId);

        $res['logueado']      = true;
        $res['bot_nombre']    = $partida['bot_nombre'];
        $res['puntos_humano'] = $marcador['humano'];
        $res['puntos_bot']    = $marcador['bot'];
        $res['url_siguiente'] = $res['terminada']
            ? '/partida/resultado?id=' . $partidaId
            : '/partida/jugar?id=' . $partidaId;

        $this->render->renderiza('partida_feedback', $res);
    }


    public function resultado()
    {
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        $data = $this->model->getResultado(Request::get('id'));
        if (!$data || (int)$data['jugador1_id'] !== $usuarioId) {
            Redirect::to('/lobby/show');
            return;
        }

        $data['logueado'] = true;
        $this->render->renderiza('partida_resultado', $data);
    }



    private function exigirLogin()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return null;
        }
        return (int)$_SESSION['usuario_id'];
    }


    private function getPartidaPropia($partidaId, $usuarioId)
    {
        $partida = $this->model->getPartida($partidaId);
        if (!$partida || (int)$partida['jugador1_id'] !== (int)$usuarioId) {
            return null;
        }
        return $partida;
    }

    private function esPost()
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}