<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../View.php';

class LoginController
{
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            header("Location: /home/index");
            exit;
        }

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
        $u = isset($_POST['username']) ? trim($_POST['username']) : '';
        $p = isset($_POST['password']) ? trim($_POST['password']) : '';

        if ($u === '' || $p === '') {
            View::render("<p>Champs manquants.</p>");
            return;
        }

        $user = User::findByUsername($u);
        if (!$user) {
            View::render("<p>Utilisateur inconnu.</p>");
            return;
        }

        if (!password_verify($p, $user['password'])) {
            View::render("<p>Mot de passe incorrect.</p>");
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        

        header("Location: /home/index");
        exit;
    }
}
