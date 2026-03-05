<?php

class Mailer
{
    public static function send($to, $subject, $html)
    {
        $from = getenv('MAIL_FROM') ?: 'no-reply@resto.local';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Resto App';

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                if (!\PHPMailer\PHPMailer\PHPMailer::validateAddress($from)) {
                    $from = 'no-reply@resto.local';
                }
                $mail->isSMTP();
                $mail->Host = getenv('MAIL_HOST') ?: 'mailhog';
                $mail->Port = (int)(getenv('MAIL_PORT') ?: 1025);
                $mail->SMTPAuth = false;
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = '';
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($from, $fromName);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $html;

                return $mail->send();
            } catch (\Throwable $e) {
                return false;
            }
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$from}>\r\n";

        return mail($to, $subject, $html, $headers);
    }
}
