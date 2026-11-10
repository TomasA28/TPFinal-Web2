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
        if (!$usuarioId) { return; }


        $mensaje = $_SESSION['trampitas_flash'] ?? '';
        unset($_SESSION['trampitas_flash']);

        $this->render->renderiza('trampitasView', [
            'logueado'    => true,
            'saldo'       => $this->model->getSaldo($usuarioId),
            'movimientos' => $this->model->getHistorial($usuarioId),
            'compra_ok'   => $mensaje === 'compra',
            'sin_saldo'   => $mensaje === 'sin_saldo',
            'error'       => $mensaje === 'error',
        ]);
    }


    public function comprar()
    {
        $usuarioId = $this->exigirLogin();
        if (!$usuarioId) { return; }
        if (!$this->esPost()) { Redirect::to('/trampitas/show'); return; }

        $cantidad = (int)($_POST['cantidad'] ?? 1);
        $ok = $this->model->comprar($usuarioId, $cantidad);

        $_SESSION['trampitas_flash'] = $ok ? 'compra' : 'error';
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

    private function esPost()
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}