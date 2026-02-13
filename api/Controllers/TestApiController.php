<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__.'/../Config/database.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class TestApiController {

    public function seed() {
        // Check seed key
        $expected = getenv('SEED_KEY');
        // If no SEED_KEY set, allow default only when not in production
        if (!$expected) {
            if (getenv('APP_ENV') === 'production') {
                Response::json(["error" => "seeding_disabled"], 403);
            }
            $expected = 'devseed';
        }

        $headers = getallheaders();
        $key = $headers['X-Seed-Key'] ?? ($headers['X-SEED-KEY'] ?? null);

        if (!$key || $key !== $expected) {
            Response::json(["error" => "invalid_seed_key"], 401);
        }

        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        $type = $input['type'] ?? 'restaurant';

        if ($type === 'restaurant') {
            $db = \Database::getConnection();

            $name = htmlspecialchars($input['name'] ?? 'Seed Restaurant ' . time());
            $description = htmlspecialchars($input['description'] ?? 'Seeded for tests');
            $event_date = $input['event_date'] ?? date('Y-m-d');
            $average_price = (int)($input['average_price'] ?? 20);
            $latitude = (float)($input['latitude'] ?? 0);
            $longitude = (float)($input['longitude'] ?? 0);
            $contact_name = htmlspecialchars($input['contact_name'] ?? 'Seeder');
            $contact_email = htmlspecialchars($input['contact_email'] ?? 'seed@example.com');
            $filename = $input['photo'] ?? 'seed.jpg';
            $owner_id = $input['owner_id'] ?? null;

            if (!$owner_id) {
                $pass = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'owner')");
                $email = 'seeduser+' . time() . '@example.com';
                $stmt->execute(['seeduser', $email, $pass]);
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
