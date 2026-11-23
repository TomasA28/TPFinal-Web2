<?php

require_once __DIR__ . "/../vendor/mustache/Mustache/Autoloader.php";
Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $stringEngine;
    private $viewPath;
    private $headerModel;

    public function __construct($headerModel = null)
    {
        $this->viewPath = dirname(__DIR__) . "/app/view";
        $this->headerModel = $headerModel;
        $this->mustache = new Mustache_Engine([
            "loader" => new Mustache_Loader_FilesystemLoader($this->viewPath, ["extension" => ".mustache"])
        ]);
        $this->stringEngine = new Mustache_Engine;
    }

    public function renderiza($view, $data = [])
    {
        $data = $this->agregarDatosHeader($data);
        $page = file_get_contents ($this->viewPath . "/header.mustache");
        $page .= file_get_contents ($this->viewPath . "/" . $view . ".mustache");
        $page .= file_get_contents ($this->viewPath . "/footer.mustache");
        echo $this->stringEngine->render($page, $data);
    }

    private function agregarDatosHeader($data)
    {
        $rol = $_SESSION['rol'] ?? null;

        $data['logueado'] = isset($_SESSION['usuario_id']);
        $data['rol'] = $rol;
        $data['esJugador'] = $rol === 'jugador';
        $data['esEditor'] = in_array($rol, ['editor', 'administrador'], true);
        $data['esAdmin'] = $rol === 'administrador';

        if (!$data['logueado']) {
            return $data;
        }

        if (!isset($data['fotoDePerfil'])) {
            $data['fotoDePerfil'] = $this->headerModel
            ? $this->headerModel->getFotoDePerfil((int) $_SESSION['usuario_id'])
            : null;
        }
        
        return $data;
    }

    public function renderPorNombre($view, $data = [])
    {
        echo $this->mustache->render($view, $data);
    }
}
