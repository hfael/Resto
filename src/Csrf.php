<?php

class Csrf
{
    public static function token()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function isValid($token)
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if ($sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function checkOrAbort()
    {
        if (!self::isValid($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            exit('Token CSRF invalide.');
        }
    }
}
