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

        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));

        // Flash: se lee y se borra, para que no reaparezca al refrescar
        $flash = $_SESSION['trampitas_flash'] ?? null;
        unset($_SESSION['trampitas_flash']);

        $data = [
            'logueado'    => true,
            'saldo'       => $this->model->getSaldo($usuarioId),
            'csrf'        => $_SESSION['csrf'],
            'compra_ok'   => $flash === 'compra',
            'error'       => $flash === 'error',
            'movimientos' => $this->model->getMovimientos($usuarioId),
        ];

        $this->render->renderiza("trampitasView", $data);
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

        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            $_SESSION['trampitas_flash'] = 'error';
            Redirect::to('/trampitas/show');
            return;
        }

        $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;

        $compraRealizada = $this->model->comprar($usuarioId, $cantidad);

        $_SESSION['trampitas_flash'] = $compraRealizada ? 'compra' : 'error';

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