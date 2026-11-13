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
        $usuarioId = $this->exigirLogin();

        if (!$usuarioId) {
            return;
        }

        $mensaje = $_SESSION['trampitas_flash'] ?? '';
        unset($_SESSION['trampitas_flash']);

        $this->render->renderiza('trampitasView', [
            'saldo' => $this->model->getSaldo($usuarioId),
            'movimientos' => $this->model->getHistorial($usuarioId),
            'compra_ok' => $mensaje === 'compra',
            'error' => $mensaje === 'error'
        ]);
    }

    public function comprar()
    {
        $usuarioId = $this->exigirLogin();

        if (!$usuarioId) {
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/trampitas/show');
            return;
        }

        $cantidad = isset($_POST['cantidad'])
            ? (int)$_POST['cantidad']
            : 0;

        $compraRealizada = $this->model->comprar($usuarioId, $cantidad);

        if ($compraRealizada) {
            $_SESSION['trampitas_flash'] = 'compra';
        } else {
            $_SESSION['trampitas_flash'] = 'error';
        }

        Redirect::to('/trampitas/show');
    }

    private function exigirLogin()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return null;
        }

        return (int)$_SESSION['usuario_id'];
    }
}