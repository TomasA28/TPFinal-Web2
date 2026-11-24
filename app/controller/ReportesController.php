<?php

namespace app\controller;

use app\model\ReportesModel;
use Redirect;
use Request;

class ReportesController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function nueva()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $preguntaId = (int) Request::get('pregunta_id');
        $partidaId = (int) Request::get('partida_id');

        if ($partidaId > 0 && !$this->model->partidaDelUsuario($partidaId, $usuarioId)) {
            $partidaId = 0;
        }

        $urlVolver = self::urlVolver(Request::get('volver'), $partidaId);

        $exito = null;
        if (Request::get('exito') !== null) {
            $exito = 'Gracias, tu reporte fue enviado. Lo revisarán los editores.';
        }

        $this->renderizarFormulario($usuarioId, $preguntaId, $partidaId, null, $exito, $urlVolver);
    }

    public function crear()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $preguntaId = (int) Request::post('pregunta_id');
        $partidaId = (int) Request::post('partida_id');
        $motivo = Request::post('motivo');
        $detalle = Request::post('detalle');

        if ($partidaId > 0 && !$this->model->partidaDelUsuario($partidaId, $usuarioId)) {
            $partidaId = 0;
        }

        $urlVolver = self::urlVolver(Request::post('volver') ?? Request::get('volver'), $partidaId);

        $resultado = $this->model->crear($usuarioId, $preguntaId, $motivo, $detalle, $partidaId);

        if ($resultado['ok']) {
            Redirect::to('/reportes/nueva?pregunta_id=' . $preguntaId . '&exito=1'
                . ($partidaId > 0 ? '&partida_id=' . $partidaId : '')
                . '&volver=' . urlencode($urlVolver));
            return;
        }

        $this->renderizarFormulario($usuarioId, $preguntaId, $partidaId, $resultado['error'], null, $urlVolver);
    }

    private function renderizarFormulario($usuarioId, $preguntaId, $partidaId, $error = null, $exito = null, $urlVolver = null)
    {
        $urlVolver = $urlVolver ?? self::urlVolver(null, $partidaId);

        $yaReportada = false;
        $tienePregunta = false;
        $pregunta = $this->model->getPreguntaParaReportar($preguntaId, $usuarioId);

        if ($pregunta !== null) {
            $tienePregunta = true;
            $yaReportada = $this->model->existeReporte($usuarioId, $preguntaId);
        }

        if ($error === null) {
            $motivo = Request::get('motivo');
            $detalle = null;
        } else {
            $motivo = Request::post('motivo');
            $detalle = Request::post('detalle');
        }

        $motivos = [];
        foreach (ReportesModel::motivos() as $valor => $texto) {
            $motivos[] = [
                'valor'        => $valor,
                'texto'        => $texto,
                'seleccionado' => ($motivo ?? '') === $valor,
            ];
        }

        $exitoCorrecto = $exito !== null;

        $this->render->renderiza('reporteView', [
            'exito'           => $exito,
            'error'           => $error,
            'ya_reportada'   => $yaReportada,
            'tiene_pregunta' => $tienePregunta,
            'pregunta_descripcion' => $pregunta['descripcion'] ?? '',
            'pregunta_categoria'  => $pregunta['categoria'] ?? '',
            'pregunta_id'            => $pregunta !== null ? (int) $pregunta['id'] : $preguntaId,
            'partida_id'             => $partidaId,
            'motivos'         => $motivos,
            'detalle'         => $detalle ?? '',
            'url_volver'      => $urlVolver,
            'url_volver_es_lobby' => $urlVolver === '/lobby/show',
            'exito_correcto' => $exitoCorrecto,
        ]);
    }

    /**
     * El destino del botón "Volver" viaja en la URL del reporte (y en el form)
     * para que apunte siempre al resumen del que se salió, no al lobby fijo.
     * Solo se aceptan rutas internas para no permitir redirecciones externas.
     */
    private static function urlVolver($valor, $partidaId)
    {
        if (is_string($valor) && self::esUrlInterna($valor)) {
            return $valor;
        }

        if ((int) $partidaId > 0) {
            return '/partida/resultado?id=' . (int) $partidaId;
        }

        return '/lobby/show';
    }

    private static function esUrlInterna($valor)
    {
        return $valor !== ''
            && strpos($valor, '/') === 0
            && strpos($valor, '//') !== 0
            && preg_match('/[\r\n\\\\]/', $valor) !== 1;
    }
}
