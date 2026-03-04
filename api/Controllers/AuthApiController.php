<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__ . '/../Config/database.php';
require_once '/var/www/src/Models/User.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';

use API\Helpers\Response;
use API\Helpers\JwtHelper;

class AuthApiController {

    private function fetchUserById($id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function register() {
        $input = json_decode(file_get_contents("php://input"), true) ?? [];

        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            Response::json(["error" => "missing_fields"], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(["error" => "invalid_email"], 400);
        }

        if (strlen($username) < 3) {
            Response::json(["error" => "username_too_short"], 400);
        }

        if (strlen($password) < 4) {
            Response::json(["error" => "password_too_short"], 400);
        }

        if (\User::findByUsername($username)) {
            Response::json(["error" => "username_exists"], 400);
        }

        if (\User::findByEmail($email)) {
            Response::json(["error" => "email_exists"], 400);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $db = \Database::getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hash]);

        $id = $db->lastInsertId();
        $role = 'user';
        $token = JwtHelper::generate(["id" => $id, "email" => $email, "role" => $role]);

        Response::json([
            "id" => $id,
            "username" => $username,
            "email" => $email,
            "role" => $role,
            "token" => $token
        ], 201);
    }

    public function login() {
        $input = json_decode(file_get_contents("php://input"), true) ?? [];

        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::json(["error" => "missing_fields"], 400);
        }

        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Response::json(["error" => "invalid_credentials"], 401);
        }

        if (!password_verify($password, $user['password'])) {
            Response::json(["error" => "invalid_credentials"], 401);
        }

        $token = JwtHelper::generate(["id" => $user['id'], "email" => $user['email'], "role" => $user['role']]);

        Response::json([
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "role" => $user['role'],
            "token" => $token
        ], 200);
    }

    public function me($user) {
        $row = $this->fetchUserById($user['id'] ?? null);
        if (!$row) {
            Response::json(["error" => "not_found"], 404);
        }
        Response::json($row, 200);
    }

    public function logout() {
        Response::json([
            "status" => "logged_out"
        ], 200);
    }
}
