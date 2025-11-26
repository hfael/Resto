<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resto</title>
</head>
<body>

<?php
$connected = isset($_SESSION['user_id']);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<header>
    <a href="/home/index">Accueil</a>

    <?php if ($connected): ?>
        <a href="/restaurant/index">Restaurants</a>
        <a href="/reservation/index">Réservations</a>
        <a href="/restaurant/my">Mes restaurants</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="/admin/restaurants">Administration</a>
        <?php endif; ?>
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
    © Resto – Projet CESI - HENINE Fael
</footer>

</body>
</html>
