<?php

class Mailer
{
    public static function send($to, $subject, $content)
    {
        $config = require __DIR__ . '/../config/mail.php';

        $headers = [
            "From: {$config['from_name']} <{$config['from_email']}>",
            "Reply-To: {$config['from_email']}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8"
        ];

        $smtp = [
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username'],
            'password' => $config['password']
        ];

        $socket = fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 30);
        if (!$socket) return false;

        fgets($socket);
        fputs($socket, "EHLO localhost\r\n"); fgets($socket);
        fputs($socket, "AUTH LOGIN\r\n"); fgets($socket);
        fputs($socket, base64_encode($smtp['username'])."\r\n"); fgets($socket);
        fputs($socket, base64_encode($smtp['password'])."\r\n"); fgets($socket);
        fputs($socket, "MAIL FROM:<{$config['from_email']}>\r\n"); fgets($socket);
        fputs($socket, "RCPT TO:<$to>\r\n"); fgets($socket);
        fputs($socket, "DATA\r\n"); fgets($socket);

        $msg =
            "Subject: $subject\r\n".
            implode("\r\n", $headers).
            "\r\n\r\n".
            $content.
            "\r\n.\r\n";

        fputs($socket, $msg);
        fgets($socket);

        fputs($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    }
}
