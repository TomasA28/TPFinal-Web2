<?php

namespace app\model;

class TrampitasModel
{
    private $database;

    private const PRECIO_UNITARIO  = 1.00;
    private const PAQUETES_VALIDOS = [1, 5, 10];
    private const LIMITE_HISTORIAL = 50;


    private const SQL_SALDO =
        "(COALESCE((SELECT SUM(cantidad) FROM compra_trampitas
                    WHERE usuario_id = ? AND estado = 'confirmada'), 0)
        + COALESCE((SELECT SUM(cantidad) FROM movimiento_trampitas
                    WHERE usuario_id = ?), 0))";

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getSaldo($usuarioId)
    {
        $id = (int)$usuarioId;
        $r = $this->database->queryOne("SELECT " . self::SQL_SALDO . " AS saldo", [$id, $id]);
        return (int)($r['saldo'] ?? 0);
    }

    public function getHistorial($usuarioId)
    {
        $id = (int)$usuarioId;
        $limite = (int)self::LIMITE_HISTORIAL;

        $filas = $this->database->queryAll(
            "SELECT fecha, 'Compra' AS tipo, cantidad
             FROM compra_trampitas WHERE usuario_id = ? AND estado = 'confirmada'
             UNION ALL
             SELECT fecha, tipo, cantidad
             FROM movimiento_trampitas WHERE usuario_id = ?
             ORDER BY fecha DESC
             LIMIT $limite",
            [$id, $id]
        );


        foreach ($filas as &$f) {
            $f['fecha_formateada']    = date('d/m/Y H:i', strtotime($f['fecha']));
            $f['es_ingreso']          = (int)$f['cantidad'] > 0;
            $f['cantidad_formateada'] = ((int)$f['cantidad'] > 0 ? '+' : '') . (int)$f['cantidad'];
        }
        return $filas;
    }


    public function comprar($usuarioId, $cantidad = 1)
    {
        $cantidad = (int)$cantidad;
        if (!in_array($cantidad, self::PAQUETES_VALIDOS, true)) {
            return false;   // el cliente no decide qué se puede comprar
        }

        $this->database->execute(
            "INSERT INTO compra_trampitas (usuario_id, cantidad, precio_unitario, estado)
             VALUES (?, ?, ?, 'confirmada')",
            [(int)$usuarioId, $cantidad, self::PRECIO_UNITARIO]
        );
        return true;
    }


    public function usar($usuarioId)
    {
        $id = (int)$usuarioId;

        $filas = $this->database->execute(
            "INSERT INTO movimiento_trampitas (usuario_id, tipo, cantidad)
             SELECT ?, 'Uso', -1 FROM DUAL
             WHERE " . self::SQL_SALDO . " >= 1",
            [$id, $id, $id]
        );
        return $filas > 0;
    }



    public function getRecaudacionTotal()
    {
        $r = $this->database->queryOne(
            "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) AS total
             FROM compra_trampitas WHERE estado = 'confirmada'"
        );
        return (float)$r['total'];
    }


    public function getBalancePorUsuario()
    {
        $filas = $this->database->queryAll(
            "SELECT u.id, u.usuario,
                COALESCE((SELECT SUM(c.cantidad) FROM compra_trampitas c
                          WHERE c.usuario_id = u.id AND c.estado = 'confirmada'), 0) AS compradas,
                COALESCE((SELECT -SUM(m.cantidad) FROM movimiento_trampitas m
                          WHERE m.usuario_id = u.id), 0) AS usadas
             FROM usuarios u
             WHERE u.es_bot = 0
             ORDER BY compradas DESC"
        );
        foreach ($filas as &$f) {
            $f['saldo'] = (int)$f['compradas'] - (int)$f['usadas'];
        }
        return $filas;
    }
}