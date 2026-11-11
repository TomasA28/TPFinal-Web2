<?php

namespace app\model;

class HeaderModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getFotoDePerfil($idUsuario)
    {
        $sql = "SELECT fotoDePerfil FROM usuarios WHERE id = :id";
        $fila = $this->database->queryOne($sql, ["id" => $idUsuario]);
        return $fila ? $fila["fotoDePerfil"] : null;
    }
}