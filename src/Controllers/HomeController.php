<?php

require_once __DIR__ . '/../View.php';
require_once __DIR__ . '/../Models/Restaurant.php';

class HomeController
{
    public function index()
    {
        $restaurants = Restaurant::allAccepted();

        View::render('home/index.twig', [
            'restaurants' => $restaurants,
            'session'     => $_SESSION,
            'connected'   => isset($_SESSION['user_id'])
        ]);
    }
}
