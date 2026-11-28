<?php

namespace app\controller;

use Redirect;
use Request;

class ReporteController
{
    private const MOTIVOS_VALIDOS = [
        'Respuesta incorrecta',
        'Ortografía/gramática',
        'Categoría errónea',
    ];

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
        if ($usuarioId === null) {
            return;
        }

        $preguntaIdInput = Request::get('pregunta_id');
        $preguntaId = \is_string($preguntaIdInput)
            ? filter_var($preguntaIdInput, FILTER_VALIDATE_INT)
            : false;
        if (!$preguntaId || !$this->model->existePregunta($preguntaId)) {
            $this->render->renderiza('reportePreguntaView', [
                'error_reporte' => 'No se encontró la pregunta que querés reportar.',
            ]);
            return;
        }

        $this->render->renderiza('reportePreguntaView', [
            'pregunta_id' => $preguntaId,
            'usuario_id' => $usuarioId,
            'mostrar_formulario' => true,
        ]);
    }

    public function reportarPregunta()
    {
        $usuarioId = $this->exigirLogin();
        if ($usuarioId === null) {
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/lobby/show');
            return;
        }

        $preguntaIdInput = Request::post('pregunta_id');
        $preguntaId = \is_string($preguntaIdInput)
            ? filter_var($preguntaIdInput, FILTER_VALIDATE_INT)
            : false;
        $motivo = Request::post('motivo');
        $motivo = \is_string($motivo) ? $motivo : '';
        $comentarioInput = Request::post('comentario', '');
        $comentarioValido = \is_string($comentarioInput);
        $comentario = $comentarioValido ? trim($comentarioInput) : '';

        $datosVista = [
            'pregunta_id' => $preguntaId ?: '',
            'usuario_id' => $usuarioId,
            'motivo_respuesta_incorrecta' => $motivo === 'Respuesta incorrecta',
            'motivo_ortografia' => $motivo === 'Ortografía/gramática',
            'motivo_categoria' => $motivo === 'Categoría errónea',
        ];

        if (
            !$preguntaId
            || !\in_array($motivo, self::MOTIVOS_VALIDOS, true)
            || !$comentarioValido
            || \strlen($comentario) > 1200
            || preg_match_all('/./us', $comentario, $caracteres) === false
            || \count($caracteres[0]) > 300
        ) {
            $this->render->renderiza('reportePreguntaView', $datosVista + [
                'mostrar_formulario' => true,
                'error_reporte' => 'Error al enviar reporte.',
                'comentario' => $comentario,
            ]);
            return;
        }

        try {
            $reporteGuardado = $this->model->existePregunta($preguntaId)
                && $this->model->guardarReporte(
                    $usuarioId,
                    $preguntaId,
                    $motivo,
                    $comentario === '' ? null : $comentario
                );
        } catch (\Throwable $error) {
            \Log::error('No se pudo guardar un reporte de pregunta: ' . $error->getMessage());
            $reporteGuardado = false;
        }

        $datosVista['reporte_exitoso'] = $reporteGuardado;
        $datosVista['reporte_fallido'] = !$reporteGuardado;
        $datosVista['mostrar_formulario'] = !$reporteGuardado;
        $datosVista['comentario'] = $comentario;

        $this->render->renderiza('reportePreguntaView', $datosVista);
    }

    private function exigirLogin()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::toIndex();
            return null;
        }

        return (int) $_SESSION['usuario_id'];
    }
}
