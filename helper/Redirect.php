<?php

class Redirect
{
    private static $base = '/TPFinal-Web2';

    public static function toIndex()
    {
        header('Location: ' . self::$base . '/');
        exit;
    }

    public static function to($ruta)
    {
        header('Location: ' . self::$base . $ruta);
        exit;
    }
}