<?php

class JwtHelper
{
    private static $key = 'RESTO_SECRET_KEY_2025';

    public static function generate($payload)
    {
        $header = base64_encode(json_encode(['alg'=>'HS256','typ'=>'JWT']));
        $body = base64_encode(json_encode($payload));
        $sig = hash_hmac('sha256', "$header.$body", self::$key, true);
        $sig = base64_encode($sig);
        return "$header.$body.$sig";
    }

    public static function verify($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;

        $header = $parts[0];
        $body = $parts[1];
        $sig = $parts[2];

        $check = base64_encode(hash_hmac('sha256', "$header.$body", self::$key, true));

        if (!hash_equals($check, $sig)) return false;

        return json_decode(base64_decode($body), true);
    }
}
