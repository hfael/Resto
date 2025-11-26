<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../View.php';

class RegisterController
{
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            header("Location: /home/index");
            exit;
        }

        $html = '
            <h2>Inscription</h2>
            <form action="/register/submit" method="POST">
                <input type="text" name="username" placeholder="Nom" />
                <input type="email" name="email" placeholder="Email" />
                <input type="password" name="password" placeholder="Mot de passe" />
                <button type="submit">OK</button>
            </form>
        ';


        View::render($html);
    }

    public function submit()
    {
        $u = trim($_POST['username'] ?? '');
        $e = trim($_POST['email'] ?? '');
        $p = trim($_POST['password'] ?? '');

        if ($u === '' || $e === '' || $p === '') {
            View::render("<p>Champs manquants.</p>");
            return;
        }

        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
            View::render("<p>Email invalide.</p>");
            return;
        }

        if (strlen($u) < 3) {
            View::render("<p>Nom d'utilisateur trop court.</p>");
            return;
        }

        if (strlen($p) < 4) {
            View::render("<p>Mot de passe trop court.</p>");
            return;
        }

        $existingName = User::findByUsername($u);
        if ($existingName) {
            View::render("<p>Nom d'utilisateur déjà utilisé.</p>");
            return;
        }

        $existingMail = User::findByEmail($e);
        if ($existingMail) {
            View::render("<p>Email déjà utilisé.</p>");
            return;
        }

        $hash = password_hash($p, PASSWORD_BCRYPT);

        User::create($u, $e, $hash);

        header("Location: /login/index");
        exit;
    }
}
