<?php

require_once __DIR__ . '/../Database.php';

class Dish {
    public static function all() {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM dishes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($name, $price) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO dishes(name, price) VALUES (?, ?)");
        return $stmt->execute([$name, $price]);
    }
}
