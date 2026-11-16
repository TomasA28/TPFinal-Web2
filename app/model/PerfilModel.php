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

    public function getPuntajeTotal($idUsuario)
    {
        $sql = "SELECT COALESCE(SUM(puntaje), 0) 
                AS puntajeTotal 
                FROM partida_jugador 
                WHERE usuario_id = :id";

        return $this->database->queryOne($sql, [
            "id" => $idUsuario
        ]);
    }

}