<?php

class Mailer
{
    public static function send($to, $subject, $html)
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Resto App <no-reply@localhost>\r\n";

        return mail($to, $subject, $html, $headers);
    }
}
