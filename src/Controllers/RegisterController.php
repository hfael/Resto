<?php

require_once __DIR__ . '/../Models/User.php';

class RegisterController
{
    public function index()
    {
        echo '
            <form action="/register/submit" method="POST">
                <input type="text" name="username" placeholder="Nom" />
                <input type="password" name="password" placeholder="Mot de passe" />
                <button type="submit">OK</button>
            </form>
        ';
    }

    public function submit()
    {
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';

        if (!$u || !$p) {
            exit("Champs manquants");
        }

        $hash = password_hash($p, PASSWORD_BCRYPT);

        User::create($u, $hash);
        echo "Inscription OK";
    }
}
