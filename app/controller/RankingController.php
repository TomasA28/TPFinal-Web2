<?php

namespace app\controller;

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
        $data["ranking"] = $this->rankingModel->getRanking();
        $this->render->renderiza("rankingView", $data);
    }
}