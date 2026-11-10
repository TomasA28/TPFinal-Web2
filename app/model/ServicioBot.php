<?php

namespace app\model;

class ServicioBot
{
    public function elegirRespuesta($habilidad, $ratioPregunta, $letraCorrecta)
    {
        $probAcierto = $habilidad * (0.5 + $ratioPregunta / 2);
        $acierta = (mt_rand() / mt_getrandmax()) < $probAcierto;

        if ($acierta) {
            return $letraCorrecta;
        }
        $incorrectas = array_values(array_diff(['A', 'B', 'C', 'D'], [$letraCorrecta]));
        return $incorrectas[array_rand($incorrectas)];
    }
}