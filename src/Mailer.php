<?php

class Mailer
{
    public static function send($to, $subject, $html)
    {
        $config = require __DIR__ . '/../config/mail.php';

        $host = 'live.smtp.mailtrap.io';
        $port = 587;
        $user = 'api';
        $pass = $config['api_token']; // IMPORTANT

        $from = $config['from_email'];
        $fromName = $config['from_name'];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return false;
        }

        fgets($socket); // banner

        fputs($socket, "EHLO localhost\r\n");
        fgets($socket);

        // STARTTLS obligatoire
        fputs($socket, "STARTTLS\r\n");
        fgets($socket);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return false;
        }

        // Re-EHLO après TLS
        fputs($socket, "EHLO localhost\r\n");
        fgets($socket);

        // AUTH LOGIN (api + token)
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket);

        fputs($socket, base64_encode($user) . "\r\n");
        fgets($socket);

        fputs($socket, base64_encode($pass) . "\r\n");
        fgets($socket);

        // MAIL FROM
        fputs($socket, "MAIL FROM:<$from>\r\n");
        fgets($socket);

        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        fgets($socket);

        // DATA
        fputs($socket, "DATA\r\n");
        fgets($socket);

        $msg =
            "From: $fromName <$from>\r\n" .
            "To: <$to>\r\n" .
            "Subject: $subject\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n\r\n" .
            $html .
            "\r\n.\r\n";

        fputs($socket, $msg);
        fgets($socket);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }
}
