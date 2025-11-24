<?php

require_once __DIR__ . '/../Models/Reservation.php';

class ReservationController
{
    public function index()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) exit("Accès refusé");

        $items = Reservation::allByUser($_SESSION['user_id']);

        foreach ($items as $r) {
            echo $r['date_reservation'].' '.$r['time_reservation'].' - '.$r['guests'].' personnes<br>';
        }

        echo '<br><a href="/reservation/create">Nouvelle réservation</a>';
    }

    public function create()
    {
        echo '
            <form action="/reservation/store" method="POST">
                <input type="date" name="date_reservation" />
                <input type="time" name="time_reservation" />
                <input type="number" name="guests" placeholder="Nombre de personnes" min="1" />
                <button type="submit">Réserver</button>
            </form>
        ';
    }

    public function store()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) exit("Accès refusé");

        $date  = $_POST['date_reservation'] ?? '';
        $time  = $_POST['time_reservation'] ?? '';
        $guests = $_POST['guests'] ?? '';

        if (!$date || !$time || !$guests) exit("Champs manquants");

        Reservation::create($_SESSION['user_id'], $date, $time, $guests);

        echo "Réservation enregistrée";
    }
}
