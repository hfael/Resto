<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #e67e22;
            --text: #333;
            --bg: #f8f9fa;
            --white: #ffffff;
            --admin: #dc3545;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--bg);
            color: var(--text);
        }

        header {
            background-color: var(--primary);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            font-size: 0.95rem;
        }

        nav a:hover {
            color: var(--white);
        }

        nav a.admin-link {
            color: var(--admin);
            font-weight: bold;
            border: 1px solid var(--admin);
            padding: 2px 8px;
            border-radius: 4px;
        }

        nav a.admin-link:hover {
            background-color: var(--admin);
            color: var(--white);
        }

        main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1rem;
            box-sizing: border-box;
        }

        footer {
            background-color: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 1.5rem 0;
            margin-top: auto;
            font-size: 0.85rem;
            border-top: 4px solid var(--accent);
        }

        footer p {
            margin: 0;
            opacity: 0.7;
        }
    </style>
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
                <a href="/admin/restaurants" class="admin-link">Administration</a>
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