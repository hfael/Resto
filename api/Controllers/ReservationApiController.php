<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once __DIR__.'/../Config/database.php';
require_once '/var/www/src/Models/Reservation.php';
require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class ReservationApiController {

    public function store($user) {
        $db = \Database::getConnection();
        $input = $_POST;

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

        $stmt = $db->prepare("SELECT owner_id FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurant_id]);
        $owner = $stmt->fetchColumn();

        if ($owner != $user['id']) {
            Response::json(["error" => "forbidden"], 403);
        }

        $stmt = $db->prepare("
            SELECT r.*, u.username AS user_name
            FROM reservations r
            JOIN users u ON u.id = r.user_id
            WHERE restaurant_id = ?
            ORDER BY r.id DESC
        ");

        $stmt->execute([$restaurant_id]);

        Response::json($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function delete($user, $id) {
        $db = \Database::getConnection();

        $stmt = $db->prepare("SELECT user_id FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $owner = $stmt->fetchColumn();

        if ($owner != $user['id']) {
            Response::json(["error" => "forbidden"], 403);
        }

        $stmt = $db->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["status" => "deleted", "id" => $id]);
    }
}
