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

    private $trampitasModel;


    public function __construct($juegoModel, $render, $trampitasModel)
    {
        $this->juegoModel = $juegoModel;
        $this->render = $render;
        $this->trampitasModel = $trampitasModel;
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
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        $this->juegoModel->limpiarPreguntaActiva((int) $_SESSION['usuario_id']);
        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);
        $_SESSION['preguntas_vistas'] = [];
        $_SESSION['puntaje_actual'] = 0;
        unset($_SESSION['ultimo_resumen'], $_SESSION['ultimo_resumen_puntaje']);
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
        $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $respuestaElegida = Request::post('respuesta');
        $preguntaId = Request::post('pregunta_id');

        if (
            !isset($_SESSION['pregunta_activa_id'])
            || (int) $_SESSION['pregunta_activa_id'] !== (int) $preguntaId
        ) {
            $mensajeError = 'La pregunta enviada ya no está activa. Volvé a elegir una pregunta.';
            if ($esAjax) {
                $this->responderAjax(['error' => $mensajeError], 409);
                return;
            }

            $this->render->renderiza('juegoView', [
                'error_respuesta' => $mensajeError,
            ]);
            return;
        }

        $esCorrecta = $this->juegoModel->procesarRespuesta(
            $respuestaElegida,
            $preguntaId,
            $_SESSION['usuario_id']
        );

        if ($esCorrecta === null) {
            $mensajeError = 'No se pudo registrar la respuesta. Volvé a elegir una pregunta.';
            if ($esAjax) {
                $this->responderAjax(['error' => $mensajeError], 422);
                return;
            }

            $this->render->renderiza('juegoView', [
                'error_respuesta' => $mensajeError,
            ]);
            return;
        }

        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);

        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }

        if (!isset($_SESSION['preguntas_vistas'])) {
            $_SESSION['preguntas_vistas'] = [];
        }

        if (!in_array((int) $preguntaId, $_SESSION['preguntas_vistas'], true)) {
            $_SESSION['preguntas_vistas'][] = (int) $preguntaId;
        }

        if ($esCorrecta) {
            $_SESSION['puntaje_actual']++;
            $puntaje = $_SESSION['puntaje_actual'];
            $urlRedireccion = '/juego/jugar';
            $data = [
                'resultado_modal' => true,
                'resultado_clase' => 'resultado-modal--correcta',
                'resultado_icono' => '✓',
                'resultado_titulo' => 'Respuesta Correcta!',
                'resultado_mensaje' => 'Tu puntaje actual es:',
                'puntaje_resultado' => $puntaje,
                'resultado_sufijo' => '',
                'resultado_url' => $urlRedireccion,
                'resultado_accion' => 'Siguiente pregunta',
                'pregunta_id' => (int) $preguntaId,
            ];
        } else {

            $usuarioId = $_SESSION['usuario_id'];
            $puntajeFinal = $_SESSION['puntaje_actual'];
            $puntajeFinal === null ? $puntajeFinal = 0 : $puntajeFinal;

            $this->juegoModel->guardarPartidaTerminada($usuarioId, $puntajeFinal, count($_SESSION['preguntas_vistas']));

            $resumen = $this->juegoModel->getPreguntasPorIds($_SESSION['preguntas_vistas']);

            // Guardamos el resumen para poder volver a él desde el formulario de reporte.
            $_SESSION['ultimo_resumen'] = $resumen;
            $_SESSION['ultimo_resumen_puntaje'] = $puntajeFinal;

            $puntaje = $puntajeFinal;
            $urlRedireccion = '/lobby/show';
            $data = [
                'resultado_modal' => true,
                'resultado_clase' => 'resultado-modal--incorrecta',
                'resultado_icono' => '×',
                'resultado_titulo' => 'Respuesta Incorrecta!',
                'resultado_mensaje' => 'Hiciste un total de:',
                'puntaje_resultado' => $puntajeFinal,
                'resultado_sufijo' => ' puntos.',
                'resultado_url' => $urlRedireccion,
                'resultado_accion' => 'Volver al lobby',
                'pregunta_id' => (int) $preguntaId,
            ];

            unset($_SESSION['preguntas_vistas'], $_SESSION['puntaje_actual']);
        }

        if ($esAjax) {
            $respuesta = [
                'es_correcta' => (bool) $esCorrecta,
                'url_redireccion' => $urlRedireccion,
            ];
            $respuesta[$esCorrecta ? 'puntaje_actual' : 'puntaje_final'] = $puntaje;
            $this->responderAjax($respuesta);
            return;
        }

        $this->render->renderiza('juegoView', $data);
    }

    private function responderAjax($datos, $codigoEstado = 200)
    {
        http_response_code($codigoEstado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos);
    }
    /**
     * GET del resumen de la partida de la ruleta, para que el botón "Volver"
     * del reporte pueda volver al mismo lugar donde se generó el link.
     */
    public function resumen()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        if (empty($_SESSION['ultimo_resumen'])) {
            Redirect::to('/juego/jugar');
            return;
        }

        $this->render->renderiza('juegoView', [
            'es_incorrecta' => true,
            'puntaje_final' => (int) ($_SESSION['ultimo_resumen_puntaje'] ?? 0),
            'resumen_preguntas' => $_SESSION['ultimo_resumen'],
            'hay_resumen' => true,
        ]);
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


    public function usarTrampita()
    {
        $uid = (int) $_SESSION['usuario_id'];
        $preguntaId = (int) Request::post('pregunta_id');

        if (
            !isset($_SESSION['pregunta_activa_id'])
            || (int) $_SESSION['pregunta_activa_id'] !== $preguntaId
        ) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'La pregunta ya no está activa.',
            ]);
            return;
        }

        // Primero verificamos que la pregunta siga vigente, después gastamos
        if (!$this->trampitasModel->consumir($uid, null, $preguntaId)) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'No tenés trampitas disponibles.',
            ]);
            return;
        }

        if ($this->juegoModel->procesarRespuestaConTrampita($preguntaId, $uid) === null) {
            $this->trampitasModel->devolver($uid);   // ver nota abajo
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'La pregunta expiró. No se descontó la trampita.',
            ]);
            return;
        }

        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);
        $_SESSION['puntaje_actual'] = ($_SESSION['puntaje_actual'] ?? 0) + 1;

        $this->render->renderiza('juegoView', [
            'es_correcta' => true,
            'puntaje_actual' => $_SESSION['puntaje_actual'],
            'uso_trampita' => true,
        ]);
    }
}