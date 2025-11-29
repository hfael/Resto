<?php
namespace API\Controllers;

require_once '/var/www/src/Models/User.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';

use API\Helpers\Response;
use API\Helpers\JwtHelper;

class AuthApiController {

    public function register() {
        Response::json(["status" => "register OK"]);
    }

    public function login() {
        Response::json(["status" => "login OK"]);
    }
}
