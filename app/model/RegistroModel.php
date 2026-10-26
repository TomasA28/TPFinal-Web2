<?php

namespace app\model;
class RegistroModel
{
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function registrar($usuario, $mail, $password, $passwordCheck, $fechaNac) {
        //POST y Sanitización de datos
        $usuario   = trim(htmlspecialchars($usuario ?? "", ENT_QUOTES, 'UTF-8'));
        $mail      = trim(htmlspecialchars($mail ?? "", ENT_QUOTES, 'UTF-8'));
        $password  = trim(htmlspecialchars($password ?? "", ENT_QUOTES, 'UTF-8'));
        $passwordCheck = trim(htmlspecialchars($passwordCheck ?? "", ENT_QUOTES, 'UTF-8'));
        $fechaNac  = trim($fechaNac ?? "");

        // Validaciones
        if ($usuario === "") {
            return ["ok" => false, "error" => "Falta el nombre de usuario."];
        }

        $existeUsuario = $this->database->queryOne("SELECT id FROM usuarios WHERE usuario = '$usuario'");
        if ($existeUsuario) {
            return ["ok" => false, "error" => "El nombre de usuario ya existe."];
        }

        if ($mail === "") {
            return ["ok" => false, "error" => "Falta el correo electrónico."];
        }

        if ($password === "") {
            return ["ok" => false, "error" => "Falta la contraseña."];
        }

        if ($passwordCheck === "") {
            return ["ok" => false, "error" => "Falta confirmar la contraseña."];
        }

        if ($password !== $passwordCheck) {
            return ["ok" => false, "error" => "Las contraseñas no coinciden."];
        }

        if ($fechaNac === "") {
            return ["ok" => false, "error" => "La fecha de nacimiento no es válida."];
        }

        $mayorDeEdad = date("Y-m-d", strtotime("-15 years"));
        if ($fechaNac > $mayorDeEdad) {
            return ["ok" => false, "error" => "Debe tener 15 años o más para crear una cuenta."];
        }

        $passwordHash = md5($password);

        //IMPORTANTE: x ahora solo guarda userNAme y passW. después modifico la Db y agrego lo demás
        $this->database->execute(
            "INSERT INTO usuarios (usuario, password, mail, fechaNacimiento) VALUES ('$usuario', '$passwordHash', '$mail', '$fechaNac')"
        );

        return ["ok" => true];
    }
}