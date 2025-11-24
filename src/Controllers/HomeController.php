<?php

require_once __DIR__ . '/../View.php';

class HomeController
{
    public function index()
    {
        $connected = isset($_SESSION['user_id']);

        if ($connected) {
            $html = "
                <h1>Bienvenue</h1>
                <p>Vous êtes connecté.</p>
                <p>
                    <a href='/dish/index'>Voir les plats</a><br>
                    <a href='/reservation/index'>Voir mes réservations</a>
                </p>
            ";
        } else {
            $html = "
                <h1>Bienvenue</h1>
                <p>Veuillez vous connecter pour accéder aux fonctionnalités.</p>
                <p>
                    <a href='/login/index'>Connexion</a><br>
                    <a href='/register/index'>Inscription</a>
                </p>
            ";
        }

        View::render($html);
    }
}
