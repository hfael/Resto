# Tests d'intégration API (pytest)

But: fournir des tests qui vérifient le bon fonctionnement des endpoints de l'API en préparation d'un frontend Flutter.

Setup (Windows):
1. python -m venv .venv
2. .venv\Scripts\pip.exe install -r requirements.txt

Lancer les tests:
- .venv\Scripts\python -m pytest -q

Variables d'environnement utiles:
- API_BASE_URL : URL de l'API (par défaut http://localhost:8080/api)
- SEED_KEY : clé utilisée pour l'endpoint de seed (`/test/seed`). Par défaut `devseed` si non défini (uniquement en environnement non-production).

Conseils:
- Les tests sont conçus pour fonctionner contre un serveur API en local (docker-compose up -d).
- Si la table `restaurants` est vide, le test de création de réservation sera ignoré automatiquement.
