<?php

require_once __DIR__ . '/../Database.php';

class User
{
    public static function findByUsername($u)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$u]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByEmail($e)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$e]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($u, $e, $hash)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        return $stmt->execute([$u, $e, $hash]);
    }
}
    