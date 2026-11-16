<?php
namespace app\controller;

use Redirect;
use Request;

class JuegoController
{
    private $categorias = [
        'historia' => 'Historia',
        'cultura' => 'Cultura',
        'deporte' => 'Deporte',
        'arte' => 'Arte',
        'ciencia' => 'Ciencia',
    ];

    private $juegoModel;
    private $render;

    public function __construct($juegoModel, $render)
    {
        $this->juegoModel = $juegoModel;
        $this->render = $render;
    }
    public function jugar()
    {
        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }

        $this->render->renderiza('ruletaView');
    }

    public function nuevaPartida()
    {
        $this->juegoModel->limpiarPreguntaActiva($_SESSION['usuario_id']);
        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);
        $_SESSION['puntaje_actual'] = 0;
        $this->jugar();
    }

    public function procesarPregunta()
    {
        if (isset($_SESSION['pregunta_activa_id'])) {
            $pregunta = $this->juegoModel->getPreguntaActiva(
                $_SESSION['usuario_id'],
                $_SESSION['pregunta_activa_id']
            );

            if ($pregunta !== null) {
                if (!isset($_SESSION['pregunta_activa_expira_en'])) {
                    $_SESSION['pregunta_activa_expira_en'] = (int) $pregunta['expira_en'];
                }

                $this->renderizarPregunta($pregunta, $_SESSION['pregunta_activa_expira_en']);
                return;
            }

            unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);
        }

        $categoriaElegida = Request::post('categoria');
        if (!\is_string($categoriaElegida) || !isset($this->categorias[$categoriaElegida])) {
            $this->render->renderiza('ruletaView', [
                'error' => 'Seleccioná una categoría válida para continuar.',
            ]);
            return;
        }

        $categoria = $this->categorias[$categoriaElegida];
        $pregunta = $this->juegoModel->getPreguntaAleatoria(
            $categoria,
            $_SESSION['usuario_id']
        );
        if ($pregunta === null) {
            $this->render->renderiza('juegoView', [
                'sin_pregunta' => true,
                'categoria_sin_preguntas' => $categoria,
            ]);
            return;
        }

        $preguntaActiva = $this->juegoModel->guardarPreguntaActiva(
            $_SESSION['usuario_id'],
            $pregunta['id']
        );
        $_SESSION['pregunta_activa_id'] = (int) $preguntaActiva['id'];
        $_SESSION['pregunta_activa_expira_en'] = (int) $preguntaActiva['expira_en'];
        $this->renderizarPregunta($preguntaActiva, $_SESSION['pregunta_activa_expira_en']);
    }

    public function procesarRespuesta()
    {
        $respuestaElegida = Request::post('respuesta');
        $preguntaId = Request::post('pregunta_id');

        if (
            !isset($_SESSION['pregunta_activa_id'])
            || (int) $_SESSION['pregunta_activa_id'] !== (int) $preguntaId
        ) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'La pregunta enviada ya no está activa. Volvé a elegir una pregunta.',
            ]);
            return;
        }

        $esCorrecta = $this->juegoModel->procesarRespuesta(
            $respuestaElegida,
            $preguntaId,
            $_SESSION['usuario_id']
        );

        if ($esCorrecta === null) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'No se pudo registrar la respuesta. Volvé a elegir una pregunta.',
            ]);
            return;
        }

        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);

        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }

        if ($esCorrecta) {
            $_SESSION['puntaje_actual']++;
            $data = [
                'es_correcta' => true,
                'puntaje_actual' => $_SESSION['puntaje_actual']
            ];
        } else {

            $usuarioId = $_SESSION['usuario_id'];
            $puntajeFinal = $_SESSION['puntaje_actual'];
            $puntajeFinal === null ? $puntajeFinal = 0 : $puntajeFinal;

            $this->juegoModel->guardarPartidaTerminada($usuarioId, $puntajeFinal);

            $data = [
                'es_incorrecta' => true,
                'puntaje_final' => $puntajeFinal
            ];

            unset($_SESSION['puntaje_actual']);
        }

        $this->render->renderiza('juegoView', $data);
    }

    private function renderizarPregunta($pregunta, $expiraEn)
    {
        $this->render->renderiza('juegoView', [
            'id' => $pregunta['id'],
            'categoria' => strtolower($pregunta['categoria']),
            'pregunta' => $pregunta['descripcion'],
            'opcionA' => $pregunta['opcion_a'],
            'opcionB' => $pregunta['opcion_b'],
            'opcionC' => $pregunta['opcion_c'],
            'opcionD' => $pregunta['opcion_d'],
            'expira_en_ms' => (int) $expiraEn * 1000,
        ]);
    }
}