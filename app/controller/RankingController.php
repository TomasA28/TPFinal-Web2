<?php

namespace app\controller;

use Redirect;

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
        $ranking = $this->rankingModel->getRanking();

        foreach ($ranking as $i => $fila) {
            $ranking[$i]['posicion'] = $i + 1;
            $ranking[$i]['es_usuario_actual'] = ($fila['id'] == $idActual);
        }

        $data["ranking"] = $ranking;
        $data["hay_ranking"] = !empty($ranking);

        $this->render->renderiza("rankingView", $data);
    }
}