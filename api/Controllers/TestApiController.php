<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';

use API\Helpers\Response;
use API\Helpers\JwtHelper;

class TestApiController {
    private function getHeader(array $headers, string $name): ?string {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return is_string($value) ? trim($value) : null;
            }
        }
        return null;
    }

    private function asBool($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        return false;
    }

    private function resetData(\PDO $db): void {
        $db->exec("SET FOREIGN_KEY_CHECKS=0");
        $db->exec("TRUNCATE TABLE reservations");
        $db->exec("TRUNCATE TABLE restaurants");
        $db->exec("TRUNCATE TABLE users");
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    private function deleteUserWithDependencies(\PDO $db, string $username, string $email): void {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($userIds as $userId) {
            $userId = (int)$userId;

            $stmt = $db->prepare("SELECT id FROM restaurants WHERE created_by = ? OR owner_id = ?");
            $stmt->execute([$userId, $userId]);
            $restaurantIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($restaurantIds as $restaurantId) {
                $deleteReservations = $db->prepare("DELETE FROM reservations WHERE restaurant_id = ?");
                $deleteReservations->execute([(int)$restaurantId]);
            }

            $deleteRestaurants = $db->prepare("DELETE FROM restaurants WHERE created_by = ? OR owner_id = ?");
            $deleteRestaurants->execute([$userId, $userId]);

            $deleteOwnReservations = $db->prepare("DELETE FROM reservations WHERE user_id = ?");
            $deleteOwnReservations->execute([$userId]);

            $deleteUser = $db->prepare("DELETE FROM users WHERE id = ?");
            $deleteUser->execute([$userId]);
        }
    }

    public function seed() {
        $expected = getenv('SEED_KEY');
        if (!$expected) {
            if (getenv('APP_ENV') === 'production') {
                Response::json(["error" => "seeding_disabled"], 403);
            }
            $expected = 'devseed';
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $key = $this->getHeader($headers, 'X-Seed-Key') ?? ($_SERVER['HTTP_X_SEED_KEY'] ?? null);

        if (!$key || $key !== $expected) {
            Response::json(["error" => "invalid_seed_key"], 401);
        }

        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        $type = $input['type'] ?? 'restaurant';

        $db = \Database::getConnection();
        if ($this->asBool($input['reset'] ?? false)) {
            $this->resetData($db);
        }

        if ($type === 'user') {
            $role = $input['role'] ?? 'user';
            $suffix = bin2hex(random_bytes(4));
            $username = trim((string)($input['username'] ?? ('seeduser_' . $suffix)));
            $email = trim((string)($input['email'] ?? ('seeduser+' . $suffix . '@example.com')));
            $password = $input['password'] ?? 'password';
            $this->deleteUserWithDependencies($db, $username, $email);

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $role]);
            $id = $db->lastInsertId();
            $token = JwtHelper::generate(["id" => $id, "email" => $email, "role" => $role]);

            Response::json([
                "status" => "seeded",
                "type" => "user",
                "id" => $id,
                "username" => $username,
                "email" => $email,
                "role" => $role,
                "password" => $password,
                "token" => $token
            ], 201);
        }

        if ($type === 'restaurant') {
            $name = htmlspecialchars($input['name'] ?? ('Seed Restaurant ' . time()));
            $description = htmlspecialchars($input['description'] ?? 'Seeded for tests');
            $event_date = $input['event_date'] ?? date('Y-m-d');
            $average_price = (int)($input['average_price'] ?? 20);
            $latitude = (float)($input['latitude'] ?? 0);
            $longitude = (float)($input['longitude'] ?? 0);
            $contact_name = htmlspecialchars($input['contact_name'] ?? 'Seeder');
            $contact_email = htmlspecialchars($input['contact_email'] ?? 'seed@example.com');
            $filename = $input['photo'] ?? '/uploads/seed.jpg';
            $owner_id = isset($input['owner_id']) ? (int)$input['owner_id'] : null;

            if (!$owner_id) {
                $suffix = bin2hex(random_bytes(4));
                $ownerUsername = 'seedowner_' . $suffix;
                $ownerEmail = 'seedowner+' . $suffix . '@example.com';
                $this->deleteUserWithDependencies($db, $ownerUsername, $ownerEmail);
                $pass = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'owner')");
                $stmt->execute([$ownerUsername, $ownerEmail, $pass]);
                $owner_id = $db->lastInsertId();
            }

            $stmt = $db->prepare("INSERT INTO restaurants (name, description, event_date, average_price, latitude, longitude, contact_name, contact_email, photo, owner_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'accepted')");
            $stmt->execute([$name, $description, $event_date, $average_price, $latitude, $longitude, $contact_name, $contact_email, $filename, $owner_id, $owner_id]);
            $id = $db->lastInsertId();

            Response::json(["status" => "seeded", "type" => "restaurant", "id" => $id], 201);
        }

        Response::json(["error" => "unknown_type"], 400);
    }
}
