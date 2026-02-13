<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__.'/../Config/database.php';
require_once '/var/www/src/Models/User.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';

use API\Helpers\Response;
use API\Helpers\JwtHelper;

class AuthApiController {

    public function register() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
            Response::json(["error" => "missing_fields"], 400);
        }

        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            Response::json(["error" => "email_exists"], 400);
        }

        $hash = password_hash($input['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$input['username'], $input['email'], $hash]);

        $id = $db->lastInsertId();
        $token = JwtHelper::generate(["id" => $id, "email" => $input['email']]);

        Response::json([
            "id" => $id,
            "username" => $input['username'],
            "email" => $input['email'],
            "token" => $token
        ], 201);
    }

    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['email']) || !isset($input['password'])) {
            Response::json(["error" => "missing_fields"], 400);
        }

        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Response::json(["error" => "invalid_credentials"], 401);
        }

        if (!password_verify($input['password'], $user['password'])) {
            Response::json(["error" => "invalid_credentials"], 401);
        }

        $token = JwtHelper::generate(["id" => $user['id'], "email" => $user['email']]);

        Response::json([
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "token" => $token
        ], 200);
    }
}
