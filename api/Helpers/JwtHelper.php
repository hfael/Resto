<?php
namespace API\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {

    private static $key = "RESTO_API_SECRET_KEY_2025_981273";

    public static function generate($payload) {
        $payload['exp'] = time() + 3600;
        return JWT::encode($payload, self::$key, 'HS256');
    }

    public static function verify($jwt) {
        try {
            $decoded = JWT::decode($jwt, new Key(self::$key, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }
}