<?php

require_once __DIR__.'/../Models/Reservation.php';
require_once __DIR__.'/../Models/Restaurant.php';
require_once __DIR__.'/../Mailer.php';
require_once __DIR__.'/../View.php';

class ReservationController
{
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login/index");
            exit;
        }
    }

    public function index()
    {
        $this->requireLogin();

        Reservation::deleteExpired();

        $items = Reservation::allByUser($_SESSION['user_id']);

        $html = "<h2>Mes réservations</h2>";

        foreach ($items as $r) {
            $html .= "
                <p>
                    <strong>{$r['restaurant_name']}</strong><br>
                    Date : {$r['reservation_date']}<br>
                    Heure : {$r['reservation_time']}<br>
                    Code : {$r['code']}
                    <a href='/reservation/delete?id={$r['id']}'>Supprimer</a>
                </p><hr>";
        }

        View::render($html);
    }

    public function create()
    {
        $this->requireLogin();

        $restaurants = Restaurant::all();

        $html = "<h2>Nouvelle réservation</h2>
        <form method='POST' action='/reservation/store'>
        <select name='restaurant_id'>";

        foreach ($restaurants as $r) {
            $html .= "<option value='{$r['id']}'>{$r['name']}</option>";
        }

        $html .= "
        </select><br><br>
        <input type='date' name='reservation_date'><br>
        <input type='time' name='reservation_time'><br>
        <button type='submit'>Réserver</button>
        </form>";

        View::render($html);
    }

    public function store()
    {


        $this->requireLogin();

        $code = strtoupper(bin2hex(random_bytes(4)));  

        Reservation::create([
            'restaurant_id' => $_POST['restaurant_id'],
            'user_id' => $_SESSION['user_id'],
            'reservation_date' => $_POST['reservation_date'],
            'reservation_time' => $_POST['reservation_time'],
            'code' => $code
        ]);
        mail($_SESSION['user_email'], "Réservation confirmée", "<h1>Réservation confirmée</h1><p>Date : {$_POST['reservation_date']}</p><p>Heure : {$_POST['reservation_time']}</p><p>Code : {$code}</p>", "Content-Type: text/html; charset=UTF-8\r\n");

        header("Location: /reservation/index");
        exit;
    }

    public function delete()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit;
        }

        Reservation::delete($id, $_SESSION['user_id']);

        header("Location: /reservation/index");
        exit;
    }
    public function byRestaurant()
    {
        $this->requireLogin();

        $restaurant_id = $_GET['id'] ?? null;
        if (!$restaurant_id) {
            View::render("<p>ID manquant.</p>");
            return;
        }

        // Vérifier ownership
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT owner_id FROM restaurants WHERE id=?");
        $stmt->execute([$restaurant_id]);
        $owner_id = $stmt->fetchColumn();

        if (!$owner_id) {
            View::render("<p>Restaurant introuvable.</p>");
            return;
        }

        if ($owner_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit("Accès refusé");
        }

        // Récupérer les réservations associées
        $stmt = $db->prepare("
            SELECT 
                r.id,
                r.reservation_date,
                r.reservation_time,
                r.code,
                u.username,
                u.email
            FROM reservations r
            JOIN users u ON u.id = r.user_id
            WHERE r.restaurant_id = ?
            ORDER BY r.reservation_date ASC, r.reservation_time ASC
        ");
        $stmt->execute([$restaurant_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = "<h2>Réservations du restaurant #$restaurant_id</h2>";

        if (empty($items)) {
            $html .= "<p>Aucune réservation.</p>";
            View::render($html);
            return;
        }

        foreach ($items as $r) {
            $html .= "
                <div style='margin-bottom:10px; padding:10px; border:1px solid #ccc'>
                    <strong>Utilisateur :</strong> {$r['username']} ({$r['email']})<br>
                    <strong>Date :</strong> {$r['reservation_date']}<br>
                    <strong>Heure :</strong> {$r['reservation_time']}<br>
                    <strong>Code :</strong> {$r['code']}<br><br>

                    <a href='/reservation/deleteByOwner?id={$r['id']}&restaurant_id={$restaurant_id}'>Supprimer</a>
                </div>
            ";
        }
        View::render($html);
    }
    public function deleteByOwner()
    {
        $this->requireLogin();

        $reservation_id = $_GET['id'] ?? null;
        $restaurant_id  = $_GET['restaurant_id'] ?? null;

        if (!$reservation_id || !$restaurant_id) {
            http_response_code(400);
            exit("Paramètres manquants");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT owner_id FROM restaurants WHERE id=?");
        $stmt->execute([$restaurant_id]);
        $owner_id = $stmt->fetchColumn();

        if ($owner_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit("Accès refusé");
        }

        $stmt = $db->prepare("DELETE FROM reservations WHERE id=?");
        $stmt->execute([$reservation_id]);

        header("Location: /reservation/byRestaurant?id=" . $restaurant_id);
        exit;
    }


}
