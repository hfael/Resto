<?php

require_once __DIR__ . '/../Database.php';

class Reservation
{
    public static function create($data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO reservations (restaurant_id, user_id, reservation_date, reservation_time, code)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['restaurant_id'],
            $data['user_id'],
            $data['reservation_date'],
            $data['reservation_time'],
            $data['code']
        ]);
    }

    public static function allByUser($userId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, res.name AS restaurant_name
            FROM reservations r
            JOIN restaurants res ON res.id = r.restaurant_id
            WHERE r.user_id = ?
            ORDER BY r.reservation_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteExpired()
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM reservations WHERE reservation_date < CURDATE()");
        $stmt->execute();
    }

    public static function delete($id, $userId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

}
