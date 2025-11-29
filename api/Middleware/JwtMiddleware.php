<?php
namespace API\Middleware;

require_once __DIR__ . '/../Helpers/JwtHelper.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\JwtHelper;
use API\Helpers\Response;

class JwtMiddleware {
    public static function verify() {
        Response::json(["status" => "middleware OK"]);
    }
}
