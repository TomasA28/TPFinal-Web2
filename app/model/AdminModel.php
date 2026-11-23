<?php

namespace app\model;

class AdminModel
{
    private $database;

    private const PERIODOS = [
        'dia'    => '1 DAY',
        'semana' => '7 DAY',
        'mes'    => '1 MONTH',
        'anio'   => '1 YEAR',
    ];

    private const SEXOS = [1 => 'Masculino', 2 => 'Femenino', 3 => 'Prefiero no cargarlo'];

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function periodoValido($periodo)
    {
        return isset(self::PERIODOS[$periodo]) ? $periodo : 'mes';
    }

    // Solo sale de la lista blanca de arriba, nunca del usuario
    private function desde($periodo)
    {
        return "DATE_SUB(NOW(), INTERVAL " . self::PERIODOS[$this->periodoValido($periodo)] . ")";
    }

    public function getResumen($periodo)
    {
        $d = $this->desde($periodo);

        $uno = function ($sql) {
            $r = $this->database->queryOne($sql);
            return (int)($r['n'] ?? 0);
        };

        return [
            'jugadores'         => $uno("SELECT COUNT(*) AS n FROM usuarios WHERE es_bot = 0 AND rol = 'jugador'"),
            'partidas'          => $uno("SELECT COUNT(*) AS n FROM partida WHERE fecha >= $d"),
            'preguntas'         => $uno("SELECT COUNT(*) AS n FROM preguntas"),
            'preguntas_creadas' => $uno("SELECT COUNT(*) AS n FROM preguntas WHERE fecha_creacion >= $d"),
            'usuarios_nuevos'   => $uno("SELECT COUNT(*) AS n FROM usuarios WHERE es_bot = 0 AND fecha_registro >= $d"),
        ];
    }

    public function getAciertoPorUsuario($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->queryAll(
            "SELECT u.usuario,
                    COUNT(*) AS respondidas,
                    SUM(r.es_correcta) AS correctas,
                    ROUND(100 * SUM(r.es_correcta) / COUNT(*), 1) AS porcentaje
             FROM respuestas r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE u.es_bot = 0 AND r.fecha >= $d
             GROUP BY u.id, u.usuario
             ORDER BY porcentaje DESC, respondidas DESC"
        );
    }

    public function getUsuariosPorPais($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->queryAll(
            "SELECT COALESCE(NULLIF(pais, ''), 'Sin dato') AS etiqueta, COUNT(*) AS total
             FROM usuarios
             WHERE es_bot = 0 AND fecha_registro >= $d
             GROUP BY etiqueta
             ORDER BY total DESC"
        );
    }

    public function getUsuariosPorSexo($periodo)
    {
        $d = $this->desde($periodo);
        $filas = $this->database->queryAll(
            "SELECT sexo, COUNT(*) AS total
             FROM usuarios
             WHERE es_bot = 0 AND fecha_registro >= $d
             GROUP BY sexo"
        );
        foreach ($filas as &$f) {
            $f['etiqueta'] = self::SEXOS[(int)$f['sexo']] ?? 'Prefiero no cargarlo';
        }
        unset($f);
        return $filas;
    }

    public function getUsuariosPorEdad($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->queryAll(
            "SELECT CASE
                        WHEN TIMESTAMPDIFF(YEAR, fechaNacimiento, CURDATE()) < 18  THEN 'Menores'
                        WHEN TIMESTAMPDIFF(YEAR, fechaNacimiento, CURDATE()) >= 65 THEN 'Jubilados'
                        ELSE 'Medio'
                    END AS etiqueta,
                    COUNT(*) AS total
             FROM usuarios
             WHERE es_bot = 0 AND fecha_registro >= $d
             GROUP BY etiqueta
             ORDER BY total DESC"
        );
    }

    public function getBalanceTrampitas()
    {
        return $this->database->queryAll(
            "SELECT u.id, u.usuario,
                    COALESCE(c.compradas, 0) AS compradas,
                    COALESCE(m.usadas, 0)    AS usadas,
                    COALESCE(c.compradas, 0) - COALESCE(m.usadas, 0) AS saldo
             FROM usuarios u
             LEFT JOIN (SELECT usuario_id, SUM(cantidad) AS compradas
                        FROM compra_trampitas WHERE estado = 'confirmada'
                        GROUP BY usuario_id) c ON c.usuario_id = u.id
             LEFT JOIN (SELECT usuario_id, SUM(cantidad) AS usadas
                        FROM movimiento_trampitas WHERE tipo = 'uso'
                        GROUP BY usuario_id) m ON m.usuario_id = u.id
             WHERE u.es_bot = 0 AND (c.compradas IS NOT NULL OR m.usadas IS NOT NULL)
             ORDER BY compradas DESC"
        );
    }


    public function getGananciasTrampitas($periodo)
    {
        $d = $this->desde($periodo);
        return $this->database->queryOne(
            "SELECT
               COALESCE(SUM(cantidad * precio_unitario), 0) AS total_usd,
               COALESCE(SUM(CASE WHEN medio_pago <> 'simulado' THEN cantidad * precio_unitario END), 0) AS real_usd,
               COALESCE(SUM(CASE WHEN medio_pago =  'simulado' THEN cantidad * precio_unitario END), 0) AS simulado_usd,
               COALESCE(SUM(cantidad), 0) AS vendidas,
               COUNT(*) AS compras
             FROM compra_trampitas
             WHERE estado = 'confirmada' AND fecha >= $d"
        );
    }
}