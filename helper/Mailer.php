<?php

require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();

        /*agregado temportal */
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Debugoutput = function ($str, $level) {
            error_log("SMTP DEBUG [$level]: $str");
};

        // Configuración SMTP
        $this->mailer->Host = 'sandbox.smtp.mailtrap.io';
        $this->mailer->SMTPAuth = true;

        $this->mailer->Username = '31084454d0dd21';
        $this->mailer->Password = 'a6a5370aabbc70';

        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 2525;

        // Configuración general
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom('no-reply@trivianight.com', 'Trivia Night');
    }

    public function enviar($destinatario, $asunto, $mensaje)
    {
        try {
            $this->mailer->clearAddresses();

            $this->mailer->addAddress($destinatario);
            $this->mailer->isHTML(true);

            $this->mailer->Subject = $asunto;
            $this->mailer->Body = $mensaje;

            $this->mailer->AltBody = strip_tags($mensaje);

            $this->mailer->send();

            return true;

        } catch (Exception $e) {
            error_log('Error enviando correo: ' . $e->getMessage());
            /*agregado temporal*/
            error_log('Error enviando correo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
