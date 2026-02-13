<?php
namespace API\Middleware;

require_once __DIR__ . '/../Helpers/JwtHelper.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\JwtHelper;
use API\Helpers\Response;

class JwtMiddleware {

    private static function getAuthHeader(): ?string {
        // 1) getallheaders (Apache mod_php)
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    return trim($v);
                }
            }
        }

        // 2) PHP-FPM / FastCGI
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        // 3) Apache rewrite fallback
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        // 4) Nginx fastcgi param fallback
        if (!empty($_SERVER['Authorization'])) {
            return trim($_SERVER['Authorization']);
        }

        return null;
    }

    public static function protect() {

        $authHeader = self::getAuthHeader();

        if (!$authHeader) {
            Response::json(["error" => "missing_token"], 401);
        }

        if (!preg_match('/^Bearer\s(\S+)$/', $authHeader, $matches)) {
            Response::json(["error" => "invalid_format"], 401);
        }

        $jwt = $matches[1];
        $payload = JwtHelper::verify($jwt);

        if (!$payload) {
            Response::json(["error" => "invalid_token"], 401);
        }

        return $payload;
    }
}
