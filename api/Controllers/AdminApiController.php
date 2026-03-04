<?php
namespace API\Controllers;

require_once '/var/www/src/Database.php';
require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class AdminApiController {
    private function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['PUT', 'PATCH'], true)) {
            $raw = file_get_contents('php://input');
            $parsed = [];
            parse_str($raw, $parsed);
            return $parsed ?: [];
        }
        return $_POST ?? [];
    }

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

    private function findRestaurant($id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
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

    public function updateRestaurantStatus($user, $id) {
        if (!$this->isAdmin($user)) {
            Response::json(["error" => "forbidden"], 403);
        }

        $restaurant = $this->findRestaurant($id);
        if (!$restaurant) {
            Response::json(["error" => "not_found"], 404);
        }

        $input = $this->getInput();
        $status = strtolower(trim((string)($input['status'] ?? '')));

        if (!in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            Response::json(["error" => "invalid_status"], 400);
        }

        if ($status === 'accepted') {
            \Restaurant::accept($id);
        } elseif ($status === 'rejected') {
            $reason = trim((string)($input['reason'] ?? ''));
            if ($reason === '') {
                Response::json(["error" => "missing_reason"], 400);
            }
            \Restaurant::reject($id, $reason);
        } else {
            $db = \Database::getConnection();
            $stmt = $db->prepare("UPDATE restaurants SET status = 'pending', owner_id = NULL, rejection_reason = NULL WHERE id = ?");
            $stmt->execute([$id]);
        }

        $updated = $this->findRestaurant($id);
        Response::json([
            "status" => "updated",
            "id" => (int)$id,
            "restaurant" => $updated ? $this->formatRestaurant($updated) : null
        ], 200);
    }
}
