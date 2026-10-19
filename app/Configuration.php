<?php

namespace app;

use app\controller\HomeController;
use app\controller\LoginController;
use app\model\LoginModel;
use app\controller\LobbyController;
use app\model\LobbyModel;
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
            $this->getLoginModel()
        );
    }

    private function getDatabase()
    {
        $config = parse_ini_file("config/config.ini");

        return new MyDatabase($config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
        );
    }

    private function getRender()
    {
        return new MustacheRender();
    }

    private function getLoginModel()
    {
        return new LoginModel(
            $this->getDatabase()
        );
    }

    public function getLobbyController()
    {
        return new LobbyController(
            $this->getLobbyModel(),
            $this->getRender()
        );
    }

    private function getLobbyModel()
    {
        return new LobbyModel(
            $this->getDatabase()
        );
    }

    public function getRouter()
    {
        return new Router($this, "home", "show");
    }
}
