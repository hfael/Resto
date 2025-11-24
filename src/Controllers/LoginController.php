<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../View.php';

class LoginController
{
    public function index()
    {
        $html = '
            <h2>Connexion</h2>
            <form action="/login/submit" method="POST">
                <input type="text" name="username" placeholder="Nom" />
                <input type="password" name="password" placeholder="Mot de passe" />
                <button type="submit">Connexion</button>
            </form>
        ';

        View::render($html);
    }

    public function submit()
    {
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';

        if (!$u || !$p) {
            exit("Champs manquants");
        }

        $user = User::findByUsername($u);
        if (!$user) {
            exit("Utilisateur inconnu");
        }

        if (!password_verify($p, $user['password'])) {
            exit("Mot de passe incorrect");
        }

        $_SESSION['user_id'] = $user['id'];

        View::render("<p>Connexion OK</p>");
    }
}
