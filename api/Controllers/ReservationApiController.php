<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__ . '/../Config/database.php';
require_once '/var/www/src/Models/Reservation.php';
require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class ReservationApiController {

    private function getUserRole($user) {
        if (isset($user['role']) && $user['role']) {
            return $user['role'];
        }
        if (!isset($user['id'])) {
            return 'user';
        }
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $role = $stmt->fetchColumn();
        return $role ?: 'user';
    }

    private function isAdmin($user) {
        return $this->getUserRole($user) === 'admin';
    }

    private function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $_POST ?? [];
    }

    public function store($user) {
        $db = \Database::getConnection();
        $input = $this->getInput();

        if (!isset($input['restaurant_id']) || !isset($input['reservation_date']) || !isset($input['reservation_time'])) {
            Response::json(["error" => "missing_fields"], 400);
        }

        $code = substr(md5(uniqid() . rand()), 0, 20);

        $stmt = $db->prepare("
            INSERT INTO reservations (restaurant_id, user_id, reservation_date, reservation_time, code)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            (int)$input['restaurant_id'],
            $user['id'],
            $input['reservation_date'],
            $input['reservation_time'],
            $code
        ]);

        Response::json([
            "status" => "created",
            "code" => $code
        ], 201);
    }

    public function userReservations($user) {
        $db = \Database::getConnection();

        $stmt = $db->prepare("
            SELECT r.*, res.name AS restaurant_name
            FROM reservations r
            JOIN restaurants res ON res.id = r.restaurant_id
            WHERE r.user_id = ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$user['id']]);

        Response::json($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function restaurantReservations($user, $restaurant_id) {
        $db = \Database::getConnection();

        $stmt = $db->prepare("SELECT id, owner_id, created_by FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$restaurant) {
            Response::json(["error" => "not_found"], 404);
        }

        $ownerAllowed = isset($user['id']) && (
            (string)$restaurant['owner_id'] === (string)$user['id'] ||
            (string)$restaurant['created_by'] === (string)$user['id']
        );

        if (!$ownerAllowed && !$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $stmt = $db->prepare("
            SELECT r.*, u.username AS username, u.email AS email, u.username AS user_name
            FROM reservations r
            JOIN users u ON u.id = r.user_id
            WHERE r.restaurant_id = ?
            ORDER BY r.id DESC
        ");

        $stmt->execute([$restaurant_id]);

        Response::json($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function delete($user, $id) {
        $db = \Database::getConnection();

        $stmt = $db->prepare("
            SELECT r.id, r.user_id, r.restaurant_id, res.owner_id, res.created_by
            FROM reservations r
            JOIN restaurants res ON res.id = r.restaurant_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            Response::json(["error" => "not_found"], 404);
        }

        $isOwner = isset($user['id']) && ((string)$row['user_id'] === (string)$user['id']);
        $isRestaurantOwner = isset($user['id']) && (
            (string)$row['owner_id'] === (string)$user['id'] ||
            (string)$row['created_by'] === (string)$user['id']
        );

        if (!$isOwner && !$isRestaurantOwner && !$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $stmt = $db->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["status" => "deleted", "id" => $id]);
    }
}