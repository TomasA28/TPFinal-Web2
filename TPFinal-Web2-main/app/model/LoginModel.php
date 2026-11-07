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

        $sql = "SELECT id, usuario, activo FROM usuarios WHERE usuario = ? AND password = ?";

        $resultado = $this->database->queryOne(
            $sql, 
            [$usuario, $passwordHash]
        );

        if(!$resultado) {
            return ["ok"=> false, "no_activado" => false];
        }

        if ($resultado['activo'] == 0) {
            return ["ok" => false, "no_activado" => true];
        }

        return ["ok" => true, "usuario" => $resultado];
    }
}