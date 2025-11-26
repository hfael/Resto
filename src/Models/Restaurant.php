<?php

require_once __DIR__ . '/../Database.php';

class Restaurant
{

    public static function allAccepted()
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM restaurants WHERE status = 'accepted' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByOwner($owner_id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM restaurants 
            WHERE created_by = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$owner_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allPending()
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT r.*, u.username AS owner_name 
            FROM restaurants r
            JOIN users u ON u.id = r.created_by
            WHERE r.status = 'pending'
            ORDER BY r.created_at ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            (name, description, event_date, average_price, latitude, longitude, contact_name, contact_email, photo, created_by, owner_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'pending')
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
            $_SESSION['user_id']
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


    public static function resubmit($id, $data)
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE restaurants
            SET 
                name=?, 
                description=?, 
                event_date=?, 
                average_price=?, 
                latitude=?, 
                longitude=?, 
                contact_name=?, 
                contact_email=?, 
                photo=?, 
                status='pending',
                rejection_reason=NULL
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

    public static function cancel($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE restaurants SET status='cancelled' WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function accept($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE restaurants 
            SET status='accepted', owner_id=created_by, rejection_reason=NULL
            WHERE id=?
        ");
        return $stmt->execute([$id]);
    }

    public static function reject($id, $reason)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE restaurants 
            SET status='rejected', rejection_reason=?
            WHERE id=?
        ");
        return $stmt->execute([$reason, $id]);
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM restaurants WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function searchAccepted($text)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, name, average_price 
            FROM restaurants
            WHERE status='accepted' AND name LIKE ?
            ORDER BY name ASC
        ");
        $stmt->execute(['%' . $text . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
