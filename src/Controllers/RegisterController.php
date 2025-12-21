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

        View::render('auth/register.twig');
    }

    public function submit()
    {
        $u = trim($_POST['username'] ?? '');
        $e = trim($_POST['email'] ?? '');
        $p = trim($_POST['password'] ?? '');

        if ($u === '' || $e === '' || $p === '') {
            View::render('auth/register.twig', [
                'error' => 'Champs manquants.'
            ]);
            return;
        }

        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
            View::render('auth/register.twig', [
                'error' => 'Email invalide.'
            ]);
            return;
        }

        if (strlen($u) < 3) {
            View::render('auth/register.twig', [
                'error' => "Nom d'utilisateur trop court."
            ]);
            return;
        }

        if (strlen($p) < 4) {
            View::render('auth/register.twig', [
                'error' => 'Mot de passe trop court.'
            ]);
            return;
        }

        if (User::findByUsername($u)) {
            View::render('auth/register.twig', [
                'error' => "Nom d'utilisateur déjà utilisé."
            ]);
            return;
        }

        if (User::findByEmail($e)) {
            View::render('auth/register.twig', [
                'error' => 'Email déjà utilisé.'
            ]);
            return;
        }

        $hash = password_hash($p, PASSWORD_BCRYPT);
        User::create($u, $e, $hash);

        header("Location: /login/index");
        exit;
    }
}
