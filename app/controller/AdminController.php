<?php

namespace app\controller;
use Redirect;
use Request;
class AdminController
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

        if (!$this->exigirAdmin()) { return; }

        $periodo = $this->model->periodoValido(Request::get('periodo'));

        $porPais = $this->model->getUsuariosPorPais($periodo);
        $porSexo = $this->model->getUsuariosPorSexo($periodo);
        $porEdad = $this->model->getUsuariosPorEdad($periodo);

        $data = [
            'logueado'    => true,
            'periodo'     => $periodo,
            'periodos'    => $this->armarPeriodos($periodo, '/admin/show'),
            'resumen'     => $this->model->getResumen($periodo),
            'acierto'     => $this->model->getAciertoPorUsuario($periodo),
            'porPais'     => $porPais,
            'porSexo'     => $porSexo,
            'porEdad'     => $porEdad,
            'paisLabels'  => json_encode(array_column($porPais, 'etiqueta')),
            'paisValores' => json_encode(array_map('intval', array_column($porPais, 'total'))),
            'sexoLabels'  => json_encode(array_column($porSexo, 'etiqueta')),
            'sexoValores' => json_encode(array_map('intval', array_column($porSexo, 'total'))),
            'edadLabels'  => json_encode(array_column($porEdad, 'etiqueta')),
            'edadValores' => json_encode(array_map('intval', array_column($porEdad, 'total'))),
        ];

        $this->render->renderiza('adminView', $data);
    }

    public function trampitas()
    {
        if (!$this->exigirAdmin()) { return; }

        $periodo = $this->model->periodoValido(Request::get('periodo'));

        $data = [
            'logueado'  => true,
            'periodo'   => $periodo,
            'periodos'  => $this->armarPeriodos($periodo, '/admin/trampitas'),
            'ganancias' => $this->model->getGananciasTrampitas($periodo),
            'balance'   => $this->model->getBalanceTrampitas(),
        ];

        $this->render->renderiza('adminTrampitasView', $data);
    }

    private function armarPeriodos($actual, $ruta)
    {
        $nombres = ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'anio' => 'Año'];
        $out = [];
        foreach ($nombres as $valor => $nombre) {
            $out[] = [
                'nombre' => $nombre,
                'url'    => $ruta . '?periodo=' . $valor,
                'activo' => $valor === $actual,
            ];
        }
        return $out;
    }

    private function exigirAdmin()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return false;
        }
        if (($_SESSION['rol'] ?? '') !== 'administrador') {
            Redirect::to('/lobby/show');
            return false;
        }
        return true;
    }
}