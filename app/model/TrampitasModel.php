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
        $r = $this->database->queryOne(
            "SELECT COALESCE(SUM(CASE tipo WHEN 'compra' THEN cantidad
                                           WHEN 'uso'    THEN -cantidad ELSE 0 END), 0) AS saldo
             FROM movimiento_trampitas WHERE usuario_id = ?", [$usuarioId]);
        return (int) $r['saldo'];
    }

    public function getHistorial($usuarioId)
    {
        return $this->database->query(
            "SELECT cantidad, ROUND(cantidad * precio_unitario, 2) AS monto_usd, estado,
                    DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha
             FROM compra_trampitas WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 20", [$usuarioId]);
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
        return $this->database->query(
            "SELECT u.id, u.usuario,
                    COALESCE(SUM(CASE WHEN m.tipo = 'compra' THEN m.cantidad END), 0) AS compradas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'uso'    THEN m.cantidad END), 0) AS usadas,
                    COALESCE(SUM(CASE m.tipo WHEN 'compra' THEN m.cantidad
                                             WHEN 'uso'    THEN -m.cantidad ELSE 0 END), 0) AS saldo
             FROM usuarios u
             JOIN movimiento_trampitas m ON m.usuario_id = u.id
             WHERE u.es_bot = 0
             GROUP BY u.id, u.usuario
             ORDER BY compradas DESC");
    }




    public function consumir($usuarioId, $partidaId = null, $preguntaId = null)
    {
        $this->database->begin();
        try {
            $this->database->queryOne("SELECT id FROM usuarios WHERE id = ? FOR UPDATE", [$usuarioId]);

            if ($this->getSaldo($usuarioId) < 1) { $this->database->rollback(); return false; }

            $this->database->execute(
                "INSERT INTO movimiento_trampitas (usuario_id, tipo, cantidad, partida_id, pregunta_id)
                 VALUES (?, 'uso', 1, ?, ?)", [$usuarioId, $partidaId, $preguntaId]);

            $this->database->commit();
            return true;
        } catch (\Throwable $e) {
            $this->database->rollback();
            return false;
        }
    }


    public function usar($usuarioId, $partidaId = null, $preguntaId = null)
    {
        return $this->consumir($usuarioId, $partidaId, $preguntaId);
    }

    public function devolver($usuarioId)
    {
        $this->database->execute(
            "DELETE FROM movimiento_trampitas
         WHERE usuario_id = ? AND tipo = 'uso'
         ORDER BY id DESC LIMIT 1", [(int)$usuarioId]);
    }


    public function crearCompra($usuarioId, $cantidad, $medioPago = 'simulado')
    {
        $cantidad = max(1, min(self::MAX_POR_COMPRA, (int) $cantidad));
        $this->database->execute(
            "INSERT INTO compra_trampitas (usuario_id, cantidad, precio_unitario, moneda, estado, medio_pago)
             VALUES (?, ?, ?, 'USD', 'pendiente', ?)",
            [$usuarioId, $cantidad, self::PRECIO_USD, $medioPago]);
        return $this->database->lastId();
    }


    public function getCompra($compraId, $usuarioId = null)
    {
        $sql = "SELECT *, ROUND(cantidad * precio_unitario, 2) AS monto_usd FROM compra_trampitas WHERE id = ?";
        $p = [$compraId];
        if ($usuarioId !== null) { $sql .= " AND usuario_id = ?"; $p[] = $usuarioId; }
        return $this->database->queryOne($sql, $p);
    }



    public function acreditar($compraId, $referenciaPago = null)
    {
        $this->database->begin();
        try {
            $this->database->execute(
                "UPDATE compra_trampitas SET estado = 'confirmada', referencia_pago = ?
                 WHERE id = ? AND estado = 'pendiente'", [$referenciaPago, $compraId]);

            if ($this->database->affectedRows() !== 1) { $this->database->rollback(); return false; }

            $c = $this->getCompra($compraId);
            $this->database->execute(
                "INSERT INTO movimiento_trampitas (usuario_id, tipo, cantidad) VALUES (?, 'compra', ?)",
                [$c['usuario_id'], $c['cantidad']]);

            $this->database->commit();
            return true;
        } catch (\Throwable $e) {
            $this->database->rollback();
            return false;
        }
    }

    public function marcarRechazada($compraId, $usuarioId)
    {
        $this->database->execute(
            "UPDATE compra_trampitas SET estado = 'rechazada'
             WHERE id = ? AND usuario_id = ? AND estado = 'pendiente'", [$compraId, $usuarioId]);
    }


    private function desde($periodo)
    {
        $map = ['dia' => '1 DAY', 'semana' => '7 DAY', 'mes' => '1 MONTH', 'anio' => '1 YEAR'];
        return "DATE_SUB(NOW(), INTERVAL " . ($map[$periodo] ?? $map['mes']) . ")";   // lista blanca
    }


    public function getGanancias($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->queryOne(
            "SELECT
               COALESCE(SUM(CASE WHEN medio_pago <> 'simulado' THEN cantidad * precio_unitario END), 0) AS real_usd,
               COALESCE(SUM(CASE WHEN medio_pago =  'simulado' THEN cantidad * precio_unitario END), 0) AS simulado_usd,
               COALESCE(SUM(cantidad), 0) AS vendidas
             FROM compra_trampitas WHERE estado = 'confirmada' AND fecha >= $d");
    }

    public function getGananciasPorDia($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->query(
            "SELECT DATE(fecha) AS dia, SUM(cantidad * precio_unitario) AS total
             FROM compra_trampitas WHERE estado = 'confirmada' AND fecha >= $d
             GROUP BY DATE(fecha) ORDER BY dia");
    }

    public function getMovimientos($usuarioId)
    {
        $filas = $this->database->query(
            "SELECT tipo, cantidad, fecha
         FROM movimiento_trampitas
         WHERE usuario_id = ?
         ORDER BY fecha DESC, id DESC
         LIMIT 20", [$usuarioId]);

        foreach ($filas as &$f) {
            $esIngreso = $f['tipo'] === 'compra';
            $f['es_ingreso']          = $esIngreso;
            $f['tipo']                = $esIngreso ? 'Compra' : 'Uso en partida';
            $f['cantidad_formateada'] = ($esIngreso ? '+' : '-') . $f['cantidad'];
            $f['fecha_formateada']    = date('d/m/Y H:i', strtotime($f['fecha']));
        }
        unset($f);

        return $filas;
    }




}