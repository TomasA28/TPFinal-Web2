<?php

namespace app\controller;

use app\model\ReportesModel;
use Redirect;
use Request;

class AdministracionController
{
    private const SECCIONES = ['sugerencias', 'reportes', 'preguntas'];
    private const CATEGORIAS = ['Historia', 'Cultura', 'Deporte', 'Arte', 'Ciencia'];

    private $sugerencias;
    private $preguntas;
    private $reportes;
    private $render;

    public function __construct($sugerenciasModel, $preguntasModel, $reportesModel, $render)
    {
        $this->sugerencias = $sugerenciasModel;
        $this->preguntas   = $preguntasModel;
        $this->reportes    = $reportesModel;
        $this->render      = $render;
    }

    public function show()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $seccion = Request::get('seccion', 'sugerencias');
        if (!in_array($seccion, self::SECCIONES, true)) {
            $seccion = 'sugerencias';
        }

        $conteosSugerencias = $this->sugerencias->contarPorEstado();

        $datos = [
            'seccion'                  => $seccion,
            'es_seccion_sugerencias'   => $seccion === 'sugerencias',
            'es_seccion_reportes'      => $seccion === 'reportes',
            'es_seccion_preguntas'     => $seccion === 'preguntas',
            'exito'                    => $this->mensajeExito(Request::get('exito')),
            'error'                    => $this->mensajeError(Request::get('error')),
            'pendientes_sugerencias'   => $conteosSugerencias['pendiente'],
            'aprobadas_sugerencias'    => $conteosSugerencias['aprobada'],
            'rechazadas_sugerencias'   => $conteosSugerencias['rechazada'],
            'pendientes_reportes'      => $this->reportes->contarPendientes(),
            'total_preguntas'          => $this->preguntas->contarTodas(),
            'total_preguntas_activas'  => $this->preguntas->contarActivas(),
        ];

        if ($seccion === 'sugerencias') {
            $datos = array_merge($datos, $this->datosSugerencias());
        } elseif ($seccion === 'reportes') {
            $datos = array_merge($datos, $this->datosReportes());
        } else {
            $datos = array_merge($datos, $this->datosPreguntas());
        }

        $this->render->renderiza('administracionView', $datos);
    }

    public function aprobarSugerencia()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $resultado = $this->sugerencias->aprobar((int) Request::post('id'), $this->usuarioId());

        if ($resultado['ok']) {
            Redirect::to('/administracion/show?seccion=sugerencias&exito=sugerencia_aprobada');
            return;
        }

        Redirect::to('/administracion/show?seccion=sugerencias&error=' . $this->codigoError($resultado));
    }

    public function rechazarSugerencia()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $resultado = $this->sugerencias->rechazar(
            (int) Request::post('id'),
            Request::post('motivo'),
            $this->usuarioId()
        );

        if ($resultado['ok']) {
            Redirect::to('/administracion/show?seccion=sugerencias&exito=sugerencia_rechazada');
            return;
        }

        Redirect::to('/administracion/show?seccion=sugerencias&error=' . $this->codigoError($resultado));
    }

    public function sugerenciaForm()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $sugerencia = $this->sugerencias->getPorId((int) Request::get('id'));

        if ($sugerencia === null || $sugerencia['estado'] !== 'pendiente') {
            Redirect::to('/administracion/show?seccion=sugerencias&error=no_encontrado');
            return;
        }

        $this->renderizarFormulario([
            'titulo'         => 'Editar y aprobar sugerencia',
            'subtitulo'      => 'Revisá los datos, corregí lo que haga falta y aprobala. La pregunta se agregará al juego.',
            'form_action'    => '/administracion/aprobarSugerenciaEditada',
            'texto_boton'    => 'Aprobar sugerencia',
            'pregunta'       => $sugerencia,
            'sugerencia_id'  => (int) $sugerencia['id'],
            'pregunta_id'    => '',
            'reporte_id'     => '',
            'url_volver'     => '/administracion/show?seccion=sugerencias',
        ]);
    }

    public function aprobarSugerenciaEditada()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $sugerenciaId = (int) Request::post('sugerencia_id');
        $datos = $this->datosPreguntaPost();
        $resultado = $this->sugerencias->aprobar($sugerenciaId, $this->usuarioId(), $datos);

        if ($resultado['ok']) {
            Redirect::to('/administracion/show?seccion=sugerencias&exito=sugerencia_aprobada');
            return;
        }

        if (isset($resultado['codigo'])) {
            Redirect::to('/administracion/show?seccion=sugerencias&error=' . $resultado['codigo']);
            return;
        }

        $this->renderizarFormulario([
            'titulo'         => 'Editar y aprobar sugerencia',
            'subtitulo'      => 'Revisá los datos, corregí lo que haga falta y aprobala. La pregunta se agregará al juego.',
            'form_action'    => '/administracion/aprobarSugerenciaEditada',
            'texto_boton'    => 'Aprobar sugerencia',
            'error'          => $resultado['error'],
            'pregunta'       => $datos,
            'sugerencia_id'  => $sugerenciaId,
            'pregunta_id'    => '',
            'reporte_id'     => '',
            'url_volver'     => '/administracion/show?seccion=sugerencias',
        ]);
    }

    public function resolverReporte()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $reporteId = (int) Request::post('id');
        $accion    = Request::post('accion');
        $reporte   = $this->reportes->getPorId($reporteId);

        if ($reporte === null || $reporte['estado'] !== 'pendiente') {
            Redirect::to('/administracion/show?seccion=reportes&error=ya_revisado');
            return;
        }

        $preguntaId = $reporte['pregunta_id'] !== null ? (int) $reporte['pregunta_id'] : 0;

        switch ($accion) {
            case 'editar':
                if ($preguntaId === 0) {
                    Redirect::to('/administracion/show?seccion=reportes&error=no_encontrado');
                    return;
                }
                Redirect::to('/administracion/preguntaForm?modo=editar&id=' . $preguntaId . '&reporte_id=' . $reporteId);
                return;

            case 'desactivar':
                if ($preguntaId === 0) {
                    Redirect::to('/administracion/show?seccion=reportes&error=no_encontrado');
                    return;
                }
                $this->preguntas->cambiarActividad($preguntaId, 0);
                $this->reportes->cerrarPendientesDePregunta($preguntaId, 'desactivada', $this->usuarioId());
                break;

            case 'eliminar':
                if ($preguntaId === 0) {
                    Redirect::to('/administracion/show?seccion=reportes&error=no_encontrado');
                    return;
                }
                $this->reportes->cerrarPendientesDePregunta($preguntaId, 'eliminada', $this->usuarioId());
                $this->preguntas->eliminar($preguntaId);
                break;

            case 'cerrar':
                $this->reportes->resolver($reporteId, 'cerrada', $this->usuarioId());
                break;

            default:
                Redirect::to('/administracion/show?seccion=reportes&error=accion');
                return;
        }

        Redirect::to('/administracion/show?seccion=reportes&exito=reporte_resuelto');
    }

    public function preguntaForm()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $modo      = Request::get('modo', 'crear');
        $pregunta  = null;
        $reporteId = (int) Request::get('reporte_id', 0);

        if ($modo === 'editar') {
            $pregunta = $this->preguntas->getPorId((int) Request::get('id'));

            if ($pregunta === null) {
                Redirect::to('/administracion/show?seccion=preguntas&error=no_encontrado');
                return;
            }
        }

        $urlVolver = $reporteId > 0
            ? '/administracion/show?seccion=reportes'
            : '/administracion/show?seccion=preguntas';

        $this->renderizarFormulario([
            'titulo'        => $modo === 'editar' ? 'Editar pregunta' : 'Crear pregunta',
            'subtitulo'     => $modo === 'editar'
                ? 'Los cambios se aplican de inmediato en el juego.'
                : 'La pregunta que crees acá se agrega directamente al juego, sin pasar por revisión.',
            'form_action'   => '/administracion/guardarPregunta',
            'texto_boton'   => $modo === 'editar' ? 'Guardar cambios' : 'Crear pregunta',
            'pregunta'      => $pregunta ?? [],
            'pregunta_id'   => $pregunta !== null ? (int) $pregunta['id'] : '',
            'reporte_id'    => $reporteId > 0 ? $reporteId : '',
            'url_volver'    => $urlVolver,
        ]);
    }

    public function guardarPregunta()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $preguntaId = (int) Request::post('pregunta_id');
        $reporteId  = (int) Request::post('reporte_id');
        $datos      = $this->datosPreguntaPost();

        if ($preguntaId > 0) {
            $resultado = $this->preguntas->actualizar(
                $preguntaId,
                $datos['descripcion'],
                $datos['opcion_a'],
                $datos['opcion_b'],
                $datos['opcion_c'],
                $datos['opcion_d'],
                $datos['respuesta_correcta'],
                $datos['categoria']
            );

            if ($resultado['ok']) {
                $this->reportes->cerrarPendientesDePregunta($preguntaId, 'editada', $this->usuarioId());

                $destino = $reporteId > 0
                    ? '/administracion/show?seccion=reportes&exito=reporte_resuelto'
                    : '/administracion/show?seccion=preguntas&exito=pregunta_editada';

                Redirect::to($destino);
                return;
            }

            if (isset($resultado['codigo'])) {
                Redirect::to('/administracion/show?seccion=preguntas&error=' . $resultado['codigo']);
                return;
            }
        } else {
            $resultado = $this->preguntas->crear(
                $this->usuarioId(),
                $datos['descripcion'],
                $datos['opcion_a'],
                $datos['opcion_b'],
                $datos['opcion_c'],
                $datos['opcion_d'],
                $datos['respuesta_correcta'],
                $datos['categoria']
            );

            if ($resultado['ok']) {
                Redirect::to('/administracion/show?seccion=preguntas&exito=pregunta_creada');
                return;
            }
        }

        $urlVolver = $reporteId > 0
            ? '/administracion/show?seccion=reportes'
            : '/administracion/show?seccion=preguntas';

        $this->renderizarFormulario([
            'titulo'        => $preguntaId > 0 ? 'Editar pregunta' : 'Crear pregunta',
            'subtitulo'     => $preguntaId > 0
                ? 'Los cambios se aplican de inmediato en el juego.'
                : 'La pregunta que crees acá se agrega directamente al juego, sin pasar por revisión.',
            'form_action'   => '/administracion/guardarPregunta',
            'texto_boton'   => $preguntaId > 0 ? 'Guardar cambios' : 'Crear pregunta',
            'error'         => $resultado['error'],
            'pregunta'      => $datos,
            'pregunta_id'   => $preguntaId > 0 ? $preguntaId : '',
            'reporte_id'    => $reporteId > 0 ? $reporteId : '',
            'url_volver'    => $urlVolver,
        ]);
    }

    public function cambiarEstadoPregunta()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $preguntaId = (int) Request::post('id');
        $activa     = Request::post('activa') === '1' ? 1 : 0;

        $this->preguntas->cambiarActividad($preguntaId, $activa);

        if ($activa === 0) {
            $this->reportes->cerrarPendientesDePregunta($preguntaId, 'desactivada', $this->usuarioId());
        }

        $exito = $activa === 1 ? 'pregunta_activada' : 'pregunta_desactivada';
        Redirect::to('/administracion/show?seccion=preguntas&exito=' . $exito);
    }

    public function eliminarPregunta()
    {
        if (!$this->exigirEditor()) {
            return;
        }

        $preguntaId = (int) Request::post('id');

        $this->reportes->cerrarPendientesDePregunta($preguntaId, 'eliminada', $this->usuarioId());
        $resultado = $this->preguntas->eliminar($preguntaId);

        if (!$resultado['ok']) {
            Redirect::to('/administracion/show?seccion=preguntas&error=' . $this->codigoError($resultado));
            return;
        }

        Redirect::to('/administracion/show?seccion=preguntas&exito=pregunta_eliminada');
    }

    private function datosSugerencias()
    {
        $filtro = Request::get('estado', 'pendiente');
        if (!in_array($filtro, ['pendiente', 'aprobada', 'rechazada', 'todas'], true)) {
            $filtro = 'pendiente';
        }

        $lista = $this->sugerencias->listar($filtro);

        return [
            'filtro_pendiente'        => $filtro === 'pendiente',
            'filtro_aprobada'         => $filtro === 'aprobada',
            'filtro_rechazada'        => $filtro === 'rechazada',
            'filtro_todas'            => $filtro === 'todas',
            'sugerencias'             => array_map([$this, 'prepararSugerencia'], $lista),
            'hay_sugerencias'         => count($lista) > 0,
        ];
    }

    private function datosReportes()
    {
        $filtro = Request::get('estado', 'pendientes');
        if (!in_array($filtro, ['pendientes', 'resueltos', 'todos'], true)) {
            $filtro = 'pendientes';
        }

        $lista = $this->reportes->listar($filtro);

        return [
            'filtro_pendientes'  => $filtro === 'pendientes',
            'filtro_resueltos'   => $filtro === 'resueltos',
            'filtro_todos'       => $filtro === 'todos',
            'reportes'           => array_map([$this, 'prepararReporte'], $lista),
            'hay_reportes'       => count($lista) > 0,
        ];
    }

    private function datosPreguntas()
    {
        $lista = $this->preguntas->listarTodas();

        return [
            'preguntas'     => array_map([$this, 'prepararPregunta'], $lista),
            'hay_preguntas' => count($lista) > 0,
        ];
    }

    private function prepararSugerencia($sugerencia)
    {
        $estado = $sugerencia['estado'] ?? 'pendiente';

        $sugerencia['estado_pendiente'] = $estado === 'pendiente';
        $sugerencia['estado_aprobada']  = $estado === 'aprobada';
        $sugerencia['estado_rechazada'] = $estado === 'rechazada';
        $sugerencia['estado_texto']     = [
            'pendiente' => 'Pendiente',
            'aprobada'  => 'Aprobada',
            'rechazada' => 'Rechazada',
        ][$estado] ?? $estado;
        $sugerencia['fecha'] = $this->formatearFecha($sugerencia['creado_en'] ?? null);

        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $sugerencia['es_correcta_' . strtolower($letra)] =
                ($sugerencia['respuesta_correcta'] ?? '') === $letra;
        }

        $sugerencia['revisado_info'] = null;
        if (!$sugerencia['estado_pendiente']) {
            $sugerencia['revisado_info'] = 'Revisada por ' . ($sugerencia['revisor_nombre'] ?: 'un editor')
                . ' · ' . $this->formatearFecha($sugerencia['revisado_en'] ?? null);
        }

        return $sugerencia;
    }

    private function prepararReporte($reporte)
    {
        $estado = $reporte['estado'] ?? 'pendiente';

        $reporte['estado_pendiente'] = $estado === 'pendiente';
        $reporte['estado_texto']     = [
            'pendiente'    => 'Pendiente',
            'editada'      => 'Resuelto: pregunta editada',
            'desactivada'  => 'Resuelto: pregunta desactivada',
            'eliminada'    => 'Resuelto: pregunta eliminada',
            'cerrada'      => 'Cerrado sin cambios',
        ][$estado] ?? $estado;
        $reporte['motivo_texto']    = ReportesModel::motivoTexto($reporte['motivo'] ?? '');
        $reporte['fecha']           = $this->formatearFecha($reporte['creado_en'] ?? null);
        $reporte['tiene_pregunta']  = !empty($reporte['pregunta_id']);
        $reporte['resolucion_info'] = null;

        if (!$reporte['estado_pendiente']) {
            $reporte['resolucion_info'] = 'Resuelto por ' . ($reporte['revisor_nombre'] ?: 'un editor')
                . ' · ' . $this->formatearFecha($reporte['resuelto_en'] ?? null);
        }

        return $reporte;
    }

    private function prepararPregunta($pregunta)
    {
        $pregunta['es_activa']         = (int) ($pregunta['activa'] ?? 1) === 1;
        $pregunta['estado_texto']      = $pregunta['es_activa'] ? 'Activa' : 'Inactiva';
        $pregunta['origen_texto']      = ($pregunta['origen'] ?? 'directa') === 'sugerencia' ? 'Sugerencia' : 'Directa';
        $pregunta['creador']           = $pregunta['creador'] ?: '—';
        $pregunta['fecha']             = $this->formatearFecha($pregunta['creado_en'] ?? null);
        $pregunta['descripcion_corta'] = $this->recortar($pregunta['descripcion'] ?? '');

        return $pregunta;
    }

    private function renderizarFormulario($datos)
    {
        $pregunta = $datos['pregunta'] ?? [];

        $datos['pregunta'] = [
            'descripcion'        => $pregunta['descripcion'] ?? '',
            'opcion_a'           => $pregunta['opcion_a'] ?? '',
            'opcion_b'           => $pregunta['opcion_b'] ?? '',
            'opcion_c'           => $pregunta['opcion_c'] ?? '',
            'opcion_d'           => $pregunta['opcion_d'] ?? '',
            'respuesta_correcta' => $pregunta['respuesta_correcta'] ?? 'A',
            'categoria'          => $pregunta['categoria'] ?? 'Historia',
        ];

        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $datos['sel_' . strtolower($letra)] =
                $datos['pregunta']['respuesta_correcta'] === $letra;
        }

        $datos['categorias'] = [];
        foreach (self::CATEGORIAS as $categoria) {
            $datos['categorias'][] = [
                'nombre' => $categoria,
                'sel'    => $datos['pregunta']['categoria'] === $categoria,
            ];
        }

        $this->render->renderiza('preguntaFormView', $datos);
    }

    private function datosPreguntaPost()
    {
        return [
            'descripcion'        => Request::post('descripcion'),
            'opcion_a'           => Request::post('opcion_a'),
            'opcion_b'           => Request::post('opcion_b'),
            'opcion_c'           => Request::post('opcion_c'),
            'opcion_d'           => Request::post('opcion_d'),
            'respuesta_correcta' => Request::post('respuesta_correcta'),
            'categoria'          => Request::post('categoria'),
        ];
    }

    private function formatearFecha($fecha)
    {
        if (empty($fecha)) {
            return '—';
        }

        $timestamp = strtotime($fecha);

        return $timestamp ? date('d/m/Y H:i', $timestamp) : $fecha;
    }

    private function recortar($texto, $largo = 90)
    {
        $texto = (string) $texto;

        if (function_exists('mb_strlen')) {
            return mb_strlen($texto) > $largo ? mb_substr($texto, 0, $largo) . '…' : $texto;
        }

        return strlen($texto) > $largo ? substr($texto, 0, $largo) . '…' : $texto;
    }

    private function codigoError($resultado)
    {
        return $resultado['codigo'] ?? 'operacion';
    }

    private function mensajeExito($clave)
    {
        $mensajes = [
            'sugerencia_aprobada'  => 'La sugerencia fue aprobada y agregada al juego.',
            'sugerencia_rechazada' => 'La sugerencia fue rechazada.',
            'pregunta_creada'      => 'La pregunta fue creada correctamente.',
            'pregunta_editada'     => 'La pregunta fue actualizada.',
            'pregunta_activada'    => 'La pregunta vuelve a estar activa en el juego.',
            'pregunta_desactivada' => 'La pregunta fue desactivada; ya no aparecerá en el juego.',
            'pregunta_eliminada'   => 'La pregunta fue eliminada definitivamente.',
            'reporte_resuelto'     => 'El reporte fue resuelto.',
        ];

        return $mensajes[$clave] ?? null;
    }

    private function mensajeError($clave)
    {
        $mensajes = [
            'no_encontrado' => 'El elemento no existe o ya no está disponible.',
            'ya_revisado'   => 'El elemento ya fue revisado.',
            'motivo'        => 'Tenés que indicar el motivo del rechazo.',
            'accion'        => 'La acción solicitada no es válida.',
            'operacion'     => 'No se pudo completar la operación. Probá de nuevo.',
        ];

        return $mensajes[$clave] ?? null;
    }

    private function usuarioId()
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    private function exigirEditor()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/login/show');
            return false;
        }

        if (!in_array($_SESSION['rol'] ?? null, ['editor', 'administrador'], true)) {
            Redirect::to('/lobby/show');
            return false;
        }

        return true;
    }
}
