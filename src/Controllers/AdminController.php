<?php

require_once __DIR__ . '/../Models/Restaurant.php';
require_once __DIR__ . '/../View.php';

class AdminController
{
    private function requireAdmin()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /login/index");
            exit;
        }
    }

    public function restaurants()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /login/index");
            exit;
        }
        $items = Restaurant::allPending();

        View::render('admin/restaurants.twig', [
            'pendingItems' => $items,
            'session'      => $_SESSION
        ]);
    }
}