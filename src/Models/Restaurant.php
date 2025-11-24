<?php

require_once __DIR__ . '/../Database.php';

class Restaurant
{
    public static function all()
    {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM restaurants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO restaurants 
            (name, description, event_date, average_price, latitude, longitude, contact_name, contact_email, photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['event_date'],
            $data['average_price'],
            $data['latitude'],
            $data['longitude'],
            $data['contact_name'],
            $data['contact_email'],
            $data['photo'],
        ]);
    }

    public static function update($id, $data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE restaurants 
            SET name=?, description=?, event_date=?, average_price=?, latitude=?, longitude=?, contact_name=?, contact_email=?, photo=?
            WHERE id=?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['event_date'],
            $data['average_price'],
            $data['latitude'],
            $data['longitude'],
            $data['contact_name'],
            $data['contact_email'],
            $data['photo'],
            $id
        ]);
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM restaurants WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
