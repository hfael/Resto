<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class RestaurantApiController {
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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $raw = file_get_contents('php://input');
            $parsed = [];
            parse_str($raw, $parsed);
            return $parsed ?: [];
        }
        return $_POST ?? [];
    }

    private function baseUrl(): string {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $forwarded ?: ($isHttps ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $scheme . '://' . $host;
    }

    private function absoluteUrl($path): ?string {
        if ($path === null || $path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    private function formatRestaurant(array $row): array {
        $imagePath = $row['photo'] ?? ($row['image'] ?? null);
        $absolute = $this->absoluteUrl($imagePath);
        $row['photo'] = $absolute;
        $row['image'] = $absolute;
        return $row;
    }

    private function formatRestaurants(array $rows): array {
        foreach ($rows as $index => $row) {
            $rows[$index] = $this->formatRestaurant($row);
        }
        return $rows;
    }

    private function findRestaurantRaw($id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function findRestaurant($id) {
        $row = $this->findRestaurantRaw($id);
        if (!$row) {
            return false;
        }
        return $this->formatRestaurant($row);
    }

    private function canManageRestaurant($user, $restaurant) {
        if ($this->isAdmin($user)) {
            return true;
        }
        return isset($user['id']) && (string)$restaurant['created_by'] === (string)$user['id'];
    }

    public function index()
    {
        $db = \Database::getConnection();
        $stmt = $db->query("SELECT * FROM restaurants WHERE status = 'accepted' ORDER BY name ASC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($this->formatRestaurants($rows));
    }

    public function search()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        if ($q === '') {
            Response::json([]);
        }
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, average_price, created_by, photo FROM restaurants WHERE status = 'accepted' AND name LIKE ? ORDER BY id DESC");
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($this->formatRestaurants($rows));
    }

    public function mine($user)
    {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE created_by = ? ORDER BY id DESC");
        $stmt->execute([$user['id']]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($this->formatRestaurants($rows));
    }

    public function pending($user)
    {
        if (!$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }
        $db = \Database::getConnection();
        $stmt = $db->query("SELECT r.*, u.username AS owner_name FROM restaurants r JOIN users u ON u.id = r.created_by WHERE r.status = 'pending' ORDER BY r.created_at ASC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($this->formatRestaurants($rows));
    }

    public function bookings($user, $id)
    {
        $restaurant = $this->findRestaurantRaw($id);
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

        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT r.*, u.username AS username, u.email AS email FROM reservations r JOIN users u ON u.id = r.user_id WHERE r.restaurant_id = ? ORDER BY r.reservation_date ASC, r.reservation_time ASC");
        $stmt->execute([$id]);
        $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        Response::json([
            "restaurant" => $this->formatRestaurant($restaurant),
            "bookings" => $bookings
        ]);
    }

    public function store($user)
    {
        $db = \Database::getConnection();

        $input = $_POST;
        $name = htmlspecialchars($input['name'] ?? '');
        $description = htmlspecialchars($input['description'] ?? '');
        $event_date = $input['event_date'] ?? null;
        $average_price = (int)($input['average_price'] ?? 0);
        $latitude = (float)($input['latitude'] ?? 0);
        $longitude = (float)($input['longitude'] ?? 0);
        $contact_name = htmlspecialchars($input['contact_name'] ?? '');
        $contact_email = htmlspecialchars($input['contact_email'] ?? '');

        if ($name === '' || $description === '' || $event_date === null || $contact_name === '' || $contact_email === '') {
            Response::json(["error" => "missing_fields"], 400);
        }

        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            Response::json(["error" => "invalid_email"], 400);
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            Response::json(["error" => "missing_photo"], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            Response::json(["error" => "invalid_photo_type"], 400);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $ext = $ext ? strtolower($ext) : 'jpg';
        $filename = 'restaurant_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $path = '/var/www/html/uploads/' . $filename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $path)) {
            Response::json(["error" => "upload_failed"], 500);
        }

        $photo = '/uploads/' . $filename;

        $stmt = $db->prepare("
            INSERT INTO restaurants 
            (name, description, event_date, average_price, latitude, longitude, contact_name, contact_email, photo, owner_id, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'pending')
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
            $photo,
            $user['id']
        ]);

        $id = $db->lastInsertId();
        $created = $this->findRestaurant($id);
        Response::json([
            "id" => $id,
            "status" => "created",
            "name" => $name,
            "restaurant" => $created
        ], 201);
    }

    public function show($id)
    {
        $row = $this->findRestaurant($id);
        if (!$row) {
            Response::json(["error" => "not_found"], 404);
        }
        Response::json($row);
    }

    public function update($user, $id)
    {
        $existing = $this->findRestaurantRaw($id);
        if (!$existing) {
            Response::json(["error" => "not_found"], 404);
        }

        if (!$this->canManageRestaurant($user, $existing)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $input = $this->getInput();

        $data = [
            'name' => htmlspecialchars($input['name'] ?? $existing['name']),
            'description' => htmlspecialchars($input['description'] ?? $existing['description']),
            'event_date' => $input['event_date'] ?? $existing['event_date'],
            'average_price' => (int)($input['average_price'] ?? $existing['average_price']),
            'latitude' => (float)($input['latitude'] ?? $existing['latitude']),
            'longitude' => (float)($input['longitude'] ?? $existing['longitude']),
            'contact_name' => htmlspecialchars($input['contact_name'] ?? $existing['contact_name']),
            'contact_email' => htmlspecialchars($input['contact_email'] ?? $existing['contact_email']),
            'photo' => $existing['photo']
        ];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['photo']['type'], $allowed)) {
                Response::json(["error" => "invalid_photo_type"], 400);
            }
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $ext = $ext ? strtolower($ext) : 'jpg';
            $filename = 'restaurant_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $path = '/var/www/html/uploads/' . $filename;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $path)) {
                Response::json(["error" => "upload_failed"], 500);
            }
            $data['photo'] = '/uploads/' . $filename;
        }

        if ($existing['status'] === 'rejected') {
            \Restaurant::resubmit($id, $data);
        } else {
            \Restaurant::update($id, $data);
        }

        Response::json([
            "status" => "updated",
            "id" => $id,
            "restaurant" => $this->findRestaurant($id)
        ]);
    }

    public function delete($user, $id)
    {
        $existing = $this->findRestaurantRaw($id);
        if (!$existing) {
            Response::json(["error" => "not_found"], 404);
        }

        if (!$this->canManageRestaurant($user, $existing)) {
            Response::json(["error" => "forbidden"], 403);
        }

        \Restaurant::delete($id);
        Response::json(["status" => "deleted", "id" => $id]);
    }

    public function cancel($user, $id)
    {
        $existing = $this->findRestaurantRaw($id);
        if (!$existing) {
            Response::json(["error" => "not_found"], 404);
        }

        if (!$this->canManageRestaurant($user, $existing)) {
            Response::json(["error" => "forbidden"], 403);
        }

        if ($existing['status'] !== 'pending') {
            Response::json(["error" => "invalid_status"], 400);
        }

        \Restaurant::cancel($id);
        Response::json(["status" => "cancelled", "id" => $id]);
    }

    public function accept($user, $id)
    {
        if (!$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $existing = $this->findRestaurantRaw($id);
        if (!$existing) {
            Response::json(["error" => "not_found"], 404);
        }

        \Restaurant::accept($id);
        Response::json(["status" => "accepted", "id" => $id]);
    }

    public function reject($user, $id)
    {
        if (!$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $existing = $this->findRestaurantRaw($id);
        if (!$existing) {
            Response::json(["error" => "not_found"], 404);
        }

        $input = $this->getInput();
        $reason = trim($input['reason'] ?? '');
        if ($reason === '') {
            Response::json(["error" => "missing_reason"], 400);
        }

        \Restaurant::reject($id, $reason);
        Response::json(["status" => "rejected", "id" => $id]);
    }
}
