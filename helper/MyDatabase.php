<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function queryOne(string $sql, array $params = [])
    {
        Log::info($sql);
        $resultado = $this->conexion->prepare($sql);
        $resultado->execute($params);
        $fila = $resultado->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql, array $params = [])
    {
        Log::info($sql);

        if ($params === []) {
            $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
            return $resultado->fetchAll();
        }

        $resultado = $this->conexion->prepare($sql);
        $resultado->execute($params);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = [])
    {
        Log::info($sql);

        if ($params === []) {
            return $this->conexion->exec($sql);
        }

        $resultado = $this->conexion->prepare($sql);
        $resultado->execute($params);
        return $resultado->rowCount();
    }

    public function lastInsertId()
    {
        return $this->conexion->lastInsertId();
    }
}
