<?php

require_once __DIR__ . "/../vendor/mustache/Mustache/Autoloader.php";
Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $stringEngine;
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . "/app/view";
        $this->mustache = new Mustache_Engine([
            "loader" => new Mustache_Loader_FilesystemLoader($this->viewPath, ["extension" => ".mustache"])
        ]);
        $this->stringEngine = new Mustache_Engine;
    }

    public function renderiza($view, $data = [])
    {
        $page = file_get_contents ($this->viewPath . "/header.mustache");
        $page .= file_get_contents ($this->viewPath . "/" . $view . ".mustache");
        $page .= file_get_contents ($this->viewPath . "/footer.mustache");
        echo $this->stringEngine->render($page, $data);
    }

    public function renderPorNombre($view, $data = [])
    {
        echo $this->mustache->render($view, $data);
    }
}
