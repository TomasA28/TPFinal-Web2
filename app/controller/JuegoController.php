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
        $this->evitarCache();
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($this->juegoModel->tienePartidaVsBotActiva($usuarioId)) {
            Redirect::to('/partida/jugar');
            return;
        }
        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        $preguntaId = $this->juegoModel->getPreguntaIdActiva($usuarioId);
        if ($preguntaId !== null) {
            $_SESSION['partida_ruleta_en_curso'] = true;
            $pregunta = $this->juegoModel->getPreguntaActiva($usuarioId, $preguntaId);
            if ($pregunta !== null) {
                $_SESSION['pregunta_activa_id'] = $preguntaId;
                $_SESSION['pregunta_activa_expira_en'] = (int) $pregunta['expira_en'];
                $this->renderizarPregunta($pregunta, $_SESSION['pregunta_activa_expira_en']);
                return;
            }
        }

        $avisoModo = $_SESSION['aviso_modo'] ?? null;
        unset($_SESSION['aviso_modo']);
        $this->render->renderiza('ruletaView', ['aviso_modo' => $avisoModo]);
    }

    public function nuevaPartida()
    {
        $this->evitarCache();
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($this->juegoModel->tienePartidaVsBotActiva($usuarioId)) {
            if (!$this->juegoModel->cerrarPartidaVsBotActiva($usuarioId)) {
                Redirect::to('/partida/jugar');
                return;
            }
            unset(
                $_SESSION['preguntas_vistas'],
                $_SESSION['puntaje_actual'],
                $_SESSION['pregunta_activa_id'],
                $_SESSION['pregunta_activa_expira_en']
            );
            $_SESSION['partida_ruleta_en_curso'] = false;
            $_SESSION['aviso_modo'] = 'La partida contra el bot se cerró al iniciar el modo solo.';
        }
        if ($this->juegoModel->getPreguntaIdActiva($usuarioId) !== null) {
            Redirect::to('/juego/jugar');
            return;
        }
        if (!empty($_SESSION['partida_ruleta_en_curso'])) {
            Redirect::to('/juego/jugar');
            return;
        }
        $_SESSION['preguntas_vistas'] = [];
        $_SESSION['puntaje_actual'] = 0;
        $_SESSION['partida_ruleta_en_curso'] = true;
        unset($_SESSION['ultimo_resumen'], $_SESSION['ultimo_resumen_puntaje']);
        $this->jugar();
    }

    public function procesarPregunta()
    {
        $this->evitarCache();
        if ($this->juegoModel->tienePartidaVsBotActiva((int) ($_SESSION['usuario_id'] ?? 0))) {
            Redirect::to('/partida/jugar');
            return;
        }
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
        $_SESSION['partida_ruleta_en_curso'] = true;
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
        $this->evitarCache();
        if ($this->juegoModel->tienePartidaVsBotActiva((int) ($_SESSION['usuario_id'] ?? 0))) {
            Redirect::to('/partida/jugar');
            return;
        }
        $respuestaElegida = Request::post('respuesta');
        $resultado = $this->juegoModel->procesarRespuesta(
            $respuestaElegida,
            $_SESSION['usuario_id']
        );

        if ($resultado === null) {
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'No se pudo registrar la respuesta. Volvé a elegir una pregunta.',
            ]);
            return;
        }

        $preguntaId = (int) $resultado['pregunta_id'];
        $esCorrecta = $resultado['es_correcta'];

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
            $data = [
                'es_correcta' => true,
                'puntaje_actual' => $_SESSION['puntaje_actual']
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

            $data = [
                'es_incorrecta' => true,
                'puntaje_final' => $puntajeFinal,
                'resumen_preguntas' => $resumen,
                'hay_resumen' => count($resumen) > 0
            ];

            unset($_SESSION['preguntas_vistas'], $_SESSION['puntaje_actual']);
            $_SESSION['partida_ruleta_en_curso'] = false;
        }

        $this->render->renderiza('juegoView', $data);
    }

    /**
     * GET del resumen de la partida de la ruleta, para que el botón "Volver"
     * del reporte pueda volver al mismo lugar donde se generó el link.
     */
    public function resumen()
    {
        $this->evitarCache();
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return;
        }

        if (empty($_SESSION['ultimo_resumen'])) {
            Redirect::to('/juego/jugar');
            return;
        }

        $this->render->renderiza('juegoView', [
            'es_incorrecta'     => true,
            'puntaje_final'     => (int) ($_SESSION['ultimo_resumen_puntaje'] ?? 0),
            'resumen_preguntas' => $_SESSION['ultimo_resumen'],
            'hay_resumen'       => true,
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
        $this->evitarCache();
        $uid = (int) $_SESSION['usuario_id'];
        if ($this->juegoModel->tienePartidaVsBotActiva($uid)) {
            Redirect::to('/partida/jugar');
            return;
        }
        $preguntaId = $this->juegoModel->getPreguntaIdActiva($uid);

        if ($preguntaId === null) {
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

        if ($this->juegoModel->procesarRespuestaConTrampita($uid) === null) {
            $this->trampitasModel->devolver($uid);   // ver nota abajo
            $this->render->renderiza('juegoView', [
                'error_respuesta' => 'La pregunta expiró. No se descontó la trampita.',
            ]);
            return;
        }

        unset($_SESSION['pregunta_activa_id'], $_SESSION['pregunta_activa_expira_en']);
        $_SESSION['puntaje_actual'] = ($_SESSION['puntaje_actual'] ?? 0) + 1;

        $this->render->renderiza('juegoView', [
            'es_correcta'    => true,
            'puntaje_actual' => $_SESSION['puntaje_actual'],
            'uso_trampita'   => true,
        ]);
    }

    private function evitarCache()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
