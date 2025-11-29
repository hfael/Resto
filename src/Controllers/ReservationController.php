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

        Mailer::send(
            $_SESSION['user_email'],
            "Confirmation de réservation",
            "
            <h2>Votre réservation est confirmée</h2>
            <p>Date : {$_POST['reservation_date']}</p>
            <p>Heure : {$_POST['reservation_time']}</p>
            <p>Code : <strong>$code</strong></p>
            "
        );

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
}
