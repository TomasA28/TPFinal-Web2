<?php

namespace app;

use app\controller\HomeController;
use app\controller\LoginController;
use app\controller\JuegoController;
use app\model\LoginModel;
use app\model\JuegoModel;
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

    public function getLoginController()
    {
        return new LoginController(
            function () {
                return $this->getLoginModel();
            },
            $this->getRender()
        );
    }
    public function getJuegoController()
    {
        return new JuegoController(
            $this->getJuegoModel(),
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
    private function getJuegoModel()
    {
        return new JuegoModel($this->getDatabase());
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
