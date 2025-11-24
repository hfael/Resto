<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restaurant</title>
</head>
<body>

<?php
$connected = isset($_SESSION['user_id']);
?>

<header>
    <a href="/home/index">Accueil</a>

    <?php if ($connected): ?>
        <a href="/dish/index">Plats</a>
        <a href="/reservation/index">Réservations</a>
        <a href="/logout/index">Déconnexion</a>
    <?php else: ?>
        <a href="/login/index">Connexion</a>
        <a href="/register/index">Inscription</a>
    <?php endif; ?>
</header>

<main>
    <?php echo $pageContent; ?>
</main>

<footer>
    © Restaurant – Projet Étudiant
</footer>

</body>
</html>
