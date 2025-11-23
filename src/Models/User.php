<?php

require_once __DIR__ . '/../Database.php';

class User {
    public static function create($username, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO users(username, password) VALUES (?, ?)");
        return $stmt->execute([$username, $password]);
    }

    public static function findByUsername($username) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
