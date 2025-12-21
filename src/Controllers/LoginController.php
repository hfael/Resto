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

        View::render('auth/login.twig');
    }

    public function submit()
    {
        $email = trim($_POST['email'] ?? ''); 
        $p = trim($_POST['password'] ?? '');

        if ($email === '' || $p === '') {
            View::render('auth/login.twig', ['error' => 'Champs manquants.']);
            return;
        }

        $user = User::findByEmail($email); 
        if (!$user) {
            View::render('auth/login.twig', ['error' => 'Utilisateur inconnu.']);
            return;
        }

        if (!password_verify($p, $user['password'])) {
            View::render('auth/login.twig', ['error' => 'Mot de passe incorrect.']);
            return;
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        header("Location: /home/index");
        exit;
    }
}
