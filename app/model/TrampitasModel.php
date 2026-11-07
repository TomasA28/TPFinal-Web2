<?php

namespace app\model;

class TrampitasModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    //Se obtine el saldo
    //se recorre cada una de las compras trampitas
    //se obtiene la suma de la cantidad
    //si el usuario_id coincide y su estado es confirmada se devuelve
    public function getSaldo($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        $compras = $this->database->queryOne(
            "SELECT SUM(cantidad) AS total
         FROM compra_trampitas
         WHERE usuario_id = ? AND estado = 'confirmada'",
            [$usuarioId]
        );

        //se recorre movimientos trampitas
        //se obtiene la suma de la cantidad
        //si el usuario_id coincide con el usuario_id a buscar
        $movimientos = $this->database->queryOne(
            "SELECT SUM(cantidad) AS total
         FROM movimiento_trampitas
         WHERE usuario_id = ?",
            [$usuarioId]
        );

        //se obtiene el total de compras
        //se obitene total de movimientos
        $totalComprado = (int) ($compras['total'] ?? 0);
        $totalMovimientos = (int) ($movimientos['total'] ?? 0);

        //se retorna la suma de ambos
        return $totalComprado + $totalMovimientos;
    }




    public function getHistorial($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        //se muestra la fecha , el tipo de compra y la cantidad
        //se recorre compra_trampitas
        //si el usuario id coincide y su estado es confirmada
        $compras = $this->database->queryAll(
            "SELECT fecha, 'Compra' AS tipo, cantidad
         FROM compra_trampitas
         WHERE usuario_id = ? AND estado = 'confirmada'",
            [$usuarioId]
        );

        //se retorna la fecha, el tipo y la cantidad
        //se recorre movimientos_trampitas si el usuario_id coincide con el criterio
        $movimientos = $this->database->queryAll(
            "SELECT fecha, tipo, cantidad
         FROM movimiento_trampitas
         WHERE usuario_id = ?",
            [$usuarioId]
        );


        //combinamos dos arrays en uno solo
        $historial = array_merge($compras, $movimientos);

        //ordenamos el historial de manera descendente
        usort($historial, function ($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        return $historial;
    }


    public function comprarUnaTrampita($usuarioId)
    {
        //se insertan los datos de compra
        $this->database->execute(
            "INSERT INTO compra_trampitas
            (usuario_id, cantidad, precio_unitario, estado)
         VALUES (?, 1, 1.00, 'confirmada')",
            [(int) $usuarioId]
        );
    }


//se insertan los datos para usar una trampita
    public function usarUnaTrampita($usuarioId)
    {
        $usuarioId = (int) $usuarioId;

        if ($this->getSaldo($usuarioId) <= 0) {
            return false;
        }

        $this->database->execute(
            "INSERT INTO movimiento_trampitas
            (usuario_id, tipo, cantidad)
         VALUES (?, 'Uso', -1)",
            [$usuarioId]
        );

        return true;
    }
}