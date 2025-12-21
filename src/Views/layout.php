<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header>
    <nav>
        <a href="/home/index">Accueil</a>

        {% if session.user_id is defined %}
            <a href="/restaurant/index">Restaurants</a>
            <a href="/reservation/index">Réservations</a>
            <a href="/restaurant/my">Mes restaurants</a>
            
            {% if session.user_role == 'admin' %}
                <a href="/admin/restaurants" style="color: red; font-weight: bold;">Administration</a>
            {% endif %}
            
            <a href="/logout/index">Déconnexion</a>
        {% else %}
            <a href="/login/index">Connexion</a>
            <a href="/register/index">Inscription</a>
        {% endif %}
    </nav>
</header>

<main>
    {% block content %}
        {# Le contenu des pages filles sera injecté ici #}
    {% endblock %}
</main>

<footer>
    <p>© Resto – Projet CESI - HENINE Fael</p>
</footer>

</body>
</html>