<?php
namespace API\Helpers;

class JwtHelper {

    private static $key = "RESTO_API_SECRET_KEY_2025_981273";

    public static function generate($payload) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $h = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');

        $payload['exp'] = time() + 3600;
        $p = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', "$h.$p", self::$key, true);
        $s = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$h.$p.$s";
    }

    public static function verify($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;

        list($h, $p, $s) = $parts;

        $expected = rtrim(strtr(
            base64_encode(
                hash_hmac('sha256', "$h.$p", self::$key, true)
            ), '+/', '-_'), '=');

        if (!hash_equals($expected, $s)) return false;

        $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);

        if (!isset($payload['exp']) || $payload['exp'] < time()) return false;

        return $payload;
    }
}
