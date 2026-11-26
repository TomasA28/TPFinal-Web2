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

        if ($this->model->getPartidaActiva($usuarioId) !== null) {
            Redirect::to('/partida/jugar');
            return;
        }

        // Cambiar al modo contra bot cierra el turno de ruleta para no mantener
        // dos partidas activas a la vez.
        if (!empty($_SESSION['partida_ruleta_en_curso']) || $this->model->tienePreguntaActiva($usuarioId)) {
            $_SESSION['aviso_modo'] = 'La partida en solitario se cerró al iniciar el modo contra el bot.';
            $this->model->limpiarPreguntaActiva($usuarioId);
            unset(
                $_SESSION['partida_ruleta_en_curso'],
                $_SESSION['preguntas_vistas'],
                $_SESSION['puntaje_actual'],
                $_SESSION['pregunta_activa_id'],
                $_SESSION['pregunta_activa_expira_en']
            );
        }

        $partidaId = $this->model->crearPartidaVsBot($usuarioId);
        if (!$partidaId) {
            if ($this->model->tienePreguntaActiva($usuarioId)) {
                Redirect::to('/juego/jugar');
                return;
            }
            Redirect::to('/lobby/show');
            return;
        }
        Redirect::to('/partida/jugar');
    }


    public function jugar()
    {
        $this->evitarCache();
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        // La partida activa se resuelve desde el usuario de la sesión, no desde
        // un id enviado por la URL o por el formulario.
        $partida = $this->model->getPartidaActiva($usuarioId);
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

        $avisoModo = $_SESSION['aviso_modo'] ?? null;
        unset($_SESSION['aviso_modo']);
        $this->render->renderiza('partida_jugar', [
            'aviso_modo'       => $avisoModo,
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
        $this->evitarCache();
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        if (!$this->esPost()) {
            Redirect::to('/lobby/show');
            return;
        }

        $res = $this->model->responder(
            $usuarioId,
            $_POST['respuesta'] ?? '',
            ($_POST['usar_trampita'] ?? '') === '1'
        );

        if ($res === null) {
            Redirect::to('/partida/jugar');
            return;
        }

        $partidaId = (int) $res['partida_id'];
        $partida  = $this->model->getPartida($partidaId);
        $marcador = $this->model->getMarcador($partidaId);

        $res['logueado']      = true;
        $res['bot_nombre']    = $partida['bot_nombre'];
        $res['puntos_humano'] = $marcador['humano'];
        $res['puntos_bot']    = $marcador['bot'];
        $res['url_siguiente'] = $res['terminada']
            ? '/partida/resultado?id=' . $partidaId
            : '/partida/jugar';

        $this->render->renderiza('partida_feedback', $res);
    }


    public function resultado()
    {
        $this->evitarCache();
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }

        $data = $this->model->getResultado(Request::get('id'));
        if (!$data || (int)$data['jugador1_id'] !== $usuarioId) {
            Redirect::to('/lobby/show');
            return;
        }

        $preguntasPartida = $this->model->getPreguntasDePartida((int) $data['id']);
        $data['preguntas_partida'] = $preguntasPartida;
        $data['hay_preguntas_partida'] = count($preguntasPartida) > 0;
        $data['partida_id'] = (int) $data['id'];

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


    private function evitarCache()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function esPost()
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}
