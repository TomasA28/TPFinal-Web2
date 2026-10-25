<?php

namespace app\model;

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuarioPorId($id)
    {
        $id = (int) $id;
        return $this->database->queryOne(
            "SELECT id, nombre, puntaje FROM usuario WHERE id = $id"
        );
    }
}