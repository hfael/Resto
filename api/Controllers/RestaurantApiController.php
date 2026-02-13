<?php
namespace API\Controllers;

require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__.'/../Config/database.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class RestaurantApiController {

    public function index()
    {
        $db = \Database::getConnection();
        $stmt = $db->query("SELECT * FROM restaurants ORDER BY id DESC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($rows);
    }


    public function store($user)
    {
        $db = \Database::getConnection();

        $name = htmlspecialchars($_POST['name'] ?? '');
        $description = htmlspecialchars($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $average_price = (int)($_POST['average_price'] ?? 0);
        $latitude = (float)($_POST['latitude'] ?? 0);
        $longitude = (float)($_POST['longitude'] ?? 0);
        $contact_name = htmlspecialchars($_POST['contact_name'] ?? '');
        $contact_email = htmlspecialchars($_POST['contact_email'] ?? '');

        if (!isset($_FILES['photo'])) {
            Response::json(["error" => "missing_photo"], 400);
        }

        $filename = 'restaurant_' . time() . '_' . rand(1000,9999) . '.jpg';
        $path = '/var/www/html/uploads/' . $filename;

        move_uploaded_file($_FILES['photo']['tmp_name'], $path);

        $stmt = $db->prepare("
            INSERT INTO restaurants 
            (name, description, event_date, average_price, latitude, longitude, contact_name, contact_email, photo, owner_id, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $name,
            $description,
            $event_date,
            $average_price,
            $latitude,
            $longitude,
            $contact_name,
            $contact_email,
            $filename,
            $user['id'],
            $user['id']
        ]);

        $id = $db->lastInsertId();
        Response::json(["id" => $id, "status" => "created"], 201);
    }



    public function show($id)
    {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        $rows = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$rows) {
            Response::json(["error" => "not_found"], 404);
        }

        Response::json($rows);
    }


    public function update($id)
    {
        $db = \Database::getConnection();
        $input = json_decode(file_get_contents("php://input"), true);

        $stmt = $db->prepare("
            UPDATE restaurants SET 
                name = ?, 
                description = ?, 
                average_price = ? 
            WHERE id = ?
        ");

        $stmt->execute([
            htmlspecialchars($input['name'] ?? ''),
            htmlspecialchars($input['description'] ?? ''),
            (int)($input['average_price'] ?? 0),
            $id
        ]);

        Response::json(["status" => "updated", "id" => $id]);
    }


    public function delete($id)
    {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        Response::json(["status" => "deleted", "id" => $id]);
    }

}



