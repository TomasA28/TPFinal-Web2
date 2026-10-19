<?php

namespace app\model;
class LoginModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function autenticar($usuario, $password)
    {
        $passwordHash = md5($password);
        $sql = "SELECT id, usuario FROM usuarios WHERE usuario = ? AND password = ?";

        return $this->database->queryOne($sql, [$usuario, $passwordHash]);
    }
}
