<?php

require_once __DIR__ . '/../Models/Reservation.php';
require_once __DIR__ . '/../View.php';
require_once __DIR__ . '/../Database.php';

class ReservationController
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])){
            header("Location: /login/index");
            exit;
        }

        $items = Reservation::allByUser($_SESSION['user_id']);

        $html = "<h2>Mes réservations</h2>";

        foreach ($items as $r) {
            $html .= $r['date_reservation'] . " " . $r['time_reservation'];
            $html .= " – " . $r['guests'] . " personnes<br>";
        }

        $html .= '<br><a href="/reservation/create">Nouvelle réservation</a>';

        View::render($html);
    }

    public function create()
    {
        if (!isset($_SESSION['user_id'])) exit("Accès refusé");

        $html = '
            <h2>Nouvelle réservation</h2>
            <form action="/reservation/store" method="POST">
                <input type="date" name="date_reservation" />
                <input type="time" name="time_reservation" />
                <input type="number" name="guests" placeholder="Nombre de personnes" min="1" />
                <button type="submit">Réserver</button>
            </form>
        ';

        View::render($html);
    }

    public function store()
    {
        if (!isset($_SESSION['user_id'])) exit("Accès refusé");

        $date   = $_POST['date_reservation'] ?? '';
        $time   = $_POST['time_reservation'] ?? '';
        $guests = $_POST['guests'] ?? '';

        if (!$date || !$time || !$guests) {
            exit("Champs manquants");
        }

        Reservation::create($_SESSION['user_id'], $date, $time, $guests);

        View::render("<p>Réservation enregistrée</p>");
    }
}
