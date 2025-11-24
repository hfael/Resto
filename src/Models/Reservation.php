<?php

require_once __DIR__ . '/../Database.php';

class Reservation
{
    public static function create($user_id, $date, $time, $guests)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO reservations(user_id, date_reservation, time_reservation, guests)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$user_id, $date, $time, $guests]);
    }

    public static function allByUser($user_id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
