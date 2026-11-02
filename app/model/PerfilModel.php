<?php

namespace app\model;

class PerfilModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuario($idUsuario)
    {
        $sql = "SELECT
                    id,
                    nombre,
                    apellido,
                    fechaNacimiento,
                    sexo,
                    lat,
                    lng,
                    mail,
                    usuario,
                    fotoDePerfil
                FROM usuarios
                WHERE id = :id";

        return $this->database->queryOne($sql, [
            "id" => $idUsuario
        ]);
    }
}