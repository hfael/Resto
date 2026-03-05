<?php

require_once __DIR__ . '/../Models/Reservation.php';
require_once __DIR__ . '/../Models/Restaurant.php';
require_once __DIR__ . '/../Mailer.php';
require_once __DIR__ . '/../View.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Csrf.php';

class ReservationController
{
    private function requirePost()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Methode non autorisee.');
        }
    }

    private function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login/index');
            exit;
        }
    }

    public function index()
    {
        $this->requireLogin();

        Reservation::deleteExpired();
        $items = Reservation::allByUser($_SESSION['user_id']);

        View::render('reservation/index.twig', [
            'items' => $items,
            'session' => $_SESSION,
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $restaurants = Restaurant::allAccepted();

        View::render('reservation/create.twig', [
            'restaurants' => $restaurants,
            'session' => $_SESSION,
        ]);
    }

    public function store()
    {
        $this->requireLogin();
        $this->requirePost();
        Csrf::checkOrAbort();

        $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
        $date = trim((string)($_POST['reservation_date'] ?? ''));
        $time = trim((string)($_POST['reservation_time'] ?? ''));

        if ($restaurantId <= 0 || $date === '' || $time === '') {
            http_response_code(400);
            exit('Champs manquants.');
        }

        $code = strtoupper(bin2hex(random_bytes(4)));

        Reservation::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $_SESSION['user_id'],
            'reservation_date' => $date,
            'reservation_time' => $time,
            'code' => $code,
        ]);

        $html = '<h1>Reservation confirmee</h1>'
            . '<p>Date : ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Heure : ' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Code : ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</p>';

        Mailer::send($_SESSION['user_email'], 'Reservation confirmee', $html);

        header('Location: /reservation/index');
        exit;
    }

    public function delete()
    {
        $this->requireLogin();
        $this->requirePost();
        Csrf::checkOrAbort();

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit;
        }

        Reservation::delete($id, $_SESSION['user_id']);

        header('Location: /reservation/index');
        exit;
    }

    public function byRestaurant()
    {
        $this->requireLogin();

        $restaurantId = (int)($_GET['id'] ?? 0);
        if ($restaurantId <= 0) {
            http_response_code(400);
            exit('ID manquant.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, name, owner_id, created_by FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurantId]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$restaurant) {
            http_response_code(404);
            exit('Restaurant introuvable.');
        }

        $isOwner = (string)($restaurant['owner_id'] ?? '') === (string)$_SESSION['user_id']
            || (string)($restaurant['created_by'] ?? '') === (string)$_SESSION['user_id'];
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $stmt = $db->prepare(
            'SELECT r.id, r.reservation_date, r.reservation_time, r.code, u.username, u.email
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             WHERE r.restaurant_id = ?
             ORDER BY r.reservation_date ASC, r.reservation_time ASC'
        );
        $stmt->execute([$restaurantId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('restaurant/bookings.twig', [
            'restaurant' => $restaurant,
            'bookings' => $items,
            'session' => $_SESSION,
        ]);
    }

    public function deleteByOwner()
    {
        $this->requireLogin();
        $this->requirePost();
        Csrf::checkOrAbort();

        $reservationId = (int)($_POST['id'] ?? 0);
        $restaurantId = (int)($_POST['restaurant_id'] ?? 0);

        if ($reservationId <= 0 || $restaurantId <= 0) {
            http_response_code(400);
            exit('Parametres manquants.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT owner_id, created_by FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurantId]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$restaurant) {
            http_response_code(404);
            exit('Restaurant introuvable.');
        }

        $isOwner = (string)($restaurant['owner_id'] ?? '') === (string)$_SESSION['user_id']
            || (string)($restaurant['created_by'] ?? '') === (string)$_SESSION['user_id'];
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $stmt = $db->prepare('DELETE FROM reservations WHERE id = ?');
        $stmt->execute([$reservationId]);

        header('Location: /reservation/byRestaurant?id=' . $restaurantId);
        exit;
    }
}

