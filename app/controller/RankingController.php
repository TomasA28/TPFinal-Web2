<?php

namespace app\controller;

use Redirect;
use Request;

class RankingController
{
    private $rankingModel;
    private $render;

    public function __construct($rankingModel, $render)
    {
        $this->rankingModel = $rankingModel;
        $this->render = $render;
    }

    public function show()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            exit;
        }

        $idActual = $_SESSION['usuario_id'];
        $periodo = $this->rankingModel->periodoValido(Request::get('periodo', 'mes'));
        $ranking = $this->rankingModel->getRanking($periodo);

        foreach ($ranking as $i => $fila) {
            $ranking[$i]['posicion'] = $i + 1;
            $ranking[$i]['es_usuario_actual'] = ($fila['id'] == $idActual);
            $ranking[$i]['perfilDelUsuario'] = '/perfil/show?id=' . (int)$fila['id'];
        }

        $data["ranking"] = $ranking;
        $data["hay_ranking"] = !empty($ranking);
        $data["periodo_dia"] = $periodo === 'dia';
        $data["periodo_semana"] = $periodo === 'semana';
        $data["periodo_mes"] = $periodo === 'mes';

        $this->render->renderiza("rankingView", $data);
    }
}