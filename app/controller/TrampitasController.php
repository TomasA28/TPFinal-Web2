<?php

namespace app\controller;
use Redirect;

class TrampitasController
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
            Redirect::toIndex();
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $resultado = $_GET['resultado'] ?? '';

        $data = [
            'logueado' => true,
            'saldo' => $this->model->getSaldo($usuarioId),
            'movimientos' => $this->model->getHistorial($usuarioId),
            'compra_ok' => $resultado === 'compra',
            'uso_ok' => $resultado === 'uso',
            'sin_saldo' => $resultado === 'sin_saldo'
        ];

        $this->render->renderiza('trampitasView', $data);
    }

    public function comprar()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $this->model->comprarUnaTrampita($usuarioId);

        Redirect::to('/trampitas/show?resultado=compra');
    }

    public function usar()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $seUso = $this->model->usarUnaTrampita($usuarioId);

        if ($seUso) {
            Redirect::to('/trampitas/show?resultado=uso');
            return;
        }

        Redirect::to('/trampitas/show?resultado=sin_saldo');
    }
}