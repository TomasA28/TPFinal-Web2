<?php

namespace app;

use app\controller\HomeController;
use app\controller\RegistroController;
use app\controller\LoginController;
use app\model\RegistroModel;
use app\model\LoginModel;
use MustacheRender;
use MyDatabase;
use Router;

require_once("helper/Autoloader.php");



class Configuration
{

    public function __construct()
    {
    }

    public function getHomeController()
    {
        return new HomeController(
            $this->getRender()
        );
    }

    public function getRegistroController()
    {
        return new RegistroController(
            $this->getRegistroModel(),
            $this->getRender()
        );
    }

    public function getLoginController()
    {
        return new LoginController(
            function () {
                return $this->getLoginModel();
            },
            $this->getRender()
        );
    }

    private function getDatabase()
    {
        $config = parse_ini_file("config/config.ini");

        return new MyDatabase(
            $config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
        );
    }
    private function getRender()
    {
        return new MustacheRender();
    }

    private function getRegistroModel()
    {
        return new RegistroModel(
            $this->getDatabase()
        );
    }

    private function getLoginModel()
    {
        return new LoginModel(
            $this->getDatabase()
        );
    }

    public function getRouter()
    {
        return new Router($this, "home", "show");
    }
}
