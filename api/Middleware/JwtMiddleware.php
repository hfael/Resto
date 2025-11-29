<?php
namespace API\Middleware;

require_once __DIR__ . '/../Helpers/JwtHelper.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\JwtHelper;
use API\Helpers\Response;

class JwtMiddleware {

    public static function protect() {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            Response::json(["error" => "missing_token"], 401);
        }

        if (!str_starts_with($headers['Authorization'], 'Bearer ')) {
            Response::json(["error" => "invalid_format"], 401);
        }

        $jwt = substr($headers['Authorization'], 7);
        $payload = JwtHelper::verify($jwt);

        if (!$payload) {
            Response::json(["error" => "invalid_token"], 401);
        }

        return $payload;
    }
}
