<?php

namespace app\model;
class RegistroModel
{
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function registrar($nombre, $apellido, $fechaNac, $sexo, $lat, $lng, 
        $mail, $password, $passwordCheck, $usuario, $fotoDePerfil) {

        //POST y Sanitización de datos
        $nombre    = trim(htmlspecialchars($nombre ?? "", ENT_QUOTES, 'UTF-8'));
        $apellido  = trim(htmlspecialchars($apellido ?? "", ENT_QUOTES, 'UTF-8'));
        $fechaNac  = trim($fechaNac ?? "");
        $sexo      = trim(htmlspecialchars($sexo ?? "", ENT_QUOTES, 'UTF-8'));
        $lat       = trim($lat ?? "");
        $lng       = trim($lng ?? "");
        $mail      = trim(htmlspecialchars($mail ?? "", ENT_QUOTES, 'UTF-8'));
        $password  = trim(htmlspecialchars($password ?? "", ENT_QUOTES, 'UTF-8'));
        $passwordCheck = trim(htmlspecialchars($passwordCheck ?? "", ENT_QUOTES, 'UTF-8'));
        $usuario   = trim(htmlspecialchars($usuario ?? "", ENT_QUOTES, 'UTF-8'));

        // Validaciones
        if ($nombre === "") {
            return ["ok" => false, "error" => "Falta ingresar nombre(s)"];
        }

        if ($apellido === "") {
            return ["ok" => false, "error" => "Falta ingresar apellido(s)"];
        }
        
        if ($fechaNac === "") {
            return ["ok" => false, "error" => "La fecha de nacimiento no es válida."];
        }

        $mayorDeEdad = date("Y-m-d", strtotime("-15 years"));
        if ($fechaNac > $mayorDeEdad) {
            return ["ok" => false, "error" => "Debe tener 15 años o más para crear una cuenta."];
        }

        $opcionesValidas = ['1', '2', '3'];
        $sexo = in_array($sexo ?? '', $opcionesValidas) ? $sexo : '3';

        if ($lat === "" || $lng === "") {
            return ["ok" => false, "error" => "Debés seleccionar las coordenadas en el mapa."];
        }

        if (is_numeric($lat) && is_numeric($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return ["ok" => false, "error" => "Las coordenadas seleccionadas no son válidas."];
            }
        } else {
            return ["ok" => false, "error" => "Las coordenadas seleccionadas no son válidas."];
        }

        if ($mail === "") {
            return ["ok" => false, "error" => "Falta el correo electrónico."];
        }

        $existeMail = $this->database->queryOne("SELECT mail FROM usuarios WHERE mail = ?", [$mail]);
        if ($existeMail) {
            return ["ok" => false, "error" => "El mail ya está en uso."];
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

        if ($usuario === "") {
            return ["ok" => false, "error" => "Falta el nombre de usuario."];
        }

        $existeUsuario = $this->database->queryOne("SELECT id FROM usuarios WHERE usuario = ?", [$usuario]);
        if ($existeUsuario) {
            return ["ok" => false, "error" => "El nombre de usuario ya existe."];
        }

        if (!$fotoDePerfil || $fotoDePerfil['error'] === UPLOAD_ERR_NO_FILE) {
            return ["ok" => false, "error" => "Falta seleccionar una foto de perfil."];
        }

        if ($fotoDePerfil['error'] !== UPLOAD_ERR_OK) {
            return ["ok" => false, "error" => "Error en la subida imagen."];
        }
        
        $extensionesValidas = array("jpg", "jpeg", "png", "gif", "webp");
        $extension = strtolower(pathinfo($fotoDePerfil['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesValidas)) {
            return ["ok" => false, "error" => "No se permite subir el tipo de imagen elegido, solo se permiten archivos JPG, JPEG , PNG, GIF o webp"];
        }
        
        $tamanioMaximo = 2 * 1024 * 1024;
        
        if ($fotoDePerfil['size'] > $tamanioMaximo) {
            return ["ok" => false, "error" => "La imagen es demasiado grande"];
        }
            
        $checkImagen = getimagesize($fotoDePerfil['tmp_name']);
        if ($checkImagen === false) {
            return ["ok" => false, "error" => "El archivo no es una imagen válida"];
        }

        $nombreUnico = uniqid('img') . '.' . $extension;
        $carpetaDestino = 'public/subidas/';

        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0777, true);
        }

        $rutaDestino = $carpetaDestino . $nombreUnico;
        $exito = move_uploaded_file($fotoDePerfil['tmp_name'], $rutaDestino);

        if ($exito == false) {
            return ["ok" => false, "error" => "Error al subir la imagen."];
        }

        $passwordHash = md5($password);
        $token = bin2hex(random_bytes(32));

        $this->database->execute(
            "INSERT INTO usuarios (nombre, apellido, fechaNacimiento, sexo, lat, lng, mail, 
            password, usuario, fotoDePerfil, token, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $nombre,
                $apellido,
                $fechaNac,
                $sexo,
                $lat,
                $lng,
                $mail,
                $passwordHash,
                $usuario,
                $nombreUnico,
                $token,
                0
            ]
        );


        return ["ok" => true, 'token' => $token, "mail" => $mail];
    }

    public function buscarPorToken($token)
    {
        return $this->database->queryOne("SELECT id FROM usuarios WHERE token = ?", [$token]);
    }

    public function activarUsuario($id)
    {
        return $this->database->execute("UPDATE usuarios SET token = NULL, activo = 1 WHERE id = ?", [$id]);
    }
}